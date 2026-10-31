require('dotenv').config();
const express = require('express');
const cors = require('cors');
const qrcode = require('qrcode');
const pino = require('pino');
const axios = require('axios');
const path = require('path');
const fs = require('fs');
const {
    default: makeWASocket,
    useMultiFileAuthState,
    DisconnectReason,
    fetchLatestBaileysVersion
} = require('@whiskeysockets/baileys');

// Prevent crashes on transient socket disconnects or unhandled Baileys promises
process.on('uncaughtException', (err) => {
    console.error('[USICS WA Engine] Uncaught Exception caught (prevented crash):', err?.message || err);
});

process.on('unhandledRejection', (reason) => {
    console.error('[USICS WA Engine] Unhandled Rejection caught (prevented crash):', reason?.message || reason);
});

const app = express();
app.use(cors());
app.use(express.json({ limit: '50mb' }));
app.use(express.urlencoded({ extended: true, limit: '50mb' }));

const PORT = process.env.PORT || 5050;
const API_KEY = process.env.API_KEY || 'usics-wa-secret-2026';
const WEBHOOK_SALES_URL = process.env.WEBHOOK_URL || 'http://usics.test/whatsapp/webhook';
const WEBHOOK_PURCHASING_URL = process.env.WEBHOOK_PURCHASING_URL || 'http://usics.test/whatsapp-purchasing/webhook';
const WEBHOOK_INVENTORY_URL = process.env.WEBHOOK_INVENTORY_URL || 'http://usics.test/whatsapp-inventory/webhook';
const BASE_AUTH_DIR = path.join(__dirname, 'auth_info_baileys');
const LID_CACHE_FILE = path.join(__dirname, 'lid_cache.json');

// Persistent LID (Linked Identity ID) to Phone Number cache
let lidCache = { '154283848777827': '6285888883258' };
if (fs.existsSync(LID_CACHE_FILE)) {
    try {
        lidCache = Object.assign(lidCache, JSON.parse(fs.readFileSync(LID_CACHE_FILE, 'utf8')));
    } catch (e) {}
}

function saveLidCache() {
    try {
        fs.writeFileSync(LID_CACHE_FILE, JSON.stringify(lidCache, null, 2));
    } catch (e) {}
}

if (!fs.existsSync(BASE_AUTH_DIR)) {
    fs.mkdirSync(BASE_AUTH_DIR, { recursive: true });
}

// In-memory Cache Store for Baileys msgRetryCounterCache
function createMemoryCache() {
    const map = new Map();
    return {
        get: (key) => map.get(key),
        set: (key, val) => map.set(key, val),
        del: (key) => map.delete(key),
        flushAll: () => map.clear()
    };
}

// In-memory sent messages cache for answering retry requests (fixes "Waiting for this message")
const sentMessagesCache = new Map();
function storeSentMessage(id, message) {
    if (!id) return;
    sentMessagesCache.set(id, message);
    if (sentMessagesCache.size > 1000) {
        const firstKey = sentMessagesCache.keys().next().value;
        sentMessagesCache.delete(firstKey);
    }
}

const logger = pino({ level: 'silent' });

// Multi-Session Storage
const SESSION_DEFS = {
    sales: { label: 'Sales & Customer Service', color: 'emerald' },
    purchasing: { label: 'Purchasing & Supplier Center', color: 'purple' },
    inventory: { label: 'Inventory & Warehouse Center', color: 'blue' }
};

const sessions = {
    sales: {
        id: 'sales',
        label: 'Sales & Customer Service',
        sock: null,
        qrCodeDataUrl: null,
        rawQrCode: null,
        status: 'disconnected', // 'disconnected' | 'connecting' | 'connected' | 'qr_ready'
        connectedUser: null,
        lastError: null
    },
    purchasing: {
        id: 'purchasing',
        label: 'Purchasing & Supplier Center',
        sock: null,
        qrCodeDataUrl: null,
        rawQrCode: null,
        status: 'disconnected',
        connectedUser: null,
        lastError: null
    },
    inventory: {
        id: 'inventory',
        label: 'Inventory & Warehouse Center',
        sock: null,
        qrCodeDataUrl: null,
        rawQrCode: null,
        status: 'disconnected',
        connectedUser: null,
        lastError: null
    }
};

/**
 * Format phone or Group ID to WhatsApp JID (e.g. 628123456789@s.whatsapp.net or 120363...@g.us)
 */
function formatJid(phone) {
    if (!phone) return null;
    const raw = String(phone).trim();
    if (raw.endsWith('@g.us') || raw.endsWith('@s.whatsapp.net')) {
        return raw;
    }
    let clean = raw.replace(/[^0-9]/g, '');
    // Resolve LID to real phone if mapped
    if (lidCache && lidCache[clean]) {
        clean = lidCache[clean];
    }
    if (clean.startsWith('0')) {
        clean = '62' + clean.slice(1);
    } else if (clean.startsWith('+62')) {
        clean = clean.replace('+', '');
    } else if (!clean.startsWith('62')) {
        clean = '62' + clean;
    }
    return `${clean}@s.whatsapp.net`;
}

/**
 * Robust text extractor for incoming messages
 */
function extractMessageText(msg) {
    if (!msg || !msg.message) return '';
    let message = msg.message;

    // Unwrap ephemeral, view-once, document-with-caption, or edited messages
    if (message.ephemeralMessage?.message) message = message.ephemeralMessage.message;
    if (message.viewOnceMessage?.message) message = message.viewOnceMessage.message;
    if (message.viewOnceMessageV2?.message) message = message.viewOnceMessageV2.message;
    if (message.documentWithCaptionMessage?.message) message = message.documentWithCaptionMessage.message;
    if (message.editedMessage?.message?.protocolMessage?.editedMessage) {
        message = message.editedMessage.message.protocolMessage.editedMessage;
    }

    let text = message.conversation ||
        message.extendedTextMessage?.text ||
        message.imageMessage?.caption ||
        message.videoMessage?.caption ||
        message.documentMessage?.caption ||
        message.buttonsResponseMessage?.selectedButtonId ||
        message.listResponseMessage?.singleSelectReply?.selectedRowId ||
        '';

    if (!text) {
        if (message.imageMessage) text = '[Gambar]';
        else if (message.documentMessage) text = `[Dokumen: ${message.documentMessage.fileName || 'file'}]`;
        else if (message.audioMessage) text = '[Pesan Suara]';
        else if (message.videoMessage) text = '[Video]';
        else if (message.stickerMessage) text = '[Stiker]';
        else if (message.locationMessage) text = '[Lokasi]';
        else if (message.contactMessage) text = '[Kontak]';
    }

    return text;
}

/**
 * Initialize / Connect a Baileys session
 */
async function startSession(sessionId) {
    const sess = sessions[sessionId];
    if (!sess) return;

    sess.status = 'connecting';
    sess.lastError = null;

    const sessionDir = path.join(BASE_AUTH_DIR, sessionId);
    if (!fs.existsSync(sessionDir)) {
        fs.mkdirSync(sessionDir, { recursive: true });
    }

    try {
        const { state, saveCreds } = await useMultiFileAuthState(sessionDir);
        const { version, isLatest } = await fetchLatestBaileysVersion();

        console.log(`[USICS WA Engine] [${sessionId.toUpperCase()}] Starting with Baileys v${version.join('.')}, latest: ${isLatest}`);

        const msgRetryCounterCache = createMemoryCache();

        const sock = makeWASocket({
            version,
            logger,
            printQRInTerminal: false,
            auth: state,
            msgRetryCounterCache,
            getMessage: async (key) => {
                if (key.id && sentMessagesCache.has(key.id)) {
                    const cached = sentMessagesCache.get(key.id);
                    if (typeof cached === 'string') {
                        return { conversation: cached };
                    }
                    return cached;
                }
                return {
                    conversation: 'Pesan dari USICS ERP'
                };
            },
            browser: [`USICS ERP - ${sess.label}`, 'Chrome', '124.0.0.0'],
            connectTimeoutMs: 60000,
            defaultQueryTimeoutMs: 60000,
            syncFullHistory: false
        });

        sess.sock = sock;

        sock.ev.on('creds.update', saveCreds);

        // Auto-learn LID to Phone mapping from contacts
        sock.ev.on('contacts.upsert', (contacts) => {
            for (const c of contacts) {
                if (c.id && c.lid) {
                    const phone = c.id.split('@')[0].split(':')[0];
                    const lid = c.lid.split('@')[0].split(':')[0];
                    if (phone && lid) {
                        lidCache[lid] = phone;
                        saveLidCache();
                    }
                }
            }
        });

        sock.ev.on('connection.update', async (update) => {
            const { connection, lastDisconnect, qr } = update;

            if (qr) {
                sess.rawQrCode = qr;
                sess.status = 'qr_ready';
                try {
                    sess.qrCodeDataUrl = await qrcode.toDataURL(qr, { scale: 8, margin: 2 });
                    console.log(`[USICS WA Engine] [${sessionId.toUpperCase()}] QR Code ready. Scan at http://localhost:${PORT}/qr?session=${sessionId}`);
                } catch (e) {
                    console.error(`[USICS WA Engine] [${sessionId.toUpperCase()}] QR generation error:`, e);
                }
            }

            if (connection === 'close') {
                const statusCode = lastDisconnect?.error?.output?.statusCode;
                const shouldReconnect = statusCode !== DisconnectReason.loggedOut;
                sess.lastError = lastDisconnect?.error?.message || 'Connection closed';

                console.log(`[USICS WA Engine] [${sessionId.toUpperCase()}] Closed due to:`, lastDisconnect?.error?.message, `, reconnect: ${shouldReconnect}`);

                if (statusCode === DisconnectReason.loggedOut) {
                    sess.status = 'disconnected';
                    sess.connectedUser = null;
                    sess.qrCodeDataUrl = null;
                    sess.rawQrCode = null;
                    sess.sock = null;
                    try {
                        if (fs.existsSync(sessionDir)) {
                            fs.rmSync(sessionDir, { recursive: true, force: true });
                        }
                    } catch (err) {
                        console.error(`Failed to clear session dir [${sessionId}]:`, err);
                    }
                    setTimeout(() => startSession(sessionId), 3000);
                } else if (shouldReconnect) {
                    sess.status = 'connecting';
                    setTimeout(() => startSession(sessionId), 5000);
                } else {
                    sess.status = 'disconnected';
                }
            } else if (connection === 'open') {
                sess.status = 'connected';
                sess.qrCodeDataUrl = null;
                sess.rawQrCode = null;
                sess.connectedUser = {
                    id: sock.user?.id || 'Connected',
                    name: sock.user?.name || `USICS ${sess.label}`,
                    phone: sock.user?.id ? sock.user.id.split(':')[0] : 'Unknown'
                };
                console.log(`[USICS WA Engine] [${sessionId.toUpperCase()}] ✅ Connected as: +${sess.connectedUser.phone} (${sess.connectedUser.name})`);
            }
        });

        // Inbound message forwarder
        sock.ev.on('messages.upsert', async (m) => {
            if (m.type !== 'notify' && m.type !== 'append') return;

            for (const msg of m.messages) {
                if (msg.key.fromMe) continue;

                const senderJid = msg.key.remoteJid;
                if (!senderJid || senderJid.endsWith('@g.us')) continue;

                // Clean phone number (strip @s.whatsapp.net, :0, or @lid)
                let cleanPhone = senderJid.split('@')[0].split(':')[0];
                if (senderJid.endsWith('@lid') && msg.key.participant) {
                    cleanPhone = msg.key.participant.split('@')[0].split(':')[0];
                }

                // Resolve WhatsApp LID (Privacy ID) to real phone number
                if (senderJid.endsWith('@lid') || cleanPhone.length >= 14) {
                    if (lidCache[cleanPhone]) {
                        console.log(`[USICS WA Engine] [${sessionId.toUpperCase()}] 🔄 Resolved LID ${cleanPhone} -> +${lidCache[cleanPhone]}`);
                        cleanPhone = lidCache[cleanPhone];
                    }
                }

                const pushName = msg.pushName || '';

                const body = extractMessageText(msg);

                console.log(`[USICS WA Engine] [${sessionId.toUpperCase()}] 📩 Inbound from +${cleanPhone} (${pushName || 'No Name'}): "${body}"`);

                if (!body) {
                    console.log(`[USICS WA Engine] [${sessionId.toUpperCase()}] ⚠️ Skipped empty or unsupported message type.`);
                    continue;
                }

                // Route to appropriate webhook
                const targetWebhook = sessionId === 'purchasing'
                    ? WEBHOOK_PURCHASING_URL
                    : (sessionId === 'inventory' ? WEBHOOK_INVENTORY_URL : WEBHOOK_SALES_URL);

                if (targetWebhook) {
                    try {
                        const webhookRes = await axios.post(targetWebhook, {
                            session: sessionId,
                            sender: cleanPhone,
                            phone: cleanPhone,
                            message: body,
                            pushName: pushName,
                            timestamp: msg.messageTimestamp,
                            source: 'usics_engine'
                        }, {
                            headers: {
                                'Content-Type': 'application/json',
                                'X-Engine-Secret': API_KEY
                            },
                            timeout: 10000
                        });
                        console.log(`[USICS WA Engine] [${sessionId.toUpperCase()}] ✅ Webhook delivered to ${targetWebhook} (Status: ${webhookRes.status})`);
                    } catch (err) {
                        console.error(`[USICS WA Engine] [${sessionId.toUpperCase()}] ❌ Webhook delivery failed to ${targetWebhook}: ${err.message}`);
                    }
                }
            }
        });

    } catch (error) {
        console.error(`[USICS WA Engine] [${sessionId.toUpperCase()}] Init error:`, error);
        sess.status = 'disconnected';
        sess.lastError = error.message;
        setTimeout(() => startSession(sessionId), 7000);
    }
}

/**
 * Authentication Middleware for API
 */
function authMiddleware(req, res, next) {
    const authHeader = req.headers['authorization'] || '';
    const tokenHeader = req.headers['x-api-key'] || '';
    const providedKey = authHeader.replace(/^Bearer\s+/i, '') || tokenHeader;

    if (!API_KEY || providedKey === API_KEY) {
        return next();
    }

    return res.status(401).json({
        success: false,
        error: 'Unauthorized: Invalid or missing API Key.'
    });
}

// -------------------------------------------------------------
// ROUTES
// -------------------------------------------------------------

app.get('/', (req, res) => {
    res.json({
        name: 'USICS WhatsApp Engine',
        port: PORT,
        multiSession: true,
        sessions: {
            sales: {
                status: sessions.sales.status,
                phone: sessions.sales.connectedUser?.phone || null,
                name: sessions.sales.connectedUser?.name || null
            },
            purchasing: {
                status: sessions.purchasing.status,
                phone: sessions.purchasing.connectedUser?.phone || null,
                name: sessions.purchasing.connectedUser?.name || null
            },
            inventory: {
                status: sessions.inventory.status,
                phone: sessions.inventory.connectedUser?.phone || null,
                name: sessions.inventory.connectedUser?.name || null
            }
        },
        uptime: Math.floor(process.uptime()),
        timestamp: new Date().toISOString()
    });
});

app.get('/status', (req, res) => {
    const requestedSession = req.query.session || 'sales';
    const sess = sessions[requestedSession] || sessions.sales;

    res.json({
        success: true,
        session: sess.id,
        status: sess.status,
        connected: sess.status === 'connected',
        phone: sess.connectedUser?.phone || null,
        name: sess.connectedUser?.name || null,
        lastError: sess.lastError,
        qrAvailable: Boolean(sess.qrCodeDataUrl),
        allSessions: {
            sales: { status: sessions.sales.status, connected: sessions.sales.status === 'connected', phone: sessions.sales.connectedUser?.phone || null },
            purchasing: { status: sessions.purchasing.status, connected: sessions.purchasing.status === 'connected', phone: sessions.purchasing.connectedUser?.phone || null },
            inventory: { status: sessions.inventory.status, connected: sessions.inventory.status === 'connected', phone: sessions.inventory.connectedUser?.phone || null }
        }
    });
});

/**
 * Multi-Session QR Dashboard
 */
app.get('/qr', (req, res) => {
    const initialSession = ['purchasing', 'inventory'].includes(req.query.session) ? req.query.session : 'sales';

    const html = `<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>USICS WhatsApp Engine - Multi-Device Pairing</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .mono { font-family: 'JetBrains Mono', monospace; }
    </style>
</head>
<body class="bg-slate-950 text-slate-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-slate-900 border border-slate-800 rounded-2xl p-6 sm:p-8 shadow-2xl space-y-6">
        
        <!-- Header -->
        <div class="flex items-center justify-between border-b border-slate-800 pb-4">
            <div>
                <span class="text-[10px] font-bold tracking-widest text-emerald-400 uppercase mono">PORT ${PORT} • MULTI-SESSION</span>
                <h1 class="text-lg font-bold text-white tracking-tight">USICS WhatsApp Engine</h1>
            </div>
            <div id="statusBadge" class="px-2.5 py-1 rounded-full text-xs font-semibold mono flex items-center gap-1.5 bg-slate-800 text-slate-400">
                <span class="w-2 h-2 rounded-full bg-slate-500 animate-pulse"></span>
                <span>Checking...</span>
            </div>
        </div>

        <!-- Session Selector Tabs -->
        <div class="grid grid-cols-3 gap-1.5 p-1 bg-slate-950 rounded-xl border border-slate-800">
            <button 
                id="tabSales"
                onclick="switchSession('sales')"
                class="py-2.5 px-2 rounded-lg text-[11px] font-bold transition-all flex items-center justify-center gap-1.5 bg-emerald-600 text-white shadow-sm"
            >
                <span>📱 Sales</span>
                <span id="dotSales" class="w-2 h-2 rounded-full bg-slate-400"></span>
            </button>
            <button 
                id="tabPurchasing"
                onclick="switchSession('purchasing')"
                class="py-2.5 px-2 rounded-lg text-[11px] font-bold transition-all flex items-center justify-center gap-1.5 text-slate-400 hover:text-white"
            >
                <span>🛒 Purchasing</span>
                <span id="dotPurchasing" class="w-2 h-2 rounded-full bg-slate-400"></span>
            </button>
            <button 
                id="tabInventory"
                onclick="switchSession('inventory')"
                class="py-2.5 px-2 rounded-lg text-[11px] font-bold transition-all flex items-center justify-center gap-1.5 text-slate-400 hover:text-white"
            >
                <span>📦 Inventory</span>
                <span id="dotInventory" class="w-2 h-2 rounded-full bg-slate-400"></span>
            </button>
        </div>

        <!-- QR or Connected View -->
        <div id="contentArea" class="flex flex-col items-center justify-center p-6 bg-slate-950 rounded-xl border border-slate-800/80 min-h-[300px]">
            <div class="animate-spin rounded-full h-10 w-10 border-2 border-emerald-500 border-t-transparent"></div>
            <p class="text-xs text-slate-400 mt-4 mono">Memeriksa koneksi sesi...</p>
        </div>

        <!-- Instructions -->
        <div class="space-y-2 text-xs text-slate-400 bg-slate-800/40 p-4 rounded-xl border border-slate-800">
            <p class="font-semibold text-slate-200" id="instructionTitle">Cara Menghubungkan Nomor Sales:</p>
            <ol class="list-decimal list-inside space-y-1 text-[11px] text-slate-400">
                <li>Buka WhatsApp di HP kantor Anda.</li>
                <li>Ketuk menu <b class="text-slate-200">Perangkat Tertaut</b> (Linked Devices).</li>
                <li>Ketuk <b class="text-slate-200">Tautkan Perangkat</b> lalu scan QR code di atas.</li>
            </ol>
        </div>

        <!-- Action Buttons -->
        <div class="flex gap-2">
            <button onclick="location.reload()" class="flex-1 py-2.5 px-4 bg-slate-800 hover:bg-slate-700 text-xs font-semibold rounded-lg transition-colors border border-slate-700 text-slate-300">
                🔄 Segarkan Tampilan
            </button>
            <button id="logoutBtn" onclick="logoutSession()" class="py-2.5 px-4 bg-rose-950/60 hover:bg-rose-900 border border-rose-800/50 text-rose-300 text-xs font-semibold rounded-lg transition-colors hidden">
                Putuskan Sesi
            </button>
        </div>

        <p class="text-center text-[10px] text-slate-500 mono">PT United Steel Center Indonesia (USC ERP)</p>
    </div>

    <script>
        let currentSession = '${initialSession}';

        function switchSession(sessionId) {
            currentSession = sessionId;
            const tabSales = document.getElementById('tabSales');
            const tabPurchasing = document.getElementById('tabPurchasing');
            const tabInventory = document.getElementById('tabInventory');
            const instTitle = document.getElementById('instructionTitle');

            const baseClass = 'py-2.5 px-2 rounded-lg text-[11px] font-bold transition-all flex items-center justify-center gap-1.5 text-slate-400 hover:text-white';
            tabSales.className = baseClass;
            tabPurchasing.className = baseClass;
            tabInventory.className = baseClass;

            if (sessionId === 'sales') {
                tabSales.className = 'py-2.5 px-2 rounded-lg text-[11px] font-bold transition-all flex items-center justify-center gap-1.5 bg-emerald-600 text-white shadow-sm';
                instTitle.innerText = 'Cara Menghubungkan Nomor Sales & CS:';
            } else if (sessionId === 'purchasing') {
                tabPurchasing.className = 'py-2.5 px-2 rounded-lg text-[11px] font-bold transition-all flex items-center justify-center gap-1.5 bg-purple-600 text-white shadow-sm';
                instTitle.innerText = 'Cara Menghubungkan Nomor Purchasing / Vendor:';
            } else {
                tabInventory.className = 'py-2.5 px-2 rounded-lg text-[11px] font-bold transition-all flex items-center justify-center gap-1.5 bg-blue-600 text-white shadow-sm';
                instTitle.innerText = 'Cara Menghubungkan Nomor Inventory / Gudang:';
            }

            checkStatus();
        }

        async function checkStatus() {
            try {
                const res = await fetch('/status?session=' + currentSession);
                const data = await res.json();
                const badge = document.getElementById('statusBadge');
                const content = document.getElementById('contentArea');
                const logoutBtn = document.getElementById('logoutBtn');

                // Update tab indicator dots
                const dotSales = document.getElementById('dotSales');
                const dotPurchasing = document.getElementById('dotPurchasing');
                const dotInventory = document.getElementById('dotInventory');
                if (data.allSessions) {
                    dotSales.className = 'w-2 h-2 rounded-full ' + (data.allSessions.sales?.connected ? 'bg-emerald-400' : 'bg-slate-500');
                    dotPurchasing.className = 'w-2 h-2 rounded-full ' + (data.allSessions.purchasing?.connected ? 'bg-purple-400' : 'bg-slate-500');
                    dotInventory.className = 'w-2 h-2 rounded-full ' + (data.allSessions.inventory?.connected ? 'bg-blue-400' : 'bg-slate-500');
                }

                if (data.status === 'connected') {
                    const activeColor = currentSession === 'sales' ? 'emerald' : (currentSession === 'purchasing' ? 'purple' : 'blue');
                    const sessionLabel = currentSession === 'sales' ? 'Nomor Sales' : (currentSession === 'purchasing' ? 'Nomor Purchasing' : 'Nomor Inventory');
                    badge.className = 'px-2.5 py-1 rounded-full text-xs font-semibold mono flex items-center gap-1.5 bg-' + activeColor + '-950/80 text-' + activeColor + '-400 border border-' + activeColor + '-800/50';
                    badge.innerHTML = '<span class="w-2 h-2 rounded-full bg-' + activeColor + '-400"></span>ONLINE';
                    
                    content.innerHTML = \`
                        <div class="text-center space-y-3">
                            <div class="w-16 h-16 bg-\${activeColor}-950/60 border border-\${activeColor}-500/30 text-\${activeColor}-400 rounded-full flex items-center justify-center mx-auto text-2xl font-bold">
                                ✓
                            </div>
                            <div>
                                <h3 class="text-base font-bold text-white">\${sessionLabel} Terhubung</h3>
                                <p class="text-xs text-slate-400 mono mt-1">+\${data.phone || 'Terdaftar'}</p>
                                <p class="text-[11px] text-\${activeColor}-400/80 mt-0.5">\${data.name || 'USICS Device'}</p>
                            </div>
                            <p class="text-[11px] text-slate-400 max-w-xs">Gateway siap menerima dan mengirim pesan untuk modul \${currentSession.toUpperCase()}.</p>
                        </div>
                    \`;
                    logoutBtn.classList.remove('hidden');
                } else if (data.status === 'qr_ready') {
                    badge.className = 'px-2.5 py-1 rounded-full text-xs font-semibold mono flex items-center gap-1.5 bg-amber-950/80 text-amber-400 border border-amber-800/50';
                    badge.innerHTML = '<span class="w-2 h-2 rounded-full bg-amber-400 animate-ping"></span>SCAN QR';
                    
                    content.innerHTML = \`
                        <div class="text-center space-y-3">
                            <div class="p-2 bg-white rounded-xl shadow-lg inline-block">
                                <img src="/qr.png?session=\${currentSession}&t=\${Date.now()}" alt="QR Code" class="w-56 h-56 rounded-lg" />
                            </div>
                            <p class="text-[11px] text-amber-300 mono animate-pulse">Menunggu scan untuk nomor \${currentSession.toUpperCase()}...</p>
                        </div>
                    \`;
                    logoutBtn.classList.add('hidden');
                } else {
                    badge.className = 'px-2.5 py-1 rounded-full text-xs font-semibold mono flex items-center gap-1.5 bg-slate-800 text-slate-400';
                    badge.innerHTML = '<span class="w-2 h-2 rounded-full bg-slate-500 animate-pulse"></span>' + data.status.toUpperCase();
                    
                    content.innerHTML = \`
                        <div class="text-center space-y-2">
                            <div class="animate-spin rounded-full h-8 w-8 border-2 border-emerald-500 border-t-transparent mx-auto"></div>
                            <p class="text-xs text-slate-300 mono">Memulai koneksi sesi \${currentSession}...</p>
                            <p class="text-[11px] text-slate-500">\${data.lastError || 'Silakan tunggu sebentar'}</p>
                        </div>
                    \`;
                    logoutBtn.classList.add('hidden');
                }
            } catch (err) {
                console.error(err);
            }
        }

        async function logoutSession() {
            if (!confirm('Putuskan koneksi nomor ' + currentSession.toUpperCase() + '?')) return;
            try {
                await fetch('/api/logout', { 
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ session: currentSession })
                });
                location.reload();
            } catch (e) {
                alert('Gagal logout: ' + e.message);
            }
        }

        // Apply initial tab
        switchSession('${initialSession}');
        setInterval(checkStatus, 3000);
    </script>
</body>
</html>`;
    res.setHeader('Content-Type', 'text/html');
    res.send(html);
});

/**
 * Direct QR image endpoint per session
 */
app.get('/qr.png', async (req, res) => {
    const requestedSession = req.query.session || 'sales';
    const sess = sessions[requestedSession] || sessions.sales;

    if (!sess.rawQrCode) {
        return res.status(404).send('QR Code is not ready or already connected.');
    }
    try {
        const buffer = await qrcode.toBuffer(sess.rawQrCode, { scale: 8, margin: 2 });
        res.setHeader('Content-Type', 'image/png');
        res.send(buffer);
    } catch (e) {
        res.status(500).send('Error generating QR image');
    }
});

/**
 * Logout & Disconnect a specific session
 */
app.post('/api/logout', async (req, res) => {
    const requestedSession = req.body.session || 'sales';
    const sess = sessions[requestedSession];

    if (!sess) {
        return res.status(400).json({ success: false, error: 'Invalid session' });
    }

    try {
        if (sess.sock) {
            await sess.sock.logout().catch(() => {});
        }
        const sessionDir = path.join(BASE_AUTH_DIR, requestedSession);
        if (fs.existsSync(sessionDir)) {
            fs.rmSync(sessionDir, { recursive: true, force: true });
        }
        sess.status = 'disconnected';
        sess.connectedUser = null;
        sess.qrCodeDataUrl = null;
        sess.rawQrCode = null;
        sess.sock = null;
        setTimeout(() => startSession(requestedSession), 2000);
        res.json({ success: true, message: `Session [${requestedSession}] logged out successfully` });
    } catch (err) {
        res.status(500).json({ success: false, error: err.message });
    }
});

function resolveSendSession(requestedSession) {
    const primary = sessions[requestedSession] || sessions.sales;
    if (primary.status === 'connected' && primary.sock) {
        return primary;
    }
    // Fallback to any connected session during single-number trial
    for (const key of ['purchasing', 'sales', 'inventory']) {
        if (sessions[key] && sessions[key].status === 'connected' && sessions[key].sock) {
            return sessions[key];
        }
    }
    return primary;
}

/**
 * Send Text Message
 */
app.post('/api/send-message', authMiddleware, async (req, res) => {
    const { phone, message, session = 'sales' } = req.body;
    const sess = resolveSendSession(session);

    if (!phone || !message) {
        return res.status(400).json({ success: false, error: 'Phone and message fields are required' });
    }

    if (sess.status !== 'connected' || !sess.sock) {
        return res.status(503).json({
            success: false,
            error: `WhatsApp Engine [${session.toUpperCase()}] is not connected. Visit /qr?session=${session} to link device.`
        });
    }

    const jid = formatJid(phone);
    try {
        const sent = await sess.sock.sendMessage(jid, { text: message });
        storeSentMessage(sent.key.id, { conversation: message });
        res.json({
            success: true,
            session: sess.id,
            message: 'Message sent successfully',
            data: { id: sent.key.id, to: jid, timestamp: sent.messageTimestamp }
        });
    } catch (err) {
        console.error(`[USICS WA Engine] [${session}] Send error:`, err);
        res.status(500).json({ success: false, error: err.message });
    }
});

/**
 * List Participating WhatsApp Groups
 */
app.get('/api/groups', authMiddleware, async (req, res) => {
    const requestedSession = req.query.session || 'sales';
    const sess = resolveSendSession(requestedSession);

    if (sess.status !== 'connected' || !sess.sock) {
        return res.json({
            success: false,
            groups: [],
            error: `WhatsApp Engine [${requestedSession.toUpperCase()}] belum terhubung.`
        });
    }

    try {
        const participating = await sess.sock.groupFetchAllParticipating();
        const groups = Object.values(participating || {}).map((g) => ({
            id: g.id,
            name: g.subject || g.id,
            participantsCount: Array.isArray(g.participants) ? g.participants.length : (g.size || 0),
            creation: g.creation || null
        })).sort((a, b) => a.name.localeCompare(b.name));

        res.json({
            success: true,
            session: sess.id,
            groups
        });
    } catch (err) {
        console.error(`[USICS WA Engine] [${sess.id}] Fetch groups error:`, err);
        res.status(500).json({ success: false, groups: [], error: err.message });
    }
});

/**
 * Send Media (Image, PDF, Document, PPTX, DOCX)
 */
app.post('/api/send-media', authMiddleware, async (req, res) => {
    const { phone, url, filePath, caption = '', type = 'document', filename = 'file.pdf', mimetype = 'application/pdf', session = 'sales' } = req.body;
    const sess = resolveSendSession(session);

    if (!phone || (!url && !filePath)) {
        return res.status(400).json({ success: false, error: 'Phone and URL/filePath fields are required' });
    }

    if (sess.status !== 'connected' || !sess.sock) {
        return res.status(503).json({
            success: false,
            error: `WhatsApp Engine [${session.toUpperCase()}] is not connected. Visit /qr?session=${session} to link device.`
        });
    }

    const jid = formatJid(phone);
    try {
        let mediaSource = { url };
        if (filePath && fs.existsSync(filePath)) {
            mediaSource = fs.readFileSync(filePath);
        }

        let resolvedMime = mimetype || 'application/pdf';
        if (filename.endsWith('.pptx')) {
            resolvedMime = 'application/vnd.openxmlformats-officedocument.presentationml.presentation';
        } else if (filename.endsWith('.docx')) {
            resolvedMime = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        } else if (filename.endsWith('.xlsx')) {
            resolvedMime = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        }

        let messagePayload = {};
        if (type === 'image' || resolvedMime.startsWith('image/')) {
            messagePayload = {
                image: mediaSource,
                caption: caption
            };
        } else {
            messagePayload = {
                document: mediaSource,
                mimetype: resolvedMime,
                fileName: filename || 'document.pdf',
                caption: caption
            };
        }

        const sent = await sess.sock.sendMessage(jid, messagePayload);
        storeSentMessage(sent.key.id, { conversation: caption || filename });
        res.json({
            success: true,
            session: sess.id,
            message: 'Media sent successfully',
            data: { id: sent.key.id, to: jid, timestamp: sent.messageTimestamp }
        });
    } catch (err) {
        console.error(`[USICS WA Engine] [${session}] Send media error:`, err);
        res.status(500).json({ success: false, error: err.message });
    }
});

// Start Server & Connect All Sessions
app.listen(PORT, () => {
    console.log(`=======================================================`);
    console.log(`🚀 USICS WhatsApp Engine (Multi-Session Node.js)`);
    console.log(`🌐 Server running at: http://localhost:${PORT}`);
    console.log(`📱 QR Code Dashboard: http://localhost:${PORT}/qr`);
    console.log(`🔑 Protected with API Key: ${API_KEY ? 'Yes' : 'No'}`);
    console.log(`📦 Sessions Active: [SALES], [PURCHASING] & [INVENTORY]`);
    console.log(`=======================================================`);
    startSession('sales');
    startSession('purchasing');
    startSession('inventory');
});
