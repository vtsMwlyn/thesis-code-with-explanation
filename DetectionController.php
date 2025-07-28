<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\GarbageDetection;
use Illuminate\Support\Facades\Storage;

class DetectionController extends Controller
{
    public function index(){
        // Ambil data mentahan untuk deteksi sampah (no filter default-nya 24 jam terakhir)
        $recent_detections = GarbageDetection::where('date_and_time', '>=', Carbon::now()->subDay())->orderBy('date_and_time', 'asc')->get();

        // Kalo ada filter, pake filter
        if(request('date') || request('starttime') || request('endtime')){
            $recent_detections = GarbageDetection::filter(request(['date', 'starttime', 'endtime']))->orderBy('date_and_time', 'asc')->get();
        }

        // Ambil timestamp buat grafik
        $labels = [];
        foreach($recent_detections->pluck('date_and_time')->toArray() as $raw_date_time){
            $labels[] = Carbon::parse($raw_date_time)->format('H:i');
        }

        // Ambil data jumlah sampahnya aja buat grafik
        $garbage_detected = $recent_detections->pluck('number')->toArray();

        // Render detection page + kasih datanya
        return view('pages.detection.index', [
            'all_detections' => $recent_detections,
            'labels' => $labels,
            'garbage_detected' => $garbage_detected,
        ]);
    }
}
