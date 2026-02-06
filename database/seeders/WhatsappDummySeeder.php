<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\WhatsappMessage;
use Illuminate\Database\Seeder;

class WhatsappDummySeeder extends Seeder
{
    public function run(): void
    {
        // Get some customers
        $customers = Customer::take(3)->get();

        // Conversation 1: Order Status Inquiry
        $customer1 = $customers->first();
        $phone1 = '6281234567890';

        WhatsappMessage::create([
            'phone' => $phone1,
            'customer_id' => $customer1?->id,
            'direction' => 'incoming',
            'message' => 'Halo, saya mau cek status pesanan saya',
            'intent' => 'greeting',
            'created_at' => now()->subHours(2),
        ]);

        WhatsappMessage::create([
            'phone' => $phone1,
            'customer_id' => $customer1?->id,
            'direction' => 'outgoing',
            'message' => "Halo " . ($customer1?->name ?? 'Bapak/Ibu') . "! 👋\n\nSelamat datang di layanan WhatsApp *PT SPINDO*.\n\nSaya bisa membantu Anda untuk:\n• Cek status pesanan\n• Cek tagihan/invoice\n• Informasi produk\n\nSilakan ketik pertanyaan Anda.",
            'intent' => 'greeting',
            'created_at' => now()->subHours(2)->addMinutes(1),
        ]);

        WhatsappMessage::create([
            'phone' => $phone1,
            'customer_id' => $customer1?->id,
            'direction' => 'incoming',
            'message' => 'Status SO-2024-0015',
            'intent' => 'order_status',
            'created_at' => now()->subHours(1)->addMinutes(30),
        ]);

        WhatsappMessage::create([
            'phone' => $phone1,
            'customer_id' => $customer1?->id,
            'direction' => 'outgoing',
            'message' => "📦 *Status Pesanan*\n\n*SO-2024-0015*\nStatus: 🔄 Diproses\nTanggal: 05 Feb 2024\n\n*Item:*\n• Pipa Besi 2\" Medium (50 btg)\n• Pipa Galvanis 3\" (25 btg)\n\n*Pengiriman:*\nNo. DO: DO-2024-0012\nTanggal: 08 Feb 2024",
            'intent' => 'order_status',
            'created_at' => now()->subHours(1)->addMinutes(31),
        ]);

        // Conversation 2: Product Inquiry
        $customer2 = $customers->skip(1)->first();
        $phone2 = '6287812345678';

        WhatsappMessage::create([
            'phone' => $phone2,
            'customer_id' => $customer2?->id,
            'direction' => 'incoming',
            'message' => 'Harga pipa galvanis berapa ya?',
            'intent' => 'product_catalog',
            'created_at' => now()->subMinutes(45),
        ]);

        WhatsappMessage::create([
            'phone' => $phone2,
            'customer_id' => $customer2?->id,
            'direction' => 'outgoing',
            'message' => "🔧 *Hasil Pencarian: \"galvanis\"*\n\n• *Pipa Galvanis 2\"* (PIP-GAL-02)\n  Harga: Rp 185.000\n  Stok: 150 btg\n\n• *Pipa Galvanis 3\"* (PIP-GAL-03)\n  Harga: Rp 275.000\n  Stok: 80 btg\n\n• *Pipa Galvanis 4\"* (PIP-GAL-04)\n  Harga: Rp 420.000\n  Stok: 45 btg\n\nKetik \"Minta penawaran [Nama Produk]\" untuk request harga khusus.",
            'intent' => 'product_catalog',
            'created_at' => now()->subMinutes(44),
        ]);

        WhatsappMessage::create([
            'phone' => $phone2,
            'customer_id' => $customer2?->id,
            'direction' => 'incoming',
            'message' => 'Minta penawaran Pipa Galvanis 3 inch 200 batang',
            'intent' => 'request_quotation',
            'created_at' => now()->subMinutes(30),
        ]);

        WhatsappMessage::create([
            'phone' => $phone2,
            'customer_id' => $customer2?->id,
            'direction' => 'outgoing',
            'message' => "✅ Permintaan penawaran untuk *Pipa Galvanis 3 inch* (200 batang) telah kami terima.\n\nTim sales kami akan segera menghubungi Anda dengan penawaran resmi.\n\nTerima kasih!",
            'intent' => 'request_quotation',
            'created_at' => now()->subMinutes(29),
        ]);

        // Conversation 3: Invoice Check
        $customer3 = $customers->skip(2)->first();
        $phone3 = '6289998887777';

        WhatsappMessage::create([
            'phone' => $phone3,
            'customer_id' => $customer3?->id,
            'direction' => 'incoming',
            'message' => 'Cek tagihan saya dong',
            'intent' => 'invoice_check',
            'created_at' => now()->subMinutes(15),
        ]);

        WhatsappMessage::create([
            'phone' => $phone3,
            'customer_id' => $customer3?->id,
            'direction' => 'outgoing',
            'message' => "📄 *Invoice Outstanding*\n\n• *INV-2024-0088*\n  Rp 15.750.000\n  ⚠️ JATUH TEMPO\n\n• *INV-2024-0102*\n  Rp 8.250.000\n  📅 10 Feb 2024\n\n━━━━━━━━━━━━━━━\n*Total: Rp 24.000.000*\n\nUntuk pembayaran, transfer ke:\nBCA 123-456-789\na.n. PT SPINDO Tbk",
            'intent' => 'invoice_check',
            'created_at' => now()->subMinutes(14),
        ]);

        $this->command->info('✅ WhatsApp dummy data created successfully!');
    }
}
