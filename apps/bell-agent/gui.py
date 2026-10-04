import os
import sys
import time
import datetime
import threading
import tkinter as tk
import customtkinter as ctk
from PIL import Image, ImageDraw
import pystray
from agent_core import BellAgentCore

# Set theme
ctk.set_appearance_mode("dark")
ctk.set_default_color_theme("blue")

def create_bell_icon():
    """Generate a clean 64x64 bell icon image for System Tray and window."""
    img = Image.new('RGBA', (64, 64), color=(0, 0, 0, 0))
    draw = ImageDraw.Draw(img)
    # Background circle
    draw.ellipse([2, 2, 62, 62], fill=(15, 23, 42, 255), outline=(6, 182, 212, 255), width=2)
    # Golden Bell Body
    draw.polygon([(32, 14), (20, 36), (44, 36)], fill=(245, 158, 11, 255))
    draw.pieslice([22, 24, 42, 44], 0, 180, fill=(245, 158, 11, 255))
    # Bell rim
    draw.rounded_rectangle([18, 38, 46, 44], radius=3, fill=(217, 119, 6, 255))
    # Clapper
    draw.ellipse([29, 44, 35, 50], fill=(251, 191, 36, 255))
    return img

class BellAgentApp(ctk.CTk):
    def __init__(self):
        super().__init__()

        self.title("JICOS Factory Bell Agent - TOA Sound Terminal")
        self.geometry("720x680")
        self.minsize(680, 620)

        # Initialize Core
        self.core = BellAgentCore(
            on_status_change=self.on_core_status_change,
            on_bell_ring=self.on_bell_ring_event
        )

        self.icon_image = create_bell_icon()
        self.tray_icon = None
        self._is_quitting = False

        self.setup_ui()
        self.populate_audio_devices()
        self.start_clock_timer()

        # Start Core
        self.core.start()

        # Handle window close
        self.protocol("WM_DELETE_WINDOW", self.on_close_window)

    def setup_ui(self):
        self.grid_columnconfigure(0, weight=1)
        self.grid_rowconfigure(3, weight=1)

        # 1. HEADER
        self.header_frame = ctk.CTkFrame(self, corner_radius=10, fg_color="#1e293b")
        self.header_frame.grid(row=0, column=0, padx=16, pady=(16, 8), sticky="ew")
        self.header_frame.grid_columnconfigure(1, weight=1)

        # Bell Icon Label
        self.logo_label = ctk.CTkLabel(
            self.header_frame, 
            text="🔔", 
            font=ctk.CTkFont(size=30)
        )
        self.logo_label.grid(row=0, column=0, rowspan=2, padx=(16, 12), pady=12)

        # App Title
        self.title_label = ctk.CTkLabel(
            self.header_frame, 
            text="JICOS BELL AGENT", 
            font=ctk.CTkFont(family="Arial", size=18, weight="bold"),
            text_color="#f8fafc"
        )
        self.title_label.grid(row=0, column=1, sticky="w", pady=(10, 0))

        self.subtitle_label = ctk.CTkLabel(
            self.header_frame, 
            text="Dedicated Factory PA & TOA Speaker Controller", 
            font=ctk.CTkFont(family="Arial", size=11),
            text_color="#94a3b8"
        )
        self.subtitle_label.grid(row=1, column=1, sticky="w", pady=(0, 10))

        # Live Clock Box
        self.clock_frame = ctk.CTkFrame(self.header_frame, fg_color="#0f172a", corner_radius=8)
        self.clock_frame.grid(row=0, column=2, rowspan=2, padx=16, pady=10, sticky="e")

        self.clock_label = ctk.CTkLabel(
            self.clock_frame, 
            text="00:00:00 WIB", 
            font=ctk.CTkFont(family="Consolas", size=17, weight="bold"),
            text_color="#38bdf8"
        )
        self.clock_label.pack(padx=12, pady=(6, 2))

        self.date_label = ctk.CTkLabel(
            self.clock_frame, 
            text="Senin, 01 Jan 2026", 
            font=ctk.CTkFont(family="Arial", size=10),
            text_color="#64748b"
        )
        self.date_label.pack(padx=12, pady=(0, 6))

        # 2. CONFIG CARDS (SERVER & AUDIO OUTPUT)
        self.settings_frame = ctk.CTkFrame(self, corner_radius=10, fg_color="#1e293b")
        self.settings_frame.grid(row=1, column=0, padx=16, pady=8, sticky="ew")
        self.settings_frame.grid_columnconfigure(1, weight=1)

        # Server URL row
        self.lbl_server = ctk.CTkLabel(self.settings_frame, text="URL Server ERP:", font=ctk.CTkFont(weight="bold"))
        self.lbl_server.grid(row=0, column=0, padx=(16, 8), pady=(12, 6), sticky="w")

        self.entry_server = ctk.CTkEntry(self.settings_frame, placeholder_text="http://192.168.1.50 atau http://erp.test")
        self.entry_server.grid(row=0, column=1, padx=8, pady=(12, 6), sticky="ew")
        self.entry_server.insert(0, self.core.config.get("server_url", "http://erp.test"))

        self.btn_sync = ctk.CTkButton(
            self.settings_frame, 
            text="🔄 Sync Jadwal", 
            width=110,
            command=self.manual_sync,
            fg_color="#0284c7",
            hover_color="#0369a1"
        )
        self.btn_sync.grid(row=0, column=2, padx=(8, 16), pady=(12, 6))

        # Connection status row
        self.lbl_status_tag = ctk.CTkLabel(self.settings_frame, text="Status Koneksi:", font=ctk.CTkFont(size=12))
        self.lbl_status_tag.grid(row=1, column=0, padx=(16, 8), pady=4, sticky="w")

        self.lbl_status_val = ctk.CTkLabel(
            self.settings_frame, 
            text="🟢 Memeriksa server...", 
            font=ctk.CTkFont(size=12, weight="bold"),
            text_color="#10b981"
        )
        self.lbl_status_val.grid(row=1, column=1, columnspan=2, padx=8, pady=4, sticky="w")

        # Audio Output Device Row (CRUCIAL!)
        self.lbl_device = ctk.CTkLabel(self.settings_frame, text="Output Audio (TOA):", font=ctk.CTkFont(weight="bold"))
        self.lbl_device.grid(row=2, column=0, padx=(16, 8), pady=(10, 14), sticky="w")

        self.combo_device = ctk.CTkComboBox(self.settings_frame, values=["Mendeteksi soundcard..."], command=self.on_device_selected)
        self.combo_device.grid(row=2, column=1, padx=8, pady=(10, 14), sticky="ew")

        self.btn_test = ctk.CTkButton(
            self.settings_frame, 
            text="▶️ Tes Bunyi TOA", 
            width=110,
            command=self.test_bell_sound,
            fg_color="#10b981",
            hover_color="#059669"
        )
        self.btn_test.grid(row=2, column=2, padx=(8, 16), pady=(10, 14))

        # 3. UPCOMING BELL COUNTDOWN BANNER
        self.banner_frame = ctk.CTkFrame(self, corner_radius=10, fg_color="#0f172a", border_width=1, border_color="#334155")
        self.banner_frame.grid(row=2, column=0, padx=16, pady=8, sticky="ew")
        self.banner_frame.grid_columnconfigure(0, weight=1)

        self.banner_title = ctk.CTkLabel(
            self.banner_frame, 
            text="JADWAL BEL BERIKUTNYA HARI INI", 
            font=ctk.CTkFont(size=11, weight="bold"),
            text_color="#94a3b8"
        )
        self.banner_title.pack(pady=(10, 2))

        self.banner_schedule_name = ctk.CTkLabel(
            self.banner_frame, 
            text="Memuat jadwal...", 
            font=ctk.CTkFont(size=16, weight="bold"),
            text_color="#f8fafc"
        )
        self.banner_schedule_name.pack(pady=2)

        self.banner_countdown = ctk.CTkLabel(
            self.banner_frame, 
            text="⏳ 00:00:00", 
            font=ctk.CTkFont(family="Consolas", size=24, weight="bold"),
            text_color="#fbbf24"
        )
        self.banner_countdown.pack(pady=(2, 10))

        # 4. TODAY'S SCHEDULES LIST
        self.list_container = ctk.CTkFrame(self, corner_radius=10, fg_color="#1e293b")
        self.list_container.grid(row=3, column=0, padx=16, pady=8, sticky="nsew")
        self.list_container.grid_columnconfigure(0, weight=1)
        self.list_container.grid_rowconfigure(1, weight=1)

        self.list_header = ctk.CTkLabel(
            self.list_container, 
            text="📋 Daftar Jadwal Bel Hari Ini:", 
            font=ctk.CTkFont(weight="bold", size=13),
            text_color="#cbd5e1"
        )
        self.list_header.grid(row=0, column=0, padx=16, pady=(12, 6), sticky="w")

        self.scroll_list = ctk.CTkScrollableFrame(self.list_container, fg_color="#0f172a", corner_radius=8)
        self.scroll_list.grid(row=1, column=0, padx=12, pady=(0, 12), sticky="nsew")
        self.scroll_list.grid_columnconfigure(1, weight=1)

        # 5. FOOTER
        self.footer_frame = ctk.CTkFrame(self, corner_radius=0, fg_color="transparent")
        self.footer_frame.grid(row=4, column=0, padx=16, pady=(4, 12), sticky="ew")
        self.footer_frame.grid_columnconfigure(0, weight=1)

        self.chk_tray = ctk.CTkCheckBox(
            self.footer_frame, 
            text="Minimize ke System Tray saat ditutup (X)",
            command=self.on_toggle_tray_setting
        )
        self.chk_tray.grid(row=0, column=0, sticky="w")
        if self.core.config.get("minimize_to_tray_on_close", True):
            self.chk_tray.select()

        self.btn_hide_tray = ctk.CTkButton(
            self.footer_frame,
            text="Sembunyikan ke Tray",
            width=140,
            command=self.hide_to_tray,
            fg_color="#334155",
            hover_color="#475569"
        )
        self.btn_hide_tray.grid(row=0, column=1, sticky="e")

    def populate_audio_devices(self):
        devices = self.core.audio.get_output_devices()
        self.audio_devices = devices
        
        if not devices:
            self.combo_device.configure(values=["Tidak ada soundcard ditemukan"])
            return

        display_names = [d['display_name'] for d in devices]
        self.combo_device.configure(values=display_names)

        # Select saved device or default Realtek/Speaker
        saved_id = self.core.config.get('audio_device_id')
        matched = False
        if saved_id is not None:
            for d in devices:
                if d['id'] == saved_id:
                    self.combo_device.set(d['display_name'])
                    matched = True
                    break

        if not matched:
            # Pick first Realtek / Speakers device if available
            preferred = None
            for d in devices:
                if "speaker" in d['name'].lower() or "realtek" in d['name'].lower():
                    preferred = d
                    break
            chosen = preferred or devices[0]
            self.combo_device.set(chosen['display_name'])
            self.core.config['audio_device_id'] = chosen['id']
            self.core.config['audio_device_name'] = chosen['name']
            self.core.save_config()

    def on_device_selected(self, choice):
        for d in self.audio_devices:
            if d['display_name'] == choice:
                self.core.config['audio_device_id'] = d['id']
                self.core.config['audio_device_name'] = d['name']
                self.core.save_config()
                print(f"[UI] Selected Audio Output Device: {d['display_name']}")
                break

    def on_toggle_tray_setting(self):
        self.core.config['minimize_to_tray_on_close'] = bool(self.chk_tray.get())
        self.core.save_config()

    def manual_sync(self):
        new_url = self.entry_server.get().strip()
        if new_url:
            self.core.config['server_url'] = new_url
            self.core.save_config()

        self.lbl_status_val.configure(text="🔄 Menghubungi server...", text_color="#38bdf8")
        threading.Thread(target=self._run_manual_sync, daemon=True).start()

    def _run_manual_sync(self):
        ok, msg = self.core.sync_with_server()
        self.after(0, lambda: self._update_sync_ui(ok, msg))

    def _update_sync_ui(self, ok, msg):
        if ok:
            self.lbl_status_val.configure(text=f"🟢 Terhubung & Tersinkron ({datetime.datetime.now().strftime('%H:%M')})", text_color="#10b981")
        else:
            self.lbl_status_val.configure(text=f"🟡 Offline (Jadwal Lokal Aktif): {msg[:35]}...", text_color="#f59e0b")
        self.refresh_schedules_table()

    def test_bell_sound(self):
        self.core.test_bell()

    def refresh_schedules_table(self):
        # Clear existing
        for widget in self.scroll_list.winfo_children():
            widget.destroy()

        today_schedules = self.core.get_today_schedules()
        if not today_schedules:
            empty_lbl = ctk.CTkLabel(
                self.scroll_list, 
                text="Tidak ada jadwal bel aktif untuk hari ini.",
                text_color="#64748b",
                font=ctk.CTkFont(size=12)
            )
            empty_lbl.pack(pady=20)
            return

        now_str = datetime.datetime.now().strftime('%H:%M:%S')

        for idx, s in enumerate(today_schedules):
            row = ctk.CTkFrame(self.scroll_list, fg_color="#1e293b" if idx % 2 == 0 else "#182234", corner_radius=6)
            row.pack(fill="x", padx=4, pady=3)
            row.grid_columnconfigure(1, weight=1)

            # Time badge
            t_str = s.get('time', '00:00:00')[:5]
            time_badge = ctk.CTkLabel(
                row, 
                text=t_str, 
                font=ctk.CTkFont(family="Consolas", size=13, weight="bold"),
                text_color="#38bdf8",
                width=65
            )
            time_badge.grid(row=0, column=0, padx=8, pady=6)

            # Name & sound type
            stype = s.get('sound_type', 'chime').upper()
            vol = s.get('volume', 80)
            name_lbl = ctk.CTkLabel(
                row, 
                text=f"{s.get('name')}  •  [{stype} - {vol}%]", 
                font=ctk.CTkFont(size=12, weight="bold"),
                text_color="#f1f5f9"
            )
            name_lbl.grid(row=0, column=1, padx=6, pady=6, sticky="w")

            # Status pill
            is_past = t_str < now_str[:5]
            status_text = "✓ Selesai" if is_past else "Menunggu"
            status_color = "#64748b" if is_past else "#10b981"

            pill = ctk.CTkLabel(
                row,
                text=status_text,
                font=ctk.CTkFont(size=11),
                text_color=status_color
            )
            pill.grid(row=0, column=2, padx=12, pady=6)

    def on_core_status_change(self):
        is_conn = self.core.is_connected
        text = "🟢 Terhubung ke ERP" if is_conn else "🟡 Offline (Cache Aktif)"
        color = "#10b981" if is_conn else "#f59e0b"
        self.after(0, lambda: self.lbl_status_val.configure(text=text, text_color=color))
        self.after(0, self.refresh_schedules_table)

    def on_bell_ring_event(self, schedule):
        name = schedule.get('name', 'Bel')
        self.after(0, lambda: self.banner_schedule_name.configure(text=f"🔔 SEDANG BERBUNYI: {name}", text_color="#ef4444"))
        self.after(0, self.refresh_schedules_table)

    def start_clock_timer(self):
        def _tick():
            now = datetime.datetime.now()
            # Clock
            time_str = now.strftime('%H:%M:%S WIB')
            self.clock_label.configure(text=time_str)

            # Date
            days_indo = {'Monday': 'Senin', 'Tuesday': 'Selasa', 'Wednesday': 'Rabu', 'Thursday': 'Kamis', 'Friday': 'Jumat', 'Saturday': 'Sabtu', 'Sunday': 'Minggu'}
            indo_day = days_indo.get(now.strftime('%A'), now.strftime('%A'))
            date_str = f"{indo_day}, {now.strftime('%d %b %Y')}"
            self.date_label.configure(text=date_str)

            # Countdown
            next_s, cd_str, _ = self.core.get_next_schedule()
            if next_s:
                self.banner_schedule_name.configure(
                    text=f"{next_s.get('time')[:5]} - {next_s.get('name')}",
                    text_color="#f8fafc"
                )
                self.banner_countdown.configure(text=f"⏳ Tersisa {cd_str}", text_color="#fbbf24")
            else:
                self.banner_schedule_name.configure(text="Semua bel hari ini telah berbunyi", text_color="#94a3b8")
                self.banner_countdown.configure(text="✓ SELESAI HARI INI", text_color="#10b981")

            self.after(1000, _tick)

        self.after(500, _tick)

    # SYSTEM TRAY LOGIC
    def setup_tray(self):
        menu = pystray.Menu(
            pystray.MenuItem("Buka Dashboard JICOS Bell", self.show_from_tray, default=True),
            pystray.MenuItem("Tes Bunyi Bel TOA", lambda: self.core.test_bell()),
            pystray.MenuItem("Sinkronkan Jadwal Sekarang", lambda: self.manual_sync()),
            pystray.Menu.SEPARATOR,
            pystray.MenuItem("Keluar Aplikasi", self.quit_app)
        )
        self.tray_icon = pystray.Icon("JICOS Bell Agent", self.icon_image, "JICOS Bell Agent (TOA)", menu)
        threading.Thread(target=self.tray_icon.run, daemon=True).start()

    def hide_to_tray(self):
        self.withdraw()
        if not self.tray_icon:
            self.setup_tray()

    def show_from_tray(self, icon=None, item=None):
        self.after(0, self._restore_window)

    def _restore_window(self):
        self.deiconify()
        self.lift()
        self.focus_force()

    def on_close_window(self):
        if self.core.config.get("minimize_to_tray_on_close", True):
            self.hide_to_tray()
        else:
            self.quit_app()

    def quit_app(self, icon=None, item=None):
        self._is_quitting = True
        self.core.stop()
        if self.tray_icon:
            try:
                self.tray_icon.stop()
            except Exception:
                pass
        self.after(0, self.destroy)
        sys.exit(0)
