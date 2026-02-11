
log_path = r"c:\laragon\www\ERP\storage\logs\laravel.log"
try:
    with open(log_path, 'r') as f:
        lines = f.readlines()
        for line in lines[-30:]:
            print(line.strip())
except Exception as e:
    print(f"Error: {e}")
