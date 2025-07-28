<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\WaterQuality;
use Illuminate\Http\Request;
use App\Models\GarbageDetection;

class PolutionController extends Controller
{
    public function index(){
        // Ambil data deteksi and data sensor 24 jam terakhir by default and kalo ga ada filter
        $recent_sensor_data = WaterQuality::where('date_and_time', '>=', Carbon::now()->subDay())->orderBy('date_and_time', 'asc')->get();
        $recent_detections = GarbageDetection::where('date_and_time', '>=', Carbon::now()->subDay())->orderBy('date_and_time', 'asc')->get();

        // Kalo ada filter ya... di-filter
        if(request('date') || request('starttime') || request('endtime')){
            $recent_sensor_data = WaterQuality::filter(request(['date', 'starttime', 'endtime']))->orderBy('date_and_time', 'asc')->get();
            $recent_detections = GarbageDetection::filter(request(['date', 'starttime', 'endtime']))->orderBy('date_and_time', 'asc')->get();
        }

        // Ambil timestamp buat di-map ke grafik
        $labels = [];
        foreach($recent_sensor_data->pluck('date_and_time')->toArray() as $raw_date_time){
            $labels[] = Carbon::parse($raw_date_time)->format('H:i');
        }

        // Ekstrak jumlah deteksi sampahnya aja buat ditaro di grafik jumlah sampah
        $qualities = $recent_sensor_data->pluck('quality')->toArray();

        // Ekstrak pengukuran sensor buat di grafik parameter kualitas air (tetep dipisah jadi 4 karena beda garis data di grafik)
        $temperature = $recent_sensor_data->pluck('temp')->toArray();
        $ph = $recent_sensor_data->pluck('ph')->toArray();
        $turbidity = $recent_sensor_data->pluck('turbidity')->toArray();
        $tds = $recent_sensor_data->pluck('tds')->toArray();

        // Ambil data jumlah sampak kedeteksinya aja
        $garbage_detected = $recent_detections->pluck('number')->toArray();

        // Render polution page and kirim datanya juga
        return view('pages.polution.index', [
            'all_sensor_data' => $recent_sensor_data,
            'labels' => $labels,
            'qualities' => $qualities,
            'garbage_detected' => $garbage_detected,
            'temp' => $temperature,
            'ph' => $ph,
            'turbidity' => $turbidity,
            'tds' => $tds,
        ]);
    }
}
