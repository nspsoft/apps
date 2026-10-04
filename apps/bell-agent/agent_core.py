import os
import json
import time
import datetime
import threading
import requests
from audio_engine import AudioEngine

BASE_DIR = os.path.dirname(os.path.abspath(__file__))
CACHE_DIR = os.path.join(BASE_DIR, 'cache')
SOUNDS_DIR = os.path.join(CACHE_DIR, 'sounds')
CONFIG_FILE = os.path.join(BASE_DIR, 'config.json')
SCHEDULES_CACHE_FILE = os.path.join(CACHE_DIR, 'schedules.json')

class BellAgentCore:
    def __init__(self, on_status_change=None, on_bell_ring=None):
        self.audio = AudioEngine()
        self.config = self.load_config()
        self.schedules = []
        self.checksum = None
        self.last_sync_time = None
        self.is_connected = False
        self.last_played = {} # { "id_YYYYMMDD_HHMM": True }
        
        self.on_status_change = on_status_change
        self.on_bell_ring = on_bell_ring
        
        self._running = False
        self._thread = None
        self._sync_thread = None

        os.makedirs(SOUNDS_DIR, exist_ok=True)
        self.load_cached_schedules()

    def load_config(self):
        defaults = {
            "server_url": "http://erp.test",
            "api_endpoint": "/api/v1/bell-agent",
            "audio_device_id": None,
            "audio_device_name": "Default",
            "sync_interval_seconds": 180,
            "minimize_to_tray_on_close": True,
            "start_with_windows": False,
            "volume_boost": 100
        }
        if os.path.exists(CONFIG_FILE):
            try:
                with open(CONFIG_FILE, 'r', encoding='utf-8') as f:
                    data = json.load(f)
                    defaults.update(data)
            except Exception as e:
                print(f"[Core] Error reading config: {e}")
        return defaults

    def save_config(self, new_config=None):
        if new_config:
            self.config.update(new_config)
        try:
            with open(CONFIG_FILE, 'w', encoding='utf-8') as f:
                json.dump(self.config, f, indent=2)
            print("[Core] Config saved successfully.")
        except Exception as e:
            print(f"[Core] Error saving config: {e}")

    def load_cached_schedules(self):
        if os.path.exists(SCHEDULES_CACHE_FILE):
            try:
                with open(SCHEDULES_CACHE_FILE, 'r', encoding='utf-8') as f:
                    data = json.load(f)
                    self.schedules = data.get('data', [])
                    self.checksum = data.get('checksum')
                    print(f"[Core] Loaded {len(self.schedules)} cached schedules from disk.")
            except Exception as e:
                print(f"[Core] Error reading cached schedules: {e}")

    def save_cached_schedules(self, payload):
        try:
            with open(SCHEDULES_CACHE_FILE, 'w', encoding='utf-8') as f:
                json.dump(payload, f, indent=2)
        except Exception as e:
            print(f"[Core] Error saving cache: {e}")

    def sync_with_server(self):
        """
        Synchronizes schedules from ERP server.
        """
        base_url = self.config.get('server_url', '').rstrip('/')
        endpoint = self.config.get('api_endpoint', '/api/v1/bell-agent').strip('/')
        
        url_ping = f"{base_url}/{endpoint}/ping"
        url_schedules = f"{base_url}/{endpoint}/schedules"

        try:
            resp_ping = requests.get(url_ping, timeout=4)
            if resp_ping.status_code == 200:
                ping_data = resp_ping.json()
                server_checksum = ping_data.get('checksum')
                self.is_connected = True

                # If checksum changed or no schedules, fetch details
                if server_checksum != self.checksum or not self.schedules:
                    resp_sched = requests.get(url_schedules, timeout=6)
                    if resp_sched.status_code == 200:
                        data = resp_sched.json()
                        self.schedules = data.get('data', [])
                        self.checksum = data.get('checksum')
                        self.save_cached_schedules(data)
                        self.download_missing_audio_files()
                
                self.last_sync_time = datetime.datetime.now()
                self._notify_status()
                return True, "Berhasil terhubung & sinkron dengan server ERP."
            else:
                self.is_connected = False
                self._notify_status()
                return False, f"Server merespon HTTP {resp_ping.status_code}"
        except Exception as e:
            self.is_connected = False
            self._notify_status()
            return False, f"Gagal terhubung ke ERP: {e} (Menggunakan jadwal offline lokal)"

    def download_missing_audio_files(self):
        """
        Downloads custom sound files locally so playback is instantaneous and offline-ready.
        """
        for item in self.schedules:
            if item.get('sound_type') == 'custom' and item.get('sound_url') and item.get('sound_filename'):
                filename = item['sound_filename']
                local_path = os.path.join(SOUNDS_DIR, filename)
                if not os.path.exists(local_path):
                    try:
                        print(f"[Core] Downloading custom bell sound: {filename}...")
                        r = requests.get(item['sound_url'], stream=True, timeout=10)
                        if r.status_code == 200:
                            with open(local_path, 'wb') as f:
                                for chunk in r.iter_content(chunk_size=8192):
                                    f.write(chunk)
                            print(f"[Core] Successfully cached: {filename}")
                    except Exception as e:
                        print(f"[Core] Error downloading sound {filename}: {e}")

    def get_today_schedules(self):
        """
        Returns list of active schedules for today, sorted by time.
        """
        now = datetime.datetime.now()
        day_name = now.strftime('%A') # e.g. Monday
        
        today_list = []
        for s in self.schedules:
            if not s.get('is_active', True):
                continue
            days = s.get('days', [])
            if day_name in days:
                today_list.append(s)

        today_list.sort(key=lambda x: x.get('time', '00:00:00'))
        return today_list

    def get_next_schedule(self):
        """
        Calculates next upcoming bell for today and countdown string.
        """
        now = datetime.datetime.now()
        current_time_str = now.strftime('%H:%M:%S')
        today_schedules = self.get_today_schedules()

        for s in today_schedules:
            sched_time = s.get('time', '00:00:00')
            if sched_time > current_time_str:
                # Calculate diff
                h, m, sec = map(int, sched_time.split(':'))
                target_dt = now.replace(hour=h, minute=m, second=sec, microsecond=0)
                diff = target_dt - now
                total_seconds = int(diff.total_seconds())
                if total_seconds >= 0:
                    cd_hours = total_seconds // 3600
                    cd_mins = (total_seconds % 3600) // 60
                    cd_secs = total_seconds % 60
                    cd_str = f"{cd_hours:02d}:{cd_mins:02d}:{cd_secs:02d}"
                    return s, cd_str, total_seconds
        
        return None, "Selesai untuk hari ini", 0

    def trigger_bell(self, schedule):
        """
        Plays the bell based on its sound_type and configured audio device.
        """
        dev_id = self.config.get('audio_device_id')
        vol = schedule.get('volume', 80)
        sound_type = schedule.get('sound_type', 'chime')

        print(f"\n[Core] 🔔 BELL TRIGGERED: '{schedule.get('name')}' at {datetime.datetime.now().strftime('%H:%M:%S')} (Device ID: {dev_id}, Vol: {vol}%)")

        if self.on_bell_ring:
            try:
                self.on_bell_ring(schedule)
            except Exception:
                pass

        if sound_type == 'custom':
            filename = schedule.get('sound_filename')
            local_path = os.path.join(SOUNDS_DIR, filename) if filename else None
            if local_path and os.path.exists(local_path):
                self.audio.play_audio_file(local_path, device_id=dev_id, volume=vol)
            else:
                print(f"[Core] Custom sound file not found locally, falling back to chime.")
                self.audio.play_chime(device_id=dev_id, volume=vol)
        elif sound_type == 'tts':
            text = schedule.get('tts_text') or f"Waktu {schedule.get('name')} telah tiba."
            # Play a soft preamble chime first, then voice
            self.audio.play_chime(device_id=dev_id, volume=vol, blocking=True)
            time.sleep(0.3)
            self.audio.play_tts(text, device_id=dev_id, volume=vol)
        else:
            # Native chime
            self.audio.play_chime(device_id=dev_id, volume=vol)

    def test_bell(self):
        """
        Plays a test chime on the configured device.
        """
        dev_id = self.config.get('audio_device_id')
        vol = self.config.get('volume_boost', 100)
        print(f"[Core] Playing test chime on device {dev_id}...")
        self.audio.play_chime(device_id=dev_id, volume=vol)

    def _notify_status(self):
        if self.on_status_change:
            try:
                self.on_status_change()
            except Exception:
                pass

    def _loop(self):
        """
        Main execution loop checking time every second.
        """
        print("[Core] Scheduler loop started.")
        while self._running:
            now = datetime.datetime.now()
            today_day = now.strftime('%A')
            current_hm = now.strftime('%H:%M')
            current_sec = now.second

            # We check on seconds 00 to 02
            if current_sec <= 2:
                for s in self.schedules:
                    if not s.get('is_active', True):
                        continue
                    days = s.get('days', [])
                    if today_day not in days:
                        continue
                    
                    sched_time = s.get('time', '00:00:00')
                    sched_hm = sched_time[:5]
                    
                    if sched_hm == current_hm:
                        track_key = f"{s.get('id')}_{now.strftime('%Y%m%d_%H%M')}"
                        if not self.last_played.get(track_key):
                            self.last_played[track_key] = True
                            threading.Thread(target=self.trigger_bell, args=(s,), daemon=True).start()

            time.sleep(0.8)

    def _sync_loop(self):
        """
        Periodic background sync with ERP server.
        """
        while self._running:
            interval = max(30, self.config.get('sync_interval_seconds', 180))
            time.sleep(interval)
            if self._running:
                self.sync_with_server()

    def start(self):
        if self._running:
            return
        self._running = True
        self.sync_with_server()
        
        self._thread = threading.Thread(target=self._loop, daemon=True)
        self._thread.start()

        self._sync_thread = threading.Thread(target=self._sync_loop, daemon=True)
        self._sync_thread.start()
        print("[Core] Bell Agent started.")

    def stop(self):
        self._running = False
        self.audio.stop()
        print("[Core] Bell Agent stopped.")
