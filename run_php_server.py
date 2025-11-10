import sys
sys.stdout.reconfigure(encoding='utf-8')

import os
import socket
import subprocess

# 🔹 Thư mục chứa file index.php
project_dir = r"c:\xampp\htdocs\du_an_ban_dien_thoai"

# 🔹 Cổng (bạn có thể đổi sang 8080 hoặc 9000)
port = 8000

# 🔹 Lấy IP nội bộ để chia sẻ trong mạng Wi-Fi
s = socket.socket(socket.AF_INET, socket.SOCK_DGRAM)
s.connect(("8.8.8.8", 80))
ip = s.getsockname()[0]
s.close()

print("\n=== Địa chỉ truy cập ===")
print(f"Máy bạn: http://localhost:{port}")
print(f"Thiết bị khác trong cùng Wi-Fi: http://{ip}:{port}\n")

# 🔹 Chạy PHP built-in server
os.chdir(project_dir)
subprocess.run(["php", "-S", f"0.0.0.0:{port}", "-t", project_dir])
