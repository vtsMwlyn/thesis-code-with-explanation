<?php

namespace App\Http\Controllers;

use App\Helpers\KNN;
use App\Models\GarbageDetection;
use App\Models\Warning;
use App\Models\WaterQuality;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class APIController extends Controller
{
    // ===== HANDLE PENGIRIMAN DATA SENSOR ===== //
    public function store_sensor_data(Request $request){
        // FLOW KALO NORMAL
        try {
            // Jalankan ini semua jika device yang ngirim datanya juga ngirim API secret key
            if($request->secret == 'VTS_Meowlynna-2312'){
                // Make sure data ga masuk database dulu kalo ada error
                DB::beginTransaction();

                // Retrieve data dari pengirim
                $temp = $request->temp;
                $ph = $request->ph;
                $turbidity = $request->turbidity;
                $tds = $request->tds;
                $location = $request->location ?? null;

                
                // ===== CARI PARAMETER KUALITAS AIR YANG KIRA-KIRA BIKIN KUALITAS AIR JADI JELEK =====

                // Buat nyimpen impostornya
                $out_of_standards = [];

                // Per item cek satu-satu, kalo keluar dari standar WHO, masukin ke list impostor
                if($temp < 12 || $temp > 25){
                    $out_of_standards[] = 'suhu';
                }

                if($ph < 6.5 || $ph > 8.5){
                    $out_of_standards[] = 'pH';
                }

                if($turbidity < 1 || $turbidity > 5){
                    $out_of_standards[] = 'tingkat kekeruhan';
                }

                if($tds > 600){
                    $out_of_standards[] = 'jumlah padatan terlarut';
                }

                // Text formatting biar nanti bentuknya jadi string dan dipisah koma buat ditampilin ke dashboard
                $sus_parameters = '';

                if (count($out_of_standards) > 1) {
                    $lastItem = array_pop($out_of_standards);
                    $sus_parameters = implode(', ', $out_of_standards) . ', dan ' . $lastItem;
                } else {
                    $sus_parameters = implode('', $out_of_standards);
                }

                // ===== PANGGIL KNN BUAT KLASIFIKASI KUALITAS AIR
                $quality = KNN::predict($temp, $ph, $turbidity, $tds);

                // ===== MEKANISME GENERATE WARNING ===== //

                // Ambil data water quality terakhir
                $latest_sensor_data = WaterQuality::latest()->first();

                // Kalo awalnya excellent, very good, atau good berarti kita generate warning. Kalo ngga yo ndak usah.
                if($latest_sensor_data && !in_array($latest_sensor_data?->quality, ['Bad', 'Very Bad']) && in_array($quality, ['Bad', 'Very Bad'])){
                    // Translate dulu ke bahasa Indonesia
                    $translated = '';

                    if($quality == 'Bad'){
                        $translated = 'Buruk';
                    } else if($quality == 'Very Bad'){
                        $translated = 'Sangat Buruk';
                    }

                    // Generate data warning-nya
                    Warning::create([
                        'date_and_time' => Carbon::now()->format('Y-m-d H:i:s'),
                        'message' => 'Terjadi penurunan kualitas air sungai di lokasi ' . $location . ' ke tingkat <strong>"' . $translated .'"</strong>. Beberapa parameter seperti <strong>' . $sus_parameters . '</strong> diduga menyebabkan penurunan.',
                        'category' => $quality,
                    ]);
                }

                // ===== SIMPEN DATA WATER QUALITY + PENGUKURAN SENSOR ===== //
                WaterQuality::create([
                    'date_and_time' => Carbon::now()->format('Y-m-d H:i:s'),
                    'location' => $location,

                    'temp' => $temp,
                    'ph' => $ph,
                    'turbidity' => $turbidity,
                    'tds' => $tds,

                    'quality' => $quality,
                ]);

                // Kalo udah sampe sini dan ga ada error baru operasi database-nya dilakuin
                DB::commit();

                // Kasih tau kalo proses kirim datanya berhasil
                return response()->json(['success' => true, 'message' => 'Successfully stored the sensor data!'], 200);
            }
            else {
                // Kalo dari awal ga ada API secret key, reject
                abort(401);
            }
        }

        // KALO GAGAL
        catch(Exception $e){
            // Kalo ada kegagalan selama proses, operasi database semuanya batalin
            DB::rollback();

            // Kasih tau kalo proses kirim datanya bermasalah
            return response()->json(['success' => false, 'message' => 'Error occured: ' . $e->getMessage()], 500);
        }
    }

    // ===== HANDLE PENGIRIMAN DATA DETEKSI ===== //
    public function store_detection_data(Request $request){
        // FLOW NORMAL
        try {
            // Lakukanlah hanya ketika yang ngirim data ngasih API secret key
            if($request->secret == 'VTS_Meowlynna-2312'){
                // Simpen foto yang dikirim
                $path = $request->file('image')->store('detections');

                // Simpen data deteksi ke database
                GarbageDetection::create([
                    'date_and_time' => $request->date_and_time,
                    'location' => $request->location ?? null,
                    'number' => $request->number,
                    'image_path' => $path,
                ]);

                // Kasih tau kalo berhasil
                return response()->json(['success' => true, 'message' => 'Successfully stored the detection data!'], 200);
            }
            else {
                // Reject kalo ga ada API secret key
                abort(401);
            }
        }

        // 
        catch(Exception $e){
            // Kasih tau kalo proses kirim datanya bermasalah
            return response()->json(['success' => false, 'message' => 'Error occured: ' . $e->getMessage()], 500);
        }
    }
}
