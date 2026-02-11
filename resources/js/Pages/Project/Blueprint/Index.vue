<script setup>
import { ref, computed, onMounted, watch, nextTick } from 'vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
import mermaid from 'mermaid';
import { 
    BookOpenIcon, 
    CpuChipIcon, 
    ShieldCheckIcon, 
    CircleStackIcon, 
    DocumentCheckIcon,
    BeakerIcon,
    ArrowPathIcon,
    PresentationChartLineIcon,
    ShareIcon,
    UsersIcon
} from '@heroicons/vue/24/outline';

const props = defineProps({
    project: Object,
});

const activeTab = ref('overview');

const menuItems = [
    { id: 'overview', label: 'Executive Summary', icon: PresentationChartLineIcon },
    { id: 'manpower', label: 'Organization Chart', icon: UsersIcon },
    { id: 'flowchat', label: 'System Flow Chart', icon: ShareIcon },
    { id: 'bpd', label: 'Business Process (BPD)', icon: ArrowPathIcon },
    { id: 'frd', label: 'Functional Req (FRD)', icon: CpuChipIcon },
    { id: 'database', label: 'Database Structure', icon: CircleStackIcon },
    { id: 'security', label: 'Security Matrix', icon: ShieldCheckIcon },
    { id: 'integration', label: 'Integration Specs', icon: BookOpenIcon },
    { id: 'qc', label: 'Quality Control', icon: BeakerIcon },
    { id: 'uat', label: 'UAT Scenarios', icon: DocumentCheckIcon },
];

const content = {
    overview: {
        title: 'Executive Summary',
        subtitle: 'JICOS ERP Implementation for PT JIDOKA',
        body: `
            <div class="space-y-6">
                <div class="bg-indigo-900/50 p-6 rounded-xl border border-indigo-500/30">
                    <h3 class="text-xl font-bold text-indigo-300 mb-2">Visi Digitalisasi</h3>
                    <p class="text-gray-300">
                        Transformasi total operasional PT JIDOKA dari manual menjadi terintegrasi penuh, 
                        mendukung standar <strong>High-Precision Packaging</strong> untuk industri otomotif (OEM).
                    </p>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-gray-800 p-4 rounded-lg">
                        <div class="text-sm text-gray-400">Target Go-Live</div>
                        <div class="text-2xl font-bold text-white">01 March 2026</div>
                    </div>
                    <div class="bg-gray-800 p-4 rounded-lg">
                        <div class="text-sm text-gray-400">Compliance Standard</div>
                        <div class="text-2xl font-bold text-white">IATF 16949</div>
                    </div>
                </div>
            </div>
        `
    },
    manpower: {
        title: 'Mapping Distribusi Manpower JRI',
        subtitle: '2025 Packaging Division Structure',
        isMermaid: true,
        body: `
            flowchart TD
                %% Styles
                classDef default fill:#1e293b,stroke:#94a3b8,color:#fff,stroke-width:1px;
                classDef root fill:#0f172a,stroke:#06b6d4,color:#22d3ee,stroke-width:2px;
                classDef dept fill:#1e293b,stroke:#f59e0b,color:#fbbf24,stroke-width:2px;
                classDef subteam fill:#0f172a,stroke:#64748b,color:#94a3b8,stroke-width:1px,stroke-dasharray: 5 5;

                ROOT[PACKAGING DIVISION]:::root
                
                %% Departments
                ROOT --> PURCH[PURCHASING, GA & HRD]:::dept
                ROOT --> MITSUBISHI[MITSUBISHI GROUP<br/>INTERNAL SALES]:::dept
                ROOT --> HONDA[HONDA GROUP<br/>INTERNAL SALES]:::dept
                ROOT --> OTHERS[OTHERS GROUP<br/>INTERNAL SALES]:::dept
                ROOT --> MKT[MKT & ENGINEERING<br/>DEVELOPMENT]:::dept
                ROOT --> ADMIN[ADMINISTRATION &<br/>FAKTUR PAJAK]:::dept
                
                %% Teams Purchasing
                PURCH --> P_CS[Control Stock Cons.<br/>Part & GA]:::subteam
                PURCH --> P_DR[Driver Team]:::subteam
                
                %% Teams Mitsubishi
                MITSUBISHI --> M_PP[Partition & Pad]:::subteam
                MITSUBISHI --> M_CB[Cart Box, RPD DLL]:::subteam
                
                %% Teams Honda
                HONDA --> H_IMP[Impraboard]:::subteam
                HONDA --> H_CB[Carton Box DLL]:::subteam
                
                %% Teams Others
                OTHERS --> O_SL[Sliter]:::subteam
                OTHERS --> O_CBL[Carton Box, Layer<br/>Partition DLL]:::subteam
        `,
        details: `
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mt-8">
                <!-- Group 1: Purchasing -->
                <div class="bg-[#0a0a1a]/50 border border-slate-800 rounded-xl p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-lg bg-blue-900/30 flex items-center justify-center text-blue-400 font-bold">HRD</div>
                        <div>
                            <h3 class="font-bold text-white text-sm">PURCHASING, GA & HRD</h3>
                            <p class="text-xs text-slate-400">Ely Susanti, Agus Supriyanto</p>
                        </div>
                    </div>
                    <div class="space-y-3 text-xs text-slate-300 pl-13">
                        <div class="pl-4 border-l-2 border-blue-900/50">
                            <strong class="text-blue-400 block mb-1">Control Stock & GA</strong>
                            <p>Ahmad Mulyana</p>
                        </div>
                        <div class="pl-4 border-l-2 border-blue-900/50">
                            <strong class="text-blue-400 block mb-1">Drivers</strong>
                            <p>Moh Chanifudin, Eci Nugraha, Rustam Nawawi, Yasin Bin Enin, Panji Agus P.</p>
                        </div>
                    </div>
                </div>

                <!-- Group 2: Mitsubishi -->
                <div class="bg-[#0a0a1a]/50 border border-slate-800 rounded-xl p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-lg bg-red-900/30 flex items-center justify-center text-red-400 font-bold">MITS</div>
                        <div>
                            <h3 class="font-bold text-white text-sm">MITSUBISHI GROUP</h3>
                            <p class="text-xs text-slate-400">Nanang Mulyana, Sarif Hidayat</p>
                        </div>
                    </div>
                    <div class="space-y-3 text-xs text-slate-300">
                        <div class="pl-4 border-l-2 border-red-900/50">
                            <strong class="text-red-400 block mb-1">Sub-Teams</strong>
                            <ul class="list-disc pl-4 space-y-1">
                                <li><strong>Partition & Pad:</strong> Suhoeru -> Sliter (Syahrir + PKL), Pad Stempel (M. A Rohman + PKL), DXC20/26 (New Operator + PKL)</li>
                                <li><strong>Cart Box:</strong> Tri Setiono -> PS823 & PS824 (Akbar Yana + PKL)</li>
                            </ul>
                        </div>
                        <div class="mt-4 pt-4 border-t border-slate-800">
                            <strong class="text-slate-500 block mb-2 text-[10px] uppercase tracking-wider">Key Customers</strong>
                            <p class="leading-relaxed">Echo Advace Technology, Fujitrans Logistics, C&B Indonesia, Leoco Indonesia, Mitsubishi Motors Krama Yudha Ind, SK Metalindo, United Steel Center, Bina Kemas Persada.</p>
                        </div>
                    </div>
                </div>

                <!-- Group 3: Honda -->
                <div class="bg-[#0a0a1a]/50 border border-slate-800 rounded-xl p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-lg bg-yellow-900/30 flex items-center justify-center text-yellow-400 font-bold">HND</div>
                        <div>
                            <h3 class="font-bold text-white text-sm">HONDA GROUP</h3>
                            <p class="text-xs text-slate-400">Aang Kunaepi, Andi Suhandi</p>
                        </div>
                    </div>
                    <div class="space-y-3 text-xs text-slate-300">
                        <div class="pl-4 border-l-2 border-yellow-900/50">
                            <strong class="text-yellow-400 block mb-1">Sub-Teams</strong>
                            <ul class="list-disc pl-4 space-y-1">
                                <li><strong>Impraboard:</strong> M Fahrul Rozy, New Operator, PKL</li>
                                <li><strong>Carton Box:</strong> M Ardiansyah, New Operator, PKL</li>
                            </ul>
                        </div>
                        <div class="mt-4 pt-4 border-t border-slate-800">
                            <strong class="text-slate-500 block mb-2 text-[10px] uppercase tracking-wider">Key Customers</strong>
                            <p class="leading-relaxed">Anzen Pakarindo, Honda Prospect Motor, LogisALL Global, NX Shoji, Sakae Riken, Sekisui Kasae, Mics Steel, Jaya Victori Cemerlang.</p>
                        </div>
                    </div>
                </div>

                <!-- Group 4: Others -->
                <div class="bg-[#0a0a1a]/50 border border-slate-800 rounded-xl p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-lg bg-emerald-900/30 flex items-center justify-center text-emerald-400 font-bold">OTH</div>
                        <div>
                            <h3 class="font-bold text-white text-sm">OTHERS GROUP</h3>
                            <p class="text-xs text-slate-400">Amur, Irwan S.</p>
                        </div>
                    </div>
                    <div class="space-y-3 text-xs text-slate-300">
                        <div class="pl-4 border-l-2 border-emerald-900/50">
                            <strong class="text-emerald-400 block mb-1">Sub-Teams</strong>
                            <ul class="list-disc pl-4 space-y-1">
                                <li><strong>Sliter:</strong> Firman Chani, Galih Sunarya</li>
                                <li><strong>Carton Box Layer:</strong> Khoirul Anam, New Operators (2), PKL (2)</li>
                            </ul>
                        </div>
                        <div class="mt-4 pt-4 border-t border-slate-800">
                            <strong class="text-slate-500 block mb-2 text-[10px] uppercase tracking-wider">Key Customers</strong>
                            <p class="leading-relaxed">Citra Plastik Makmur, Dharma Precision Parts, Kojima Auto Tech, Kyoraku Blowmolding, Nippon Konpo, Progress Diecast, Trustindo Mekatronics, Yuju Indonesia, Origin Durachem, Adharco Jaya Selaras.</p>
                        </div>
                    </div>
                </div>

                <!-- Specialized: MKT & Admin -->
                <div class="bg-[#0a0a1a]/50 border border-slate-800 rounded-xl p-6 lg:col-span-2 grid grid-cols-2 gap-4">
                     <div>
                        <div class="flex items-center gap-3 mb-2">
                             <div class="p-2 bg-purple-900/30 rounded text-purple-400"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.384-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z" /></svg></div>
                             <div>
                                 <h3 class="font-bold text-white text-xs">MKT & ENGINEERING</h3>
                                 <p class="text-xs text-slate-400">Ahmad Rubangi</p>
                             </div>
                        </div>
                     </div>
                     <div>
                        <div class="flex items-center gap-3 mb-2">
                             <div class="p-2 bg-pink-900/30 rounded text-pink-400"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg></div>
                             <div>
                                 <h3 class="font-bold text-white text-xs">ADMIN & TAX</h3>
                                 <p class="text-xs text-slate-400">Ahmad Hasanudin</p>
                             </div>
                        </div>
                     </div>
                </div>
            </div>
        `
    },
    flowchat: {
        title: 'System Process Flow',
        subtitle: 'Inventory Flow with Customer Order',
        isMermaid: true,
        body: `
            graph TD
                %% Styles
                classDef default fill:#1e293b,stroke:#94a3b8,color:#fff,stroke-width:1px;
                classDef decision fill:#0f172a,stroke:#06b6d4,color:#22d3ee,stroke-width:2px;
                classDef terminal fill:#0f172a,stroke:#4ade80,color:#4ade80,stroke-width:2px,stroke-dasharray: 5 5;
                classDef document fill:#1e293b,stroke:#f59e0b,color:#fbbf24,stroke-width:1px;
                
                %% Nodes
                start([Customer Purchase Order]):::terminal --> SO[Sales Order]
                SO --> pop{POP Decision:<br/>Pick, Order, Produce?}:::decision
                
                %% Pick Branch
                pop -- Pick --> pick_ticket[Create 'Pick Ticket']:::document
                pick_ticket --> pick_item[Pick up items from<br/>current inventory]
                
                %% Order Branch
                pop -- Order --> det_order_qty[Determine Order Qty]
                det_order_qty --> create_po[Create Purchase Order<br/>and Send]:::document
                create_po --> receive_item[Receive Items]
                
                %% Produce Branch
                pop -- Produce --> det_prod_qty[Determine Quantity]
                det_prod_qty --> create_wo[Create Work Order]:::document
                create_wo --> manufacture[Assemble or<br/>Manufacture Items]
                
                %% Convergence
                pick_item --> box[Box and package<br/>the items]
                receive_item --> box
                manufacture --> box
                
                box --> docs[Create Delivery Order<br/>and Invoice]:::document
                docs --> ship([Ship to Customer]):::terminal
        `,
        details: `
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mt-12 border-t border-slate-800 pt-8 bg-[#0a0a1a]/50 p-8 rounded-xl">
                <div class="space-y-4">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="p-2 bg-blue-900/30 rounded-lg text-blue-400">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" /></svg>
                        </div>
                        <h3 class="text-lg font-bold text-white">INTERNAL SALES GROUP</h3>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="bg-slate-800/50 p-4 rounded-lg border border-slate-700/50">
                            <strong class="text-cyan-400 block mb-2 text-xs uppercase tracking-wider">Preparation</strong>
                            <ol class="list-decimal pl-4 space-y-1 text-sm text-slate-300">
                                <li>Membuat List Master Product (Finished Goods)</li>
                                <li>Membuat List Master Customer</li>
                            </ol>
                        </div>
                        <div class="bg-slate-800/50 p-4 rounded-lg border border-slate-700/50">
                            <strong class="text-cyan-400 block mb-2 text-xs uppercase tracking-wider">Regular Flow</strong>
                            <ol class="list-decimal pl-4 space-y-1 text-sm text-slate-300" start="3">
                                <li>Input Forecast Customer ke System</li>
                                <li>Input PO Customer ke System sebagai SO</li>
                                <li>Input Schedule Delivery ke System</li>
                                <li>Membuat Monthly Production Planning (MPP)</li>
                                <li>Membuat Work Order Sheet (WOS)</li>
                                <li>Input Output Produksi sebagai GR dari WOS</li>
                                <li>Membuat Delivery Order (Surat Jalan)</li>
                                <li>Membuat Invoice</li>
                            </ol>
                        </div>
                    </div>
                </div>
                
                <div class="space-y-4">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="p-2 bg-emerald-900/30 rounded-lg text-emerald-400">
                            <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                        </div>
                        <h3 class="text-lg font-bold text-white">PURCHASING, GA & HRD TEAM</h3>
                    </div>
                    
                    <div class="space-y-4">
                        <div class="bg-slate-800/50 p-4 rounded-lg border border-slate-700/50">
                            <strong class="text-emerald-400 block mb-2 text-xs uppercase tracking-wider">Preparation</strong>
                            <ol class="list-decimal pl-4 space-y-1 text-sm text-slate-300">
                                <li>Membuat List Master Product (Raw Material)</li>
                                <li>Membuat List Master Supplier</li>
                            </ol>
                        </div>
                        <div class="bg-slate-800/50 p-4 rounded-lg border border-slate-700/50">
                            <strong class="text-emerald-400 block mb-2 text-xs uppercase tracking-wider">Regular Flow</strong>
                            <ol class="list-decimal pl-4 space-y-1 text-sm text-slate-300" start="3">
                                <li>Membuat PO Supplier (Material)</li>
                                <li>Membuat PO Subcont (Jasa Process)</li>
                                <li>Melakukan Good Received (GR) Material</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>
        `
    },
    bpd: {
        title: 'Business Process Document',
        subtitle: 'As-Is (Manual) vs To-Be (Digital JICOS)',
        body: `
            <div class="space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="bg-red-900/20 p-6 rounded-xl border border-red-500/30">
                        <h3 class="text-lg font-bold text-red-400 mb-4">AS-IS (Lama)</h3>
                        <ul class="list-disc pl-5 space-y-2 text-gray-300">
                            <li>Pencatatan manual di Excel/Buku Tulis.</li>
                            <li>Cek stok fisik memakan waktu lama.</li>
                            <li>Traceability Lot sulit dilacak saat audit.</li>
                        </ul>
                    </div>
                    <div class="bg-emerald-900/20 p-6 rounded-xl border border-emerald-500/30">
                        <h3 class="text-lg font-bold text-emerald-400 mb-4">TO-BE (JICOS)</h3>
                        <ul class="list-disc pl-5 space-y-2 text-gray-300">
                            <li>Digital SPK & Dashboard Real-time.</li>
                            <li>Stok otomatis berkurang saat produksi.</li>
                            <li><strong>Traceability QR Code</strong> instan.</li>
                        </ul>
                    </div>
                </div>
            </div>
        `
    },
    frd: {
        title: 'Functional Requirements',
        subtitle: 'Precision Packaging Module Logic',
        body: `
            <div class="space-y-4">
                <div class="bg-slate-800 p-5 rounded-lg">
                    <h4 class="font-bold text-white mb-2">Material-to-Carton Logic</h4>
                    <p class="text-gray-400 text-sm">Sistem menghitung konversi otomatis dari Sheet Board menjadi Carton Box berdasarkan:</p>
                    <ul class="mt-2 space-y-1 text-sm text-cyan-400">
                        <li>• <strong>Up Value:</strong> Jumlah box per sheet.</li>
                        <li>• <strong>Production Waste:</strong> Sisa potongan (scrap).</li>
                        <li>• <strong>Material Yield:</strong> Efisiensi penggunaan board.</li>
                    </ul>
                </div>
            </div>
        `
    },
    database: {
        title: 'Database Structure',
        subtitle: 'Entity Relationship Diagram (ERD)',
        isMermaid: true,
        body: `
            erDiagram
                COMPANIES ||--o{ PRODUCTS : owns
                COMPANIES ||--o{ CUSTOMERS : manages
                
                CUSTOMERS ||--o{ SALES_ORDERS : places
                SALES_ORDERS {
                    bigint id PK
                    string number
                    date date
                    string status
                }
                
                SALES_ORDERS ||--o{ SALES_ORDER_ITEMS : contains
                SALES_ORDER_ITEMS {
                    bigint id PK
                    bigint product_id FK
                    decimal quantity
                    decimal price
                }
                
                PRODUCTS ||--o{ SALES_ORDER_ITEMS : sold_as
                PRODUCTS ||--o{ PRODUCT_STOCKS : stored_in
                
                WAREHOUSES ||--o{ PRODUCT_STOCKS : holds
                PRODUCT_STOCKS {
                    bigint id PK
                    decimal quantity
                    string location_bin
                }
                
                WORK_ORDERS ||--o{ PRODUCTION_ENTRIES : generates
                WORK_ORDERS {
                    string number
                    string status
                    datetime start_time
                }
                
                PROJECTS ||--o{ PROJECT_TASKS : tracks
        `
    },
    security: {
        title: 'Security Matrix',
        subtitle: 'Role-Based Access Control (RBAC)',
        body: `
            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left text-gray-400">
                    <thead class="text-xs text-gray-200 uppercase bg-gray-700">
                        <tr>
                            <th class="px-4 py-2">Module</th>
                            <th class="px-4 py-2">Operator</th>
                            <th class="px-4 py-2">Finance</th>
                            <th class="px-4 py-2">Manager</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="bg-gray-800 border-b border-gray-700">
                            <td class="px-4 py-2 font-medium text-white">Production Entry</td>
                            <td class="px-4 py-2 text-green-400">Create/Edit</td>
                            <td class="px-4 py-2 text-red-400">View Only</td>
                            <td class="px-4 py-2 text-blue-400">View Only</td>
                        </tr>
                        <tr class="bg-gray-800 border-b border-gray-700">
                            <td class="px-4 py-2 font-medium text-white">Financial Rpt</td>
                            <td class="px-4 py-2 text-red-400">No Access</td>
                            <td class="px-4 py-2 text-green-400">Full Access</td>
                            <td class="px-4 py-2 text-blue-400">View Only</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        `
    },
    integration: {
        title: 'Integrations',
        subtitle: 'Internal Data Flow & WhatsApp Bot',
        body: `
            <div class="grid grid-cols-1 gap-4">
                <div class="bg-gray-800 p-4 rounded-lg flex items-center gap-4">
                    <div class="p-3 bg-green-900 rounded-full text-green-400">
                        <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
                        (WA)
                    </div>
                    <div>
                        <h4 class="font-bold text-white">WhatsApp Bot Alerts</h4>
                        <p class="text-xs text-gray-400">Notifikasi instan untuk Breakdown Mesin & Low Stock.</p>
                    </div>
                </div>
            </div>
        `
    },
    qc: {
        title: 'Quality Control',
        subtitle: 'Operator Self-Check Strategy',
        body: `
            <div class="space-y-4">
                <div class="bg-indigo-900/30 p-4 rounded-lg border-l-4 border-indigo-500">
                    <h4 class="font-bold text-white">Lean QC Strategy</h4>
                    <p class="text-sm text-gray-300 mt-1">
                        Sistem "Gatekeeper" memastikan operator melakukan <strong>Self-Check</strong> sebelum lanjut produksi.
                        Wajib upload foto <em>First Article</em>.
                    </p>
                </div>
                <div class="grid grid-cols-3 gap-2 text-center text-xs">
                    <div class="bg-gray-800 p-2 rounded">Incoming (IQC)</div>
                    <div class="bg-gray-800 p-2 rounded">In-Process (IPQC)</div>
                    <div class="bg-gray-800 p-2 rounded">Outgoing (OQA)</div>
                </div>
            </div>
        `
    },
    uat: {
        title: 'UAT Scenarios',
        subtitle: 'End-to-End Validation Plan',
        body: `
            <ul class="space-y-2 text-sm text-gray-300">
                <li class="flex items-center gap-2">
                    <span class="w-4 h-4 bg-green-500 rounded-full flex items-center justify-center text-[10px] text-black font-bold">1</span>
                    Order to Cash (Sales -> Invoice)
                </li>
                <li class="flex items-center gap-2">
                    <span class="w-4 h-4 bg-blue-500 rounded-full flex items-center justify-center text-[10px] text-black font-bold">2</span>
                    Production & Lean QC (SPK -> FG)
                </li>
                <li class="flex items-center gap-2">
                    <span class="w-4 h-4 bg-yellow-500 rounded-full flex items-center justify-center text-[10px] text-black font-bold">3</span>
                    Traceability & NCR (Defect Handling)
                </li>
            </ul>
        `
    }
};

const currentContent = computed(() => content[activeTab.value]);

onMounted(() => {
    mermaid.initialize({ 
        startOnLoad: false, 
        theme: 'dark',
        securityLevel: 'loose',
    });
    renderMermaid();
});

const renderMermaid = async () => {
    // Wait for the next tick to ensure DOM is updated
    await nextTick();
    
    // Slight delay to ensure transition stability if needed
    // setTimeout is not ideal but sometimes helps with mermaid's measurement logic in hidden containers
    
    const element = document.querySelector('.mermaid-graph');
    
    // Only proceed if element exists and content is mermaid type
    if (element && currentContent.value.isMermaid) {
        // Reset content to loading state if re-rendering (optional, but good for UX)
        // element.innerHTML = 'Loading Visualizer...'; 
        
        try {
            // Generate unique ID for each render to prevent caching issues
            const graphId = 'mermaid-svg-' + activeTab.value + '-' + Date.now();
            const { svg } = await mermaid.render(graphId, currentContent.value.body);
            element.innerHTML = svg;
        } catch (e) {
            console.error('Mermaid render error:', e);
            element.innerHTML = `<div class="text-red-500 font-mono text-sm p-4 bg-red-900/20 rounded">
                <p class="font-bold">Diagram Render Error:</p>
                <p>${e.message}</p>
            </div>`;
        }
    }
};

// No need to watch activeTab separately if we use @after-enter, 
// BUT @after-enter only fires on transitions. 
// Initial load needs onMounted.
</script>

<template>
    <Head title="Project Blueprint" />

    <AppLayout title="Project Blueprint">
        <div class="flex h-[calc(100vh-4rem)] bg-[#050510] overflow-hidden">
            <!-- Sidebar -->
            <aside class="w-72 bg-[#0a0a1a] border-r border-slate-800 flex flex-col z-20">
                <div class="p-6 border-b border-slate-800">
                    <h2 class="text-xl font-black text-white tracking-tight">BLUEPRINT<span class="text-cyan-500">.HUB</span></h2>
                    <p class="text-xs text-slate-500 mt-1">Strategic Documentation JICOS</p>
                </div>
                <nav class="flex-1 overflow-y-auto p-4 space-y-1">
                    <button 
                        v-for="item in menuItems" 
                        :key="item.id"
                        @click="activeTab = item.id"
                        :class="[
                            'w-full flex items-center gap-3 px-4 py-3 text-sm font-medium rounded-lg transition-all duration-200',
                            activeTab === item.id 
                                ? 'bg-cyan-500/10 text-cyan-400 border border-cyan-500/20 shadow-[0_0_15px_rgba(6,182,212,0.15)]' 
                                : 'text-slate-400 hover:text-white hover:bg-white/5'
                        ]"
                    >
                        <component :is="item.icon" class="w-5 h-5" />
                        {{ item.label }}
                    </button>
                </nav>
            </aside>

            <!-- Main Content -->
            <main class="flex-1 overflow-y-auto relative perspective-1000">
                <div class="max-w-6xl mx-auto p-12">
                    <Transition 
                        enter-active-class="transition-all duration-500 ease-out"
                        enter-from-class="opacity-0 translate-y-10 scale-95"
                        enter-to-class="opacity-100 translate-y-0 scale-100"
                        leave-active-class="transition-all duration-300 ease-in absolute top-12 left-12 right-12"
                        leave-from-class="opacity-100 translate-y-0"
                        leave-to-class="opacity-0 -translate-y-10"
                        mode="out-in"
                        @after-enter="renderMermaid"
                    >
                        <div :key="activeTab" class="bg-[#0f0f25] border border-slate-800 rounded-2xl p-8 shadow-2xl relative overflow-hidden group min-h-[600px]">
                            <!-- Background Decor -->
                            <div class="absolute top-0 right-0 -mr-20 -mt-20 w-64 h-64 bg-cyan-500/10 rounded-full blur-3xl pointer-events-none group-hover:bg-cyan-500/20 transition-all duration-1000"></div>

                            <div class="relative z-10 h-full flex flex-col">
                                <h1 class="text-4xl font-black text-white mb-2 tracking-tight">{{ currentContent.title }}</h1>
                                <p class="text-lg text-cyan-400 mb-8 font-light border-b border-white/5 pb-4">{{ currentContent.subtitle }}</p>
                                
                                <div class="flex-1 overflow-auto">
                                    <div v-if="currentContent.isMermaid" class="mermaid-container pb-12">
                                        <div class="mermaid-graph flex justify-center p-4 bg-slate-900 rounded-lg min-h-[300px]">
                                            <!-- Mermaid Diagram Rendered Here -->
                                            Loading Visualizer...
                                        </div>
                                        <!-- Render additional details if available (for Flowchart side notes) -->
                                        <div v-if="currentContent.details" v-html="currentContent.details" class="animate-fade-in-up"></div>
                                    </div>
                                    <div v-else class="prose prose-invert max-w-none prose-headings:font-bold prose-p:text-slate-300 prose-li:text-slate-300" v-html="currentContent.body"></div>
                                </div>
                            </div>
                        </div>
                    </Transition>
                </div>
            </main>
        </div>
    </AppLayout>
</template>

<style>
/* Mermaid custom styling override */
.mermaid-graph svg {
    max-width: 100%;
    height: auto;
}
</style>

<style scoped>
.perspective-1000 {
    perspective: 1000px;
}
/* Custom Scrollbar */
::-webkit-scrollbar {
    width: 6px;
}
::-webkit-scrollbar-track {
    background: #0a0a1a; 
}
::-webkit-scrollbar-thumb {
    background: #1e293b; 
    border-radius: 3px;
}
::-webkit-scrollbar-thumb:hover {
    background: #334155; 
}
</style>
