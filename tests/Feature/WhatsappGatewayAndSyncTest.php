<?php

namespace Tests\Feature;

use App\Models\AppSetting;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\User;
use App\Models\WhatsappMessage;
use App\Services\WhatsappBotService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsappGatewayAndSyncTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
    }

    /**
     * Test 1: WhatsApp Settings page is accessible and defaults to port 5055.
     */
    public function test_whatsapp_settings_page_loads_with_port_5055(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('settings.whatsapp.index'));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Settings/WhatsappSettings')
            ->has('settings.jicos_engine_server_url')
            ->where('settings.jicos_engine_server_url', 'http://127.0.0.1:5055')
            ->has('settings.whatsapp_sync_phone_outgoing')
            ->has('settings.purchasing_whatsapp_sync_phone_outgoing')
        );
    }

    /**
     * Test 2: Can update WhatsApp Settings and toggle phone sync per module.
     */
    public function test_can_update_whatsapp_settings_and_phone_sync_toggles(): void
    {
        $payload = [
            'whatsapp_provider' => 'jicos_engine',
            'jicos_engine_server_url' => 'http://127.0.0.1:5055',
            'jicos_engine_api_key' => 'jicos-test-key-2026',
            'whatsapp_sync_phone_outgoing' => true,

            'purchasing_whatsapp_provider' => 'jicos_engine',
            'purchasing_jicos_engine_server_url' => 'http://127.0.0.1:5055',
            'purchasing_jicos_engine_api_key' => 'jicos-test-key-2026',
            'purchasing_whatsapp_sync_phone_outgoing' => true,

            'inventory_whatsapp_provider' => 'jicos_engine',
            'inventory_jicos_engine_server_url' => 'http://127.0.0.1:5055',
            'inventory_jicos_engine_api_key' => 'jicos-test-key-2026',
            'inventory_whatsapp_sync_phone_outgoing' => false,

            'hr_whatsapp_mode' => 'dedicated',
            'hr_whatsapp_provider' => 'jicos_engine',
            'hr_jicos_engine_server_url' => 'http://127.0.0.1:5055',
            'hr_jicos_engine_api_key' => 'jicos-test-key-2026',
            'hr_whatsapp_sync_phone_outgoing' => true,
        ];

        $response = $this->actingAs($this->user)
            ->post(route('settings.whatsapp.update'), $payload);

        $response->assertSessionHas('success');

        $this->assertEquals('1', AppSetting::get('whatsapp_sync_phone_outgoing'));
        $this->assertEquals('1', AppSetting::get('purchasing_whatsapp_sync_phone_outgoing'));
        $this->assertEquals('0', AppSetting::get('inventory_whatsapp_sync_phone_outgoing'));
        $this->assertEquals('1', AppSetting::get('hr_whatsapp_sync_phone_outgoing'));
    }

    /**
     * Test 3: Inbound webhook ignores group messages.
     */
    public function test_inbound_webhook_ignores_group_messages(): void
    {
        $response = $this->postJson('/whatsapp/webhook', [
            'session' => 'sales',
            'phone' => '628123456789-123456789@g.us',
            'message' => 'Pesan dari grup kantor',
            'fromMe' => false,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'ignored', 'reason' => 'group_message']);
    }

    /**
     * Test 4: Outgoing phone manual message is synced when toggle is ON.
     */
    public function test_sales_outgoing_phone_sync_stores_message_when_toggle_enabled(): void
    {
        AppSetting::set('whatsapp_sync_phone_outgoing', '1');

        $customer = Customer::create([
            'name' => 'PT Test Customer',
            'phone' => '081299998888',
            'code' => 'CUST-TEST-01',
        ]);

        $response = $this->postJson('/whatsapp/webhook', [
            'session' => 'sales',
            'phone' => '081299998888',
            'message' => 'Halo Pak, pesanan sudah siap dikirim ya.',
            'fromMe' => true,
            'sender' => '6285888883258',
            'pushName' => 'Admin Sales',
            'source' => 'jicos_engine',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success', 'synced' => true]);

        $this->assertDatabaseHas('whatsapp_messages', [
            'phone' => '081299998888',
            'direction' => 'outgoing',
            'intent' => 'manual_phone',
            'module' => 'sales',
            'is_read' => true,
            'customer_id' => $customer->id,
        ]);
    }

    /**
     * Test 5: Outgoing phone manual message is ignored when toggle is OFF.
     */
    public function test_sales_outgoing_phone_sync_ignored_when_toggle_disabled(): void
    {
        AppSetting::set('whatsapp_sync_phone_outgoing', '0');

        $response = $this->postJson('/whatsapp/webhook', [
            'session' => 'sales',
            'phone' => '081299998888',
            'message' => 'Pesan rahasia di HP yang tidak ingin masuk ERP',
            'fromMe' => true,
            'sender' => '6285888883258',
            'pushName' => 'Admin Sales',
            'source' => 'jicos_engine',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'ignored', 'reason' => 'phone_sync_disabled']);

        $this->assertDatabaseMissing('whatsapp_messages', [
            'message' => 'Pesan rahasia di HP yang tidak ingin masuk ERP',
        ]);
    }

    /**
     * Test 6: Outgoing phone message automatically marks previous unread customer messages as read.
     */
    public function test_sales_outgoing_phone_sync_marks_unread_incoming_as_read(): void
    {
        AppSetting::set('whatsapp_sync_phone_outgoing', '1');

        $unreadMessage = WhatsappMessage::create([
            'phone' => '081299998888',
            'direction' => 'incoming',
            'message' => 'Halo apakah barang saya sudah ready?',
            'is_read' => false,
            'module' => 'sales',
        ]);

        $this->assertFalse((bool) $unreadMessage->is_read);

        $response = $this->postJson('/whatsapp/webhook', [
            'session' => 'sales',
            'phone' => '081299998888',
            'message' => 'Sudah ready Pak!',
            'fromMe' => true,
        ]);

        $response->assertStatus(200);
        $unreadMessage->refresh();
        $this->assertTrue((bool) $unreadMessage->is_read);
    }

    /**
     * Test 7: Purchasing outgoing phone sync links to Supplier model.
     */
    public function test_purchasing_outgoing_phone_sync_links_to_supplier(): void
    {
        AppSetting::set('purchasing_whatsapp_sync_phone_outgoing', '1');

        $supplier = Supplier::create([
            'name' => 'PT Supplier Utama',
            'phone' => '081277776666',
            'code' => 'SUPP-001',
        ]);

        $response = $this->postJson('/whatsapp-purchasing/webhook', [
            'session' => 'purchasing',
            'phone' => '081277776666',
            'message' => 'Halo tim supplier, kami konfirmasi PO no 123.',
            'fromMe' => true,
            'sender' => '6285888883258',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'success', 'synced' => true]);

        $this->assertDatabaseHas('whatsapp_messages', [
            'phone' => '081277776666',
            'direction' => 'outgoing',
            'intent' => 'manual_phone',
            'module' => 'purchasing',
            'supplier_id' => $supplier->id,
        ]);
    }

    /**
     * Test 8: Purchasing outgoing phone sync is ignored when toggle is OFF.
     */
    public function test_purchasing_outgoing_phone_sync_ignored_when_toggle_disabled(): void
    {
        AppSetting::set('purchasing_whatsapp_sync_phone_outgoing', '0');

        $response = $this->postJson('/whatsapp-purchasing/webhook', [
            'session' => 'purchasing',
            'phone' => '081277776666',
            'message' => 'Pesan supplier di HP pribadi',
            'fromMe' => true,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['status' => 'ignored', 'reason' => 'phone_sync_disabled']);

        $this->assertDatabaseMissing('whatsapp_messages', [
            'message' => 'Pesan supplier di HP pribadi',
        ]);
    }

    /**
     * Test 9: Failed message recording preserves delivery_success: false and delivery_error for manual resend.
     */
    public function test_failed_whatsapp_message_is_logged_with_delivery_error_and_flagged_for_manual_resend(): void
    {
        $message = WhatsappMessage::create([
            'phone' => '085863633885',
            'direction' => 'outgoing',
            'message' => 'Tes pesan gagal terkirim',
            'intent' => 'manual_reply',
            'is_read' => true,
            'module' => 'sales',
            'metadata' => [
                'delivery_success' => false,
                'delivery_error' => "Session 'sales' is not connected. Status: qr_ready",
            ],
        ]);

        $this->assertFalse($message->metadata['delivery_success']);
        $this->assertStringContainsString('qr_ready', $message->metadata['delivery_error']);

        // Check history API returns metadata with delivery_success false
        $historyResponse = $this->actingAs($this->user)
            ->get(route('sales.whatsapp.history', '085863633885'));

        $historyResponse->assertStatus(200);
        $data = $historyResponse->json();
        $found = collect($data)->firstWhere('id', $message->id);
        $this->assertNotNull($found);
        $this->assertFalse($found['metadata']['delivery_success']);
    }

    /**
     * Test 10: Status check endpoint returns JSON.
     */
    public function test_session_status_endpoint_returns_json_response(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('settings.whatsapp.status', [
                'server_url' => 'http://127.0.0.1:5055',
                'api_key' => 'jicos-wa-secret-2026',
                'session' => 'sales',
            ]));

        $response->assertStatus(200);
        $this->assertArrayHasKey('success', $response->json());
    }
}
