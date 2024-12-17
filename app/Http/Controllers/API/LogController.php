<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Spatie\Activitylog\Models\Activity;

class LogController extends Controller
{
    public function getActivityLogs(Request $request)
    {
        try {
            $logs = Activity::with('causer')
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return ResponseFormatter::success(
                $logs,
                'Data log aktivitas berhasil diambil'
            );
        } catch (\Exception $e) {
            return ResponseFormatter::error(
                null,
                'Terjadi kesalahan saat mengambil data log aktivitas: ' . $e->getMessage(),
                500
            );
        }
    }
}
