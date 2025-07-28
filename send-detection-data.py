# ===== Library import =====
import requests
from datetime import datetime
import os
from ultralytics import YOLO
import cv2
import time

# ===== Mekanisme buat kirim data ke website (tapi dipanggil nanti) =====
def sendDataToWebsite(image_path, object_count):
    # ===== Ambil time stamp saat ini (waktu dan tanggal) terus diformat jadi yyyy-mm-dd hh:mm:ss
    now = datetime.now()
    formatted_datetime = now.strftime("%Y-%m-%d %H:%M:%S")

    # ===== Taro data yang mau dikirim ke JSON =====
    data = {
        "secret": "VTS_Meowlynna-2312",
        "date_and_time": formatted_datetime,
        "number": object_count,
    }

    # ===== Kita mau ngirim datanya barengan sama gambar... =====
    with open(image_path, "rb") as img_file:

        # ===== Kita tambahin file-nya sebagai bagian dari data yang mau dikirim =====
        files = {
            "image": img_file
        }

        # ===== Kirim ke website =====
        response = requests.post(url, data=data, files=files)

        # ===== Print status berhasil atau gagalnya =====
        if response.status_code == 200:
            print("Request successful!")
            print(response.json())
        else:
            print(f"Request failed with status code {response.status_code}")
            print(response.text)


# ===== Konfigurasi =====
model_path = './models/datasetv1_200epoch.pt' # model yang udah di-train-nya ditaro di mana
url = "https://thesis.esage.site/public/api/send-detection-data" # link API buat kirim data ke website
threshold = 0.5 # skor minimal buat penentu ada deteksi atau ngga
send_interval = 60 # setiap berapa detik sekali data dikirim

output_dir = 'detected-frames' # nama folder buat save frame yang udah digambar and otw dikirim website
os.makedirs(output_dir, exist_ok=True) # kalo belum ada foldernya, bikin dulu

# ===== Load model & kamera =====
model = YOLO(model_path) # kita setel YOLO buat pake model yang udah di-training punya kita buat nanti ngedeteksi
cap = cv2.VideoCapture(0) # konek ke webcam

# ===== Frame reading =====
ret, frame = cap.read() # ambil data frame awal dari webcam
H, W, _ = frame.shape # ambil resolusi gambar

last_send_time = time.time() # buat tracking udah detik ke-berapa

# ===== Hal yang bakal dilakuin program sejak dia di-start =====
while ret:
    # ===== Ambil frame saat ini =====
    results = model(frame)[0]

    # ===== Hitung ada berapa sampah kedeteksi =====
    object_count = sum(1 for result in results.boxes.data.tolist() if result[4] > threshold)

    # ===== Naro bounding box sama keterangannya di frame =====
    for idx, result in enumerate(results.boxes.data.tolist()):
        x1, y1, x2, y2, score, class_id = result # data frame dipecah jadi koordinat deteksi, skor, sama class deteksi apa

        # ===== Dilakukan jika skornya melebihi threshold minimum =====
        if score > threshold:

            # ===== Gambar kotak bounding box-nya (dari koordinat x1, y1 sampe x2, y2; warna hijau, sama tebel) =====
            cv2.rectangle(frame, (int(x1), int(y1)), (int(x2), int(y2)), (0, 255, 0), 2)

            # ===== Kasih teks keterangan (yang kayak "Garbage 69%"), disetel buat ditaro di atas frame, font, warna hijau, garis solid, tebelnya juga =====
            cv2.putText(
                frame,
                f"{results.names[int(class_id)]} {score * 100:.2f}%",
                (int(x1), int(y1 - 10)),
                cv2.FONT_HERSHEY_SIMPLEX,
                0.6,
                (0, 255, 0),
                2,
                cv2.LINE_AA
            )


    current_time = time.time() # again buat bantu tracking udah berapa detik

    # ===== Kalo udah 1 menit... =====
    if current_time - last_send_time >= send_interval:
        last_send_time = current_time # again buat tracking udah berapa detik
        output_path = os.path.join(output_dir, f"frame_{int(time.time())}.jpg") # spesifikasiin nama gambar + lokasi ditaronya di mana
        cv2.imwrite(output_path, frame) # save gambarnya ke sana

        sendDataToWebsite(output_path, object_count) # finally, kirim datanya ke website (isi kodenya cek lagi di atas)


    # ===== Nunjukin hasil stream + deteksi ke window baru =====
    cv2.imshow("Detection Result", frame)

    # ===== Nunggu perintah buat berhentiin program (Esc key) =====
    k = cv2.waitKey(5) & 0xFF
    if k == 27:
        break

    # ===== Lanjut ke frame berikutnya =====
    ret, frame = cap.read()


# ===== Pas programnya udah berhenti =====
cap.release() # putus koneksi ke webcam
cv2.destroyAllWindows() # tutup window
