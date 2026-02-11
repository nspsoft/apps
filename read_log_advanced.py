
import os

log_path = r"c:\laragon\www\ERP\storage\logs\laravel.log"
try:
    with open(log_path, 'rb') as f:
        f.seek(0, os.SEEK_END)
        size = f.tell()
        read_size = min(size, 100000)
        f.seek(-read_size, os.SEEK_END)
        content = f.read().decode('utf-8', errors='ignore')
        
        last_error_index = content.rfind("SQLSTATE")
        if last_error_index != -1:
            start = max(0, last_error_index - 100)
            end = min(len(content), last_error_index + 400)
            print("--- ERROR FOUND ---")
            print(content[start:end])
        else:
            print("No SQLSTATE found in last 100KB")
except Exception as e:
    print(f"Error: {e}")
