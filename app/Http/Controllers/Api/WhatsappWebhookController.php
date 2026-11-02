<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Services\WhatsappBotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class WhatsappWebhookController extends Controller
{
    protected WhatsappBotService $botService;

    public function __construct(WhatsappBotService $botService)
    {
        $this->botService = $botService;
    }

    /**
     * Handle incoming webhook from JICOS Engine or Wablas
     */
    public function handle(Request $request)
    {
        Log::info('WhatsApp Webhook Payload:', [
            'headers' => $request->headers->all(),
            'body' => $request->all(),
        ]);

        $phone = $request->input('phone') ?: $request->input('sender');
        $message = $request->input('message');
        $pushName = $request->input('pushName');
        $mediaUrl = $request->input('url') ?: $request->input('media');

        if (!$phone || (!$message && !$mediaUrl)) {
            return response()->json(['status' => 'ignored', 'reason' => 'invalid_payload']);
        }

        $isGroup = str_contains($phone, '@g.us') || $request->input('isGroup');

        // Ignore group messages for CS Bot
        if ($isGroup) {
            return response()->json(['status' => 'ignored', 'reason' => 'group_message']);
        }

        // Clean phone number
        $phone = str_replace(['@s.whatsapp.net', '@c.us', '@lid'], '', $phone);
        $phone = $this->resolveLidToPhone($phone, $pushName);

        // Handle outgoing message from phone sync
        $fromMe = $request->boolean('fromMe');
        if ($fromMe) {
            $session = $request->input('session', 'sales');
            $settingKey = ($session === 'hr' ? 'hr_whatsapp_sync_phone_outgoing' : ($session === 'inventory' ? 'inventory_whatsapp_sync_phone_outgoing' : 'whatsapp_sync_phone_outgoing'));
            $isSyncEnabled = (bool) \App\Models\AppSetting::get($settingKey, false);

            if (!$isSyncEnabled) {
                return response()->json(['status' => 'ignored', 'reason' => 'phone_sync_disabled']);
            }

            // Sync outgoing message to database
            $customer = ($session !== 'hr') ? Customer::where('phone', 'like', "%{$phone}%")->first() : null;
            $employee = ($session === 'hr') ? \App\Models\Employee::where('phone', 'like', "%{$phone}%")->first() : null;

            \App\Models\WhatsappMessage::create([
                'phone' => $phone,
                'direction' => 'outgoing',
                'message' => $message ?? ($mediaUrl ? '[Media File]' : ''),
                'intent' => 'manual_phone',
                'is_read' => true,
                'module' => $session ?: 'sales',
                'metadata' => [
                    'source' => 'phone_manual',
                    'delivery_success' => true,
                    'url' => $mediaUrl,
                    'pushName' => $pushName,
                ],
                'customer_id' => $customer?->id,
                'employee_id' => $employee?->id,
            ]);

            // Mark previous incoming messages as read
            \App\Models\WhatsappMessage::where('phone', $phone)
                ->where('direction', 'incoming')
                ->where('is_read', false)
                ->update(['is_read' => true]);

            Cache::forget('whatsapp_unread_count');

            return response()->json([
                'status' => 'success',
                'synced' => true,
                'message' => 'Outgoing message from phone synced successfully.'
            ]);
        }

        try {
            // Process message and get response
            $response = $this->botService->handleIncomingMessage($phone, $message ?? '', $pushName, $mediaUrl);

            return response()->json([
                'status' => 'success',
                'response_sent' => true,
            ]);
        } catch (\Exception $e) {
            Log::error('WhatsApp Webhook Error', [
                'phone' => $phone,
                'message' => $message,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Resolve WhatsApp LID to real phone number
     */
    protected function resolveLidToPhone(string $phone, ?string $pushName): string
    {
        $cached = Cache::get("wa_lid_to_phone_{$phone}");
        if ($cached) {
            return $cached;
        }

        if (strlen($phone) >= 14 || (!str_starts_with($phone, '62') && !str_starts_with($phone, '08'))) {
            if (!empty($pushName)) {
                $customer = Customer::where('contact_person', 'like', "%{$pushName}%")
                    ->orWhere('name', 'like', "%{$pushName}%")
                    ->first();

                if ($customer && !empty($customer->phone)) {
                    $realPhone = preg_replace('/[^0-9]/', '', $customer->phone);
                    if (str_starts_with($realPhone, '0')) {
                        $realPhone = '62' . substr($realPhone, 1);
                    }
                    Cache::forever("wa_lid_to_phone_{$phone}", $realPhone);
                    Log::info("Resolved WhatsApp LID {$phone} to Customer phone {$realPhone} ({$customer->name})");
                    return $realPhone;
                }
            }
        }

        return $phone;
    }

    /**
     * Verify webhook (for testing)
     */
    public function verify(Request $request)
    {
        return response()->json([
            'status' => 'ok',
            'message' => 'WhatsApp webhook is active',
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
