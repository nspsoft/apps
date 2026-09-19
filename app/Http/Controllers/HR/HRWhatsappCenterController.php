<?php

namespace App\Http\Controllers\HR;

use App\Http\Controllers\Controller;
use App\Models\AppSetting;
use App\Models\WhatsappMessage;
use App\Models\WhatsappTemplate;
use App\Models\WhatsappContactLabel;
use App\Models\Employee;
use App\Services\FonnteService;
use App\Services\WablasService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class HRWhatsappCenterController extends Controller
{
    protected static ?bool $hasIsReadColumn = null;

    protected function hasIsReadColumn(): bool
    {
        if (self::$hasIsReadColumn !== null) {
            return self::$hasIsReadColumn;
        }

        return self::$hasIsReadColumn = Schema::hasColumn('whatsapp_messages', 'is_read');
    }

    /**
     * Resolve the active WhatsApp service according to HR settings.
     */
    protected function getActiveService(FonnteService $fonnte, WablasService $wablas)
    {
        $hrMode = AppSetting::get('hr_whatsapp_mode', 'same_as_sales');

        if ($hrMode === 'dedicated') {
            $provider = AppSetting::get('hr_whatsapp_provider', 'fonnte');
            if ($provider === 'wablas') {
                $token = AppSetting::get('hr_wablas_api_token', '');
                $url = AppSetting::get('hr_wablas_server_url', 'https://pati.wablas.com');
                return $wablas->setCredentials($token, $url);
            } else {
                $token = AppSetting::get('hr_fonnte_api_token', '');
                return $fonnte->setCredentials($token);
            }
        } else {
            $provider = AppSetting::get('whatsapp_provider', 'fonnte');
            if ($provider === 'wablas') {
                $token = AppSetting::get('wablas_api_token', '');
                $url = AppSetting::get('wablas_server_url', 'https://pati.wablas.com');
                return $wablas->setCredentials($token, $url);
            } else {
                $token = AppSetting::get('fonnte_api_token', '');
                return $fonnte->setCredentials($token);
            }
        }
    }

    /**
     * Display the HR WhatsApp Center
     */
    public function index()
    {
        // Get unique contacts sorted by latest message for HR module
        $contacts = WhatsappMessage::module('hr')
            ->select('phone', 'employee_id')
            ->selectRaw('MAX(created_at) as last_activity')
            ->selectRaw('(SELECT message FROM whatsapp_messages wm WHERE wm.phone = whatsapp_messages.phone AND wm.module = "hr" ORDER BY created_at DESC LIMIT 1) as last_message')
            ->selectRaw('(SELECT intent FROM whatsapp_messages wm WHERE wm.phone = whatsapp_messages.phone AND wm.module = "hr" ORDER BY created_at DESC LIMIT 1) as last_intent')
            ->groupBy('phone', 'employee_id')
            ->orderByDesc('last_activity')
            ->with(['employee' => function ($q) {
                $q->select('id', 'nik', 'full_name', 'department_id', 'position_id', 'phone', 'profile_picture')
                  ->with(['department:id,name', 'position:id,name']);
            }])
            ->get();

        // Match missing employee references by phone number
        $contacts->transform(function ($contact) {
            if (!$contact->employee) {
                $normalizedPhone = preg_replace('/[^0-9]/', '', $contact->phone);
                $variations = [
                    $normalizedPhone,
                    '0' . substr($normalizedPhone, 2),
                    substr($normalizedPhone, 2),
                ];

                $employee = Employee::where(function ($q) use ($variations) {
                    foreach ($variations as $p) {
                        if (empty($p)) continue;
                        $q->orWhere('phone', 'like', "%{$p}%");
                    }
                })->with(['department:id,name', 'position:id,name'])->first();

                if ($employee) {
                    $contact->employee = $employee;
                    $contact->employee_id = $employee->id;
                }
            }
            return $contact;
        });

        $totalUnread = 0;
        $templates = collect();
        $labelPresets = [];

        if ($this->hasIsReadColumn()) {
            $unreadCounts = WhatsappMessage::module('hr')
                ->unread()
                ->selectRaw('phone, COUNT(*) as unread_count')
                ->groupBy('phone')
                ->pluck('unread_count', 'phone');

            $contacts->transform(function ($contact) use ($unreadCounts) {
                $contact->unread_count = $unreadCounts[$contact->phone] ?? 0;
                return $contact;
            });

            $totalUnread = Cache::remember('hr_whatsapp_unread_count', 10, function () {
                return WhatsappMessage::module('hr')->unread()->count();
            });
        } else {
            $contacts->transform(function ($contact) {
                $contact->unread_count = 0;
                return $contact;
            });
        }

        try {
            // Get labels grouped by phone
            $allLabels = WhatsappContactLabel::all()->groupBy('phone');
            $contacts->transform(function ($contact) use ($allLabels) {
                $contact->labels = $allLabels[$contact->phone] ?? collect();
                return $contact;
            });
            $labelPresets = WhatsappContactLabel::presets();
        } catch (\Exception $e) {
            $contacts->transform(function ($contact) {
                $contact->labels = collect();
                return $contact;
            });
        }

        try {
            $templates = WhatsappTemplate::active()->orderBy('sort_order')->get();
        } catch (\Exception $e) {
            // Templates table may not exist yet
        }

        // Active Employees for "Chat Baru" picker
        $employees = Employee::where('is_active', true)
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->select('id', 'nik', 'full_name', 'department_id', 'position_id', 'phone')
            ->with(['department:id,name', 'position:id,name'])
            ->orderBy('full_name')
            ->get();

        return Inertia::render('HR/Whatsapp/Index', [
            'contacts' => $contacts,
            'totalUnread' => $totalUnread,
            'templates' => $templates,
            'labelPresets' => $labelPresets,
            'employees' => $employees,
            'hrMode' => AppSetting::get('hr_whatsapp_mode', 'same_as_sales'),
        ]);
    }

    /**
     * Get messages for a specific phone number (Chat History)
     */
    public function history(string $phone)
    {
        // Mark all incoming messages for this phone as read
        if ($this->hasIsReadColumn()) {
            WhatsappMessage::module('hr')
                ->where('phone', $phone)
                ->where('direction', 'incoming')
                ->where('is_read', false)
                ->update(['is_read' => true]);

            Cache::forget('hr_whatsapp_unread_count');
        }

        $messages = WhatsappMessage::module('hr')
            ->where('phone', $phone)
            ->orderBy('created_at', 'asc')
            ->get();

        return response()->json($messages);
    }

    /**
     * Send a manual message to an employee
     */
    public function send(Request $request, FonnteService $fonnte, WablasService $wablas)
    {
        $request->validate([
            'phone' => 'required',
            'message' => 'nullable|string',
            'file' => 'nullable|file|max:10240', // Max 10MB
            'employee_id' => 'nullable|exists:hr_employees,id',
        ]);

        $phone = $request->phone;
        $message = $request->message ?? '';
        $activeService = $this->getActiveService($fonnte, $wablas);

        $result = ['success' => false, 'error' => 'No content to send'];

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $mime = $file->getMimeType();
            $path = $file->store('whatsapp-attachments', 'public');
            $url = asset('storage/' . $path);

            $attachmentMeta = [
                'url' => $url,
                'mime' => $mime,
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
            ];

            if (str_starts_with($mime, 'image/')) {
                $result = $activeService->sendImage($phone, $url, $message);
                $attachmentMeta['type'] = 'image';
            } else {
                $result = $activeService->sendFile($phone, $url, $message);
                $attachmentMeta['type'] = 'document';
            }

            $logMessage = $message ?: "[File: {$file->getClientOriginalName()}]";
        } elseif (!empty($message)) {
            $result = $activeService->sendMessage($phone, $message);
            $logMessage = $message;
        }

        // Always log the attempt if we had content
        if (isset($logMessage)) {
            $employeeId = $request->employee_id;

            if (!$employeeId) {
                // Find employee by phone number variations
                $normalizedPhone = preg_replace('/[^0-9]/', '', $phone);
                $variations = [
                    $normalizedPhone,
                    '0' . substr($normalizedPhone, 2),
                    substr($normalizedPhone, 2),
                ];

                $employee = Employee::where(function ($q) use ($variations) {
                    foreach ($variations as $p) {
                        if (empty($p)) continue;
                        $q->orWhere('phone', 'like', "%{$p}%");
                    }
                })->first();

                $employeeId = $employee?->id;
            }

            WhatsappMessage::create([
                'phone' => $phone,
                'direction' => 'outgoing',
                'message' => $logMessage,
                'intent' => 'manual_reply',
                'is_read' => true,
                'module' => 'hr',
                'metadata' => array_merge($attachmentMeta ?? [], [
                    'delivery_success' => $result['success'],
                    'delivery_error' => $result['error'] ?? null
                ]),
                'employee_id' => $employeeId,
            ]);
        }

        if ($result['success']) {
            return back()->with('success', 'Pesan WhatsApp berhasil dikirim ke karyawan.');
        }

        $errorMessage = $result['error'] ?? 'Unknown error';

        if (str_contains(request()->getHost(), '.test') || str_contains(request()->getHost(), 'localhost')) {
            $errorMessage .= ". Catatan: Pengiriman lampiran file ke gateway eksternal di localhost sering kali tidak dapat diunduh gateway karena URL lokal tidak publik. Pesan teks tetap diproses.";
        }

        return back()->with('error', 'Gagal mengirim pesan: ' . $errorMessage);
    }

    /**
     * Delete chat history for a specific phone number
     */
    public function destroy(string $phone)
    {
        WhatsappMessage::module('hr')->where('phone', $phone)->delete();
        WhatsappContactLabel::where('phone', $phone)->delete();

        return back()->with('success', 'Riwayat chat WhatsApp berhasil dihapus.');
    }

    /**
     * Get unread count for HR sidebar indicator
     */
    public function unreadCount()
    {
        if (!$this->hasIsReadColumn()) {
            return response()->json([
                'total' => 0,
            ]);
        }

        $total = Cache::remember('hr_whatsapp_unread_count', 10, function () {
            return WhatsappMessage::module('hr')->unread()->count();
        });

        return response()->json([
            'total' => $total,
        ]);
    }

    /**
     * Add a label to a contact.
     */
    public function addLabel(Request $request)
    {
        $data = $request->validate([
            'phone' => 'required|string',
            'label' => 'required|string|max:50',
            'color' => 'required|string|max:20',
        ]);

        WhatsappContactLabel::firstOrCreate(
            ['phone' => $data['phone'], 'label' => $data['label']],
            ['color' => $data['color']]
        );

        return response()->json(['success' => true]);
    }

    /**
     * Remove a label from a contact.
     */
    public function removeLabel(WhatsappContactLabel $label)
    {
        $label->delete();
        return response()->json(['success' => true]);
    }
}
