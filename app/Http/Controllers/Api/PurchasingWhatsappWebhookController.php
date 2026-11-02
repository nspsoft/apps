<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\SupplierContact;
use App\Services\PurchasingWhatsappBotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class PurchasingWhatsappWebhookController extends Controller
{
    protected PurchasingWhatsappBotService $botService;

    public function __construct(PurchasingWhatsappBotService $botService)
    {
        $this->botService = $botService;
    }

    /**
     * Handle incoming webhook from JICOS Engine or Wablas for Purchasing
     */
    public function handle(Request $request)
    {
        Log::info('Purchasing Webhook Payload:', [
            'headers' => $request->headers->all(),
            'body' => $request->all(),
        ]);

        $phone = $request->input('phone') ?: $request->input('sender');
        $message = $request->input('message');
        $pushName = $request->input('pushName');
        
        if (!$phone || !$message) {
            return response()->json(['status' => 'ignored', 'reason' => 'invalid_payload']);
        }

        $isGroup = str_contains($phone, '@g.us') || $request->input('isGroup');

        // Ignore group messages
        if ($isGroup) {
            return response()->json(['status' => 'ignored', 'reason' => 'group_message']);
        }

        // Clean phone number
        $phone = str_replace(['@s.whatsapp.net', '@c.us', '@lid'], '', $phone);
        $phone = $this->resolveLidToPhone($phone, $pushName);

        // Handle outgoing message from phone sync
        $fromMe = $request->boolean('fromMe');
        if ($fromMe) {
            $isSyncEnabled = (bool) \App\Models\AppSetting::get('purchasing_whatsapp_sync_phone_outgoing', false);

            if (!$isSyncEnabled) {
                return response()->json(['status' => 'ignored', 'reason' => 'phone_sync_disabled']);
            }

            // Find supplier
            $supplier = Supplier::where('phone', 'like', "%{$phone}%")->first();
            $supplierId = $supplier?->id;
            if (!$supplierId) {
                $contact = SupplierContact::where('phone', 'like', "%{$phone}%")->first();
                $supplierId = $contact?->supplier_id;
            }

            \App\Models\WhatsappMessage::create([
                'phone' => $phone,
                'direction' => 'outgoing',
                'message' => $message,
                'intent' => 'manual_phone',
                'is_read' => true,
                'module' => 'purchasing',
                'metadata' => [
                    'source' => 'phone_manual',
                    'delivery_success' => true,
                    'pushName' => $pushName,
                ],
                'supplier_id' => $supplierId,
            ]);

            // Mark previous incoming messages as read
            \App\Models\WhatsappMessage::module('purchasing')
                ->where('phone', $phone)
                ->where('direction', 'incoming')
                ->where('is_read', false)
                ->update(['is_read' => true]);

            Cache::forget('purchasing_whatsapp_unread_count');

            return response()->json([
                'status' => 'success',
                'synced' => true,
                'message' => 'Purchasing outgoing message from phone synced successfully.'
            ]);
        }

        try {
            // Process message and get response
            $response = $this->botService->handleIncomingMessage($phone, $message, $pushName);

            return response()->json([
                'status' => 'success',
                'response_sent' => true,
            ]);
        } catch (\Exception $e) {
            Log::error('Purchasing WhatsApp Webhook Error', [
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
     * Resolve WhatsApp LID to real phone number for supplier
     */
    protected function resolveLidToPhone(string $phone, ?string $pushName): string
    {
        $cached = Cache::get("wa_purchasing_lid_to_phone_{$phone}");
        if ($cached) {
            return $cached;
        }

        if (strlen($phone) >= 14 || (!str_starts_with($phone, '62') && !str_starts_with($phone, '08'))) {
            if (!empty($pushName)) {
                $contact = SupplierContact::where('name', 'like', "%{$pushName}%")
                    ->orWhere('phone', 'like', "%{$pushName}%")
                    ->first();

                if ($contact && !empty($contact->phone)) {
                    $realPhone = preg_replace('/[^0-9]/', '', $contact->phone);
                    if (str_starts_with($realPhone, '0')) {
                        $realPhone = '62' . substr($realPhone, 1);
                    }
                    Cache::forever("wa_purchasing_lid_to_phone_{$phone}", $realPhone);
                    return $realPhone;
                }
            }
        }

        return $phone;
    }

    /**
     * Verify webhook
     */
    public function verify(Request $request)
    {
        return response()->json([
            'status' => 'ok',
            'message' => 'Purchasing WhatsApp webhook is active',
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
