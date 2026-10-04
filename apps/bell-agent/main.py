import os
import sys

# Ensure current directory is in sys.path
BASE_DIR = os.path.dirname(os.path.abspath(__file__))
if BASE_DIR not in sys.path:
    sys.path.insert(0, BASE_DIR)

from gui import BellAgentApp

def main():
    try:
        app = BellAgentApp()
        app.mainloop()
    except KeyboardInterrupt:
        sys.exit(0)
    except Exception as e:
        print(f"Error starting JICOS Bell Agent: {e}")
        import traceback
        traceback.print_exc()

if __name__ == '__main__':
    main()
