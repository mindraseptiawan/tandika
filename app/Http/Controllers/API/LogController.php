<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class LogController extends Controller
{
    public function getActivityLogs(Request $request)
    {
        try {
            $logs = Activity::with('causer')
                ->where('default', 'user_log') // Filter log berdasarkan nama log
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            // Transform data untuk menambahkan informasi yang lebih detail
            $transformedLogs = $logs->map(function ($log) {
                return [
                    'id' => $log->id,
                    'description' => $log->description,
                    'causer_name' => $log->causer ? $log->causer->name : 'System',
                    'causer_email' => $log->causer ? $log->causer->email : '',
                    'created_at' => $log->created_at->format('Y-m-d H:i:s'),
                    'log_name' => $log->log_name,
                    // Tambahkan properti lain yang diinginkan
                ];
            });

            // Rebuild pagination dengan data yang sudah ditransform
            $paginatedLogs = new \Illuminate\Pagination\LengthAwarePaginator(
                $transformedLogs,
                $logs->total(),
                $logs->perPage(),
                $logs->currentPage(),
                ['path' => request()->url()]
            );

            return ResponseFormatter::success(
                $paginatedLogs,
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
