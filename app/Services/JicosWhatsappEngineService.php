<?php

namespace App\Services;

use App\Models\AppSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class JicosWhatsappEngineService
{
    protected string $serverUrl;
    protected string $apiKey;
    protected string $session = 'sales'; // 'sales' | 'purchasing' | 'inventory' | 'hr'

    public function __construct()
    {
        $this->serverUrl = rtrim((string) (AppSetting::get('usics_engine_server_url') ?: AppSetting::get('jicos_engine_server_url') ?: config('services.jicos_engine.url', 'http://127.0.0.1:5050')), '/');
        $this->apiKey = (string) (AppSetting::get('usics_engine_api_key') ?: AppSetting::get('jicos_engine_api_key') ?: config('services.jicos_engine.key', 'jicos-wa-secret-2026'));
    }

    /**
     * Set target session ('sales' | 'purchasing' | 'inventory' | 'hr')
     */
    public function setSession(string $session): self
    {
        $this->session = in_array($session, ['sales', 'purchasing', 'inventory', 'hr']) ? $session : 'sales';
        return $this;
    }

    /**
     * Set credentials dynamically (e.g. for specific modules or custom testing)
     */
    public function setCredentials(string $apiKey, ?string $serverUrl = null, ?string $session = null): self
    {
        $this->apiKey = $apiKey;
        if ($serverUrl) {
            $this->serverUrl = rtrim($serverUrl, '/');
        }
        if ($session) {
            $this->setSession($session);
        }
        return $this;
    }

    /**
     * Check Engine connection status
     */
    public function getStatus(?string $session = null): array
    {
        $targetSession = $session ?: $this->session;
        try {
            $response = Http::withHeaders([
                'X-API-Key' => $this->apiKey,
            ])->timeout(5)->get("{$this->serverUrl}/status", [
                'session' => $targetSession,
            ]);

            if ($response->successful()) {
                $payload = $response->json() ?? [];
                return array_merge([
                    'success' => true,
                    'connected' => $payload['connected'] ?? false,
                    'status' => $payload['status'] ?? 'disconnected',
                    'session' => $payload['session'] ?? $targetSession,
                    'phone' => $payload['phone'] ?? null,
                    'name' => $payload['name'] ?? null,
                    'data' => $payload,
                ], $payload);
            }

            return [
                'success' => false,
                'connected' => false,
                'status' => 'disconnected',
                'error' => 'Engine returned HTTP status ' . $response->status(),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'connected' => false,
                'status' => 'offline',
                'error' => 'Cannot connect to JICOS WhatsApp Engine: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Send a text message via JICOS WhatsApp Engine
     */
    public function sendMessage(string $phone, string $message): array
    {
        try {
            $response = Http::withHeaders([
                'X-API-Key' => $this->apiKey,
                'Accept' => 'application/json',
            ])->timeout(15)->post("{$this->serverUrl}/api/send-message", [
                'session' => $this->session,
                'phone' => $this->formatPhone($phone),
                'message' => $message,
            ]);

            $result = $response->json();

            Log::info("JICOS WA Engine Send Response [{$this->session}]", [
                'phone' => $phone,
                'status' => $response->status(),
                'result' => $result,
            ]);

            if ($response->successful() && ($result['success'] ?? false) === true) {
                return [
                    'success' => true,
                    'data' => $result,
                    'error' => null,
                ];
            }

            return [
                'success' => false,
                'data' => $result,
                'error' => $result['error'] ?? ('Engine error: HTTP ' . $response->status()),
            ];
        } catch (\Throwable $e) {
            Log::error("JICOS WA Engine Send Error [{$this->session}]: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Connection failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Send an image with caption
     */
    public function sendImage(string $phone, string $imageUrl, string $caption = ''): array
    {
        try {
            $response = Http::withHeaders([
                'X-API-Key' => $this->apiKey,
                'Accept' => 'application/json',
            ])->timeout(25)->post("{$this->serverUrl}/api/send-media", [
                'session' => $this->session,
                'phone' => $this->formatPhone($phone),
                'url' => $imageUrl,
                'caption' => $caption,
                'type' => 'image',
            ]);

            $result = $response->json();

            if ($response->successful() && ($result['success'] ?? false) === true) {
                return [
                    'success' => true,
                    'data' => $result,
                    'error' => null,
                ];
            }

            return [
                'success' => false,
                'error' => $result['error'] ?? ('Engine error: HTTP ' . $response->status()),
            ];
        } catch (\Throwable $e) {
            Log::error("JICOS WA Engine Send Image Error [{$this->session}]: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get participating WhatsApp Groups
     */
    public function getGroups(?string $session = null): array
    {
        $targetSession = $session ?: $this->session;
        try {
            $response = Http::withHeaders([
                'X-API-Key' => $this->apiKey,
                'Accept' => 'application/json',
            ])->timeout(15)->get("{$this->serverUrl}/api/groups", [
                'session' => $targetSession,
            ]);

            if ($response->successful()) {
                return $response->json() ?? ['success' => false, 'groups' => []];
            }

            return [
                'success' => false,
                'groups' => [],
                'error' => 'Engine returned HTTP ' . $response->status(),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'groups' => [],
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send a document/file (PDF, Excel, Word, PowerPoint, etc.)
     */
    public function sendFile(string $phone, string $fileUrl, string $caption = '', string $filename = 'document.pdf', ?string $filePath = null): array
    {
        try {
            $mimetype = 'application/pdf';
            if (str_ends_with(strtolower($filename), '.pptx')) {
                $mimetype = 'application/vnd.openxmlformats-officedocument.presentationml.presentation';
            } elseif (str_ends_with(strtolower($filename), '.docx')) {
                $mimetype = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
            } elseif (str_ends_with(strtolower($filename), '.xlsx')) {
                $mimetype = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
            }

            $response = Http::withHeaders([
                'X-API-Key' => $this->apiKey,
                'Accept' => 'application/json',
            ])->timeout(45)->post("{$this->serverUrl}/api/send-media", [
                'session' => $this->session,
                'phone' => $this->formatPhone($phone),
                'url' => $fileUrl,
                'filePath' => $filePath,
                'caption' => $caption,
                'filename' => $filename,
                'mimetype' => $mimetype,
                'type' => 'document',
            ]);

            $result = $response->json();

            if ($response->successful() && ($result['success'] ?? false) === true) {
                return [
                    'success' => true,
                    'data' => $result,
                    'error' => null,
                ];
            }

            return [
                'success' => false,
                'error' => $result['error'] ?? ('Engine error: HTTP ' . $response->status()),
            ];
        } catch (\Throwable $e) {
            Log::error("JICOS WA Engine Send File Error [{$this->session}]: " . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Format phone number to clean numeric string with country code 62 or preserve Group JID (@g.us)
     */
    protected function formatPhone(string $phone): string
    {
        $raw = trim($phone);
        if (str_ends_with($raw, '@g.us') || str_ends_with($raw, '@s.whatsapp.net')) {
            return $raw;
        }

        $phone = preg_replace('/[^0-9]/', '', $raw);

        if (str_starts_with($phone, '0')) {
            $phone = '62' . substr($phone, 1);
        } elseif (str_starts_with($phone, '8')) {
            $phone = '62' . $phone;
        }

        return $phone;
    }
}
