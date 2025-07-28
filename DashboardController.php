<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\Warning;
use App\Models\WaterQuality;
use Illuminate\Http\Request;
use App\Models\GarbageDetection;
use Illuminate\Support\Facades\Storage;

class DashboardController extends Controller
{
    // Render page dashboard
    public function index(){
        return view('dashboard');
    }

    // Ngasih datanya doang
    public function latest_data(){
        // Ambil data pengukuran sensor, kualitas, dan deteksi terakhir
        $recent_sensor_data = WaterQuality::latest()->first();
        $recent_warning = Warning::orderBy('date_and_time', 'desc')->get();
        $recent_detection = GarbageDetection::latest()->first();

        // Kirim datanya ke depan
        return response()->json([
            'latest_detection' => [
                'number' => $recent_detection->number,
                'photo' => Storage::url('app/public/' . $recent_detection->image_path)
            ],
            'all_warnings' => $recent_warning,
            'latest_sensor_data' => $recent_sensor_data,
        ]);
    }
}
