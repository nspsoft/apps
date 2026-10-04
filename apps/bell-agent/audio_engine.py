import os
import time
import tempfile
import numpy as np
import sounddevice as sd
import soundfile as sf

class AudioEngine:
    def __init__(self):
        self.sample_rate = 44100
        self._current_stream = None

    @staticmethod
    def get_output_devices():
        """
        Returns a list of all audio output devices available on this Windows PC.
        """
        devices = []
        try:
            all_devices = sd.query_devices()
            hostapis = sd.query_hostapis()
            
            for idx, dev in enumerate(all_devices):
                if dev.get('max_output_channels', 0) > 0:
                    api_name = hostapis[dev['hostapi']]['name'] if dev['hostapi'] < len(hostapis) else 'Unknown'
                    # Prefer DirectSound, MME, or WASAPI
                    devices.append({
                        'id': idx,
                        'name': dev['name'],
                        'api': api_name,
                        'display_name': f"[{idx}] {dev['name']} ({api_name})",
                        'channels': dev['max_output_channels']
                    })
        except Exception as e:
            print(f"[AudioEngine] Error querying devices: {e}")
        return devices

    def generate_chime(self, volume=100):
        """
        Synthesizes high-fidelity 4-tone industrial chime (C5 -> E5 -> G5 -> C6).
        """
        sr = self.sample_rate
        # Notes: freq, start_time, duration
        notes = [
            (523.25, 0.0, 0.8),   # C5
            (659.25, 0.35, 0.8),  # E5
            (783.99, 0.70, 0.8),  # G5
            (1046.50, 1.05, 1.6)  # C6
        ]
        
        total_duration = 2.8
        total_samples = int(sr * total_duration)
        audio = np.zeros(total_samples, dtype=np.float32)

        for freq, start_sec, dur_sec in notes:
            start_sample = int(sr * start_sec)
            note_samples = int(sr * dur_sec)
            t = np.linspace(0, dur_sec, note_samples, False)

            # Fundamental + soft 2nd and 3rd harmonics for bell chime resonance
            wave = (
                0.70 * np.sin(2 * np.pi * freq * t) +
                0.20 * np.sin(2 * np.pi * (freq * 2) * t) +
                0.10 * np.sin(2 * np.pi * (freq * 3) * t)
            )

            # Smooth exponential decay envelope
            envelope = np.exp(-3.5 * (t / dur_sec))
            # Quick 5ms attack to eliminate clicks
            attack_samples = int(sr * 0.005)
            if attack_samples > 0 and note_samples > attack_samples:
                envelope[:attack_samples] *= np.linspace(0, 1, attack_samples)

            note_audio = wave * envelope
            end_sample = min(start_sample + note_samples, total_samples)
            actual_len = end_sample - start_sample
            audio[start_sample:end_sample] += note_audio[:actual_len]

        # Apply volume scaling (0.0 - 1.0)
        vol_factor = max(0.0, min(1.0, (volume / 100.0)))
        audio = audio * vol_factor * 0.85
        
        # Dual-channel stereo
        stereo_audio = np.column_stack((audio, audio))
        return stereo_audio

    def play_chime(self, device_id=None, volume=100, blocking=False):
        """
        Play native synthesized chime directly to selected soundcard.
        """
        try:
            self.stop()
            chime_data = self.generate_chime(volume)
            sd.play(chime_data, samplerate=self.sample_rate, device=device_id)
            if blocking:
                sd.wait()
            return True
        except Exception as e:
            print(f"[AudioEngine] Failed to play chime: {e}")
            return False

    def play_audio_file(self, file_path, device_id=None, volume=100, blocking=False):
        """
        Play custom audio file (MP3, WAV, OGG) directly to selected soundcard.
        """
        if not os.path.exists(file_path):
            print(f"[AudioEngine] Audio file not found: {file_path}")
            return False

        try:
            self.stop()
            data, fs = sf.read(file_path, dtype='float32')
            
            # Apply volume scaling
            vol_factor = max(0.0, min(1.0, (volume / 100.0)))
            data = data * vol_factor

            sd.play(data, samplerate=fs, device=device_id)
            if blocking:
                sd.wait()
            return True
        except Exception as e:
            print(f"[AudioEngine] Failed to play file {file_path}: {e}")
            return False

    def play_tts(self, text, device_id=None, volume=100, blocking=False):
        """
        Converts text to speech, saves to temp WAV, and plays to selected soundcard.
        """
        if not text or not text.strip():
            return False

        temp_wav = os.path.join(tempfile.gettempdir(), f"jicos_bell_tts_{int(time.time())}.wav")
        try:
            import pyttsx3
            engine = pyttsx3.init()
            # Set speech rate slightly slower for factory PA acoustics
            engine.setProperty('rate', 150)
            engine.save_to_file(text, temp_wav)
            engine.runAndWait()

            if os.path.exists(temp_wav):
                res = self.play_audio_file(temp_wav, device_id=device_id, volume=volume, blocking=blocking)
                try:
                    os.remove(temp_wav)
                except Exception:
                    pass
                return res
        except Exception as e:
            print(f"[AudioEngine] Failed to synthesize TTS: {e}")
            return False

    def stop(self):
        """
        Immediately stops any running audio playback.
        """
        try:
            sd.stop()
        except Exception:
            pass
