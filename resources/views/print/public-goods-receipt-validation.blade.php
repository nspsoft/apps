<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Validasi GRN - JIDOKA ERP</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { background-color: #0b0c15; color: #fff; font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="flex items-center justify-center min-h-screen p-4">
    <div class="w-full max-w-md bg-slate-900 border border-slate-800 rounded-[2.5rem] overflow-hidden shadow-2xl">
        <div class="p-8 text-center border-b border-slate-800 bg-slate-950/50">
            <div class="flex justify-center mb-6">
                <img src="/images/jri-official-logo.png" alt="logo" class="h-12">
            </div>
            <h1 class="text-xl font-black italic tracking-tighter text-transparent bg-clip-text bg-gradient-to-r from-emerald-400 to-cyan-500">
                VERIFIKASI PENERIMAAN BARANG
            </h1>
            <p class="text-[10px] text-slate-500 font-bold uppercase tracking-[0.2em] mt-1">Sistem ERP Jidoka Result Indonesia</p>
        </div>

        <div class="p-8 space-y-6">
            <div class="flex items-center gap-4 p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/20">
                <div class="w-10 h-10 rounded-full bg-emerald-500 flex items-center justify-center shadow-lg shadow-emerald-500/20">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="3" stroke="currentColor" class="w-6 h-6 text-white">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                    </svg>
                </div>
                <div>
                    <div class="text-xs font-bold text-emerald-400 uppercase tracking-widest">Keaslian Dokumen</div>
                    <div class="text-lg font-black text-white">TERVERIFIKASI ASLI</div>
                </div>
            </div>

            <div class="space-y-4">
                <div class="flex justify-between border-b border-slate-800/50 pb-2">
                    <span class="text-xs text-slate-500 uppercase font-bold tracking-widest">No. GRN</span>
                    <span class="text-xs font-bold text-white">{{ $receipt->grn_number }}</span>
                </div>
                <div class="flex justify-between border-b border-slate-800/50 pb-2">
                    <span class="text-xs text-slate-500 uppercase font-bold tracking-widest">Supplier</span>
                    <span class="text-xs font-bold text-slate-300">{{ $receipt->supplier->name }}</span>
                </div>
                <div class="flex justify-between border-b border-slate-800/50 pb-2">
                    <span class="text-xs text-slate-500 uppercase font-bold tracking-widest">Gudang</span>
                    <span class="text-xs font-bold text-cyan-400">{{ $receipt->warehouse->name }}</span>
                </div>
                <div class="flex justify-between border-b border-slate-800/50 pb-2">
                    <span class="text-xs text-slate-500 uppercase font-bold tracking-widest">Tgl Terima</span>
                    <span class="text-xs font-bold text-white">{{ date('d M Y', strtotime($receipt->receipt_date)) }}</span>
                </div>
                <div class="flex justify-between pb-2">
                    <span class="text-xs text-slate-500 uppercase font-bold tracking-widest">Status Penerimaan</span>
                    <span class="text-xs font-bold uppercase tracking-widest px-2 py-0.5 rounded bg-blue-500/20 text-blue-400">{{ $receipt->status }}</span>
                </div>
            </div>

            <div class="mt-6 border-t border-slate-800/50 pt-6">
                <h3 class="text-xs font-bold text-emerald-400 mb-4 uppercase tracking-widest">Detail Pengiriman</h3>
                <div class="overflow-hidden rounded-xl border border-slate-800">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-800/50 text-slate-400 uppercase tracking-wider font-bold">
                            <tr>
                                <th class="px-4 py-3">Produk</th>
                                <th class="px-4 py-3 text-center">Dikirim</th>
                                <th class="px-4 py-3 text-center">Satuan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800 bg-slate-900/30">
                            @foreach ($receipt->items as $item)
                            <tr class="hover:bg-slate-800/30 transition-colors">
                                <td class="px-4 py-3">
                                    <div class="font-bold text-white mb-0.5">{{ $item->product->name ?? $item->product_name }}</div>
                                    <div class="text-[10px] text-slate-500 font-mono tracking-wide">{{ $item->product->sku ?? '-' }}</div>
                                </td>
                                <td class="px-4 py-3 text-center font-black text-white text-sm">{{ number_format($item->qty_ordered, 0, ',', '.') }}</td>
                                <td class="px-4 py-3 text-center text-cyan-500 font-bold text-[10px] uppercase">{{ $item->product->unit->code ?? 'PCS' }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="pt-6 text-center">
                <p class="text-[9px] text-slate-500 italic">Verifikasi dilakukan secara real-time pada {{ date('d/m/Y H:i:s') }}. Dokumen ini terdaftar secara sah dalam manajemen rantai pasok Jidoka.</p>
            </div>
        </div>

        <div class="p-4 bg-slate-950 text-center">
            <p class="text-[8px] font-bold text-slate-700 tracking-[0.3em] uppercase">© 2026 PT. JIDOKA RESULT INDONESIA</p>
        </div>
    </div>
</body>
</html>
