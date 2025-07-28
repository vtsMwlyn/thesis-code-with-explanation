# ===== Import library =====
import serial
import requests
import time

# ===== Konfigurasi =====
COM_PORT = 'COM4' # setel port USB
BAUD_RATE = 9600 # setel kecepatan serial monitor (disamain kayak serial monitor arduino)
URL = "https://thesis.esage.site/public/api/send-sensor-data" # link API buat kirim data ke website
send_interval = 60  # delay pengiriman data
last_send_time = 0 # variabel pembantu buat tracking udah berapa detik

# ===== Mekanisme buat konek ke port USB + serial monitor =====
try:
    ser = serial.Serial(COM_PORT, BAUD_RATE, timeout=2) # konekin
    print(f"Connected to {COM_PORT} at {BAUD_RATE} baud.") # status kalo berhasil
except Exception as e:
    print("Failed to connect to serial port:", e) # status kalo gagal
    exit()

# ===== Hal yang akan dilakukan oleh program sejak dia di-start (selama belum di-stop) =====
while True:
    # ===== Normal =====
    try:
        # ===== Baca data string dari serial monitor =====
        line = ser.readline().decode('utf-8').strip()
        if not line:
            continue

        print("Received:", line) # nge-print data yang diterima bair kitanya tau

        # ===== Ngubah data string ke bentuk JSON =====
        data = {}
        for part in line.split(','):
            key, value = part.split(':')
            data[key.strip()] = float(value.strip())

        # ===== Masukin API secret key (mekanisme security) =====
        data["secret"] = "VTS_Meowlynna-2312"

        # ===== Mekanisme ngirim data ke website setiap 5 detik sekali =====
        current_time = time.time() # buat ngetrack waktu berjalan

        # ===== Jika sudah 1 menit... kirim data =====
        if current_time - last_send_time >= send_interval:
            last_send_time = current_time # buat ngetrack waktu juga
            print("Sending:", data) # ngasih tau kalo data otw dikirim
            response = requests.post(URL, json=data) # ngirim data ke website
            print("POST Status:", response.status_code) # print status berhasil atau ngga-nya

    # ===== Kalo ada error atau program di-stop =====
    except KeyboardInterrupt:
        print("Stopped by user.")
        break
    except Exception as e:
        print("Error:", e)


# ===== Putus koneksi ke serial monitor kalo udah selesai =====
ser.close()