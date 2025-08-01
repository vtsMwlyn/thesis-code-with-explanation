<?php

namespace App\Http\Controllers;

use App\Helpers\KNN;
use App\Models\WaterQuality;
use Carbon\Carbon;
use Illuminate\Http\Request;

class SensorController extends Controller
{
    public function index(){
        // Ambil data mentahan buat data sensor (kalo ga ada filter default-nya 24 jam terakhir)
        $recent_sensor_data = WaterQuality::where('date_and_time', '>=', Carbon::now()->subDay())->orderBy('date_and_time', 'asc')->get();

        // Kalo ada filter ya difilter
        if(request('date') || request('starttime') || request('endtime')){
            $recent_sensor_data = WaterQuality::filter(request(['date', 'starttime', 'endtime']))->orderBy('date_and_time', 'asc')->get();
        }

        // Ambil timestamp buat nanti ditaro di grafik
        $labels = [];
        foreach($recent_sensor_data->pluck('date_and_time')->toArray() as $raw_date_time){
            $labels[] = Carbon::parse($raw_date_time)->format('H:i');
        }

        // Karena parameter kualitas airnya dipisah jadi 4 grafik, si data mentahannya dipisah jadi 4 array
        $temperature = $recent_sensor_data->pluck('temp')->toArray();
        $ph = $recent_sensor_data->pluck('ph')->toArray();
        $turbidity = $recent_sensor_data->pluck('turbidity')->toArray();
        $tds = $recent_sensor_data->pluck('tds')->toArray();

        // Render sensor page sekaligus kirim datanya
        return view('pages.sensor.index', [
            'all_sensor_data' => $recent_sensor_data,

            'labels' => $labels,
            'temp' => $temperature,
            'ph' => $ph,
            'turbidity' => $turbidity,
            'tds' => $tds,
        ]);
    }
}
