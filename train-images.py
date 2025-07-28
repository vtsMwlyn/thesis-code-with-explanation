# Import library
from ultralytics import YOLO

# Pilih modeal yang mau dipake
model = YOLO("yolov8n.pt")

# Training
results = model.train(data='./config.yaml', epochs=200)