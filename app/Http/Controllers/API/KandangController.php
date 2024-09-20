<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Kandang;

class KandangController extends Controller
{
    public function all(Request $request)
    {
        $id = $request->input('id');
        $limit = $request->input('limit', 10);
        $nama_kandang = $request->input('nama_kandang');
        $operator = $request->input('operator');
        $kapasitas = $request->input('kapasitas');
        $lokasi = $request->input('lokasi');
        $status = $request->input('status');

        if ($id) {
            $kandang = Kandang::find($id);

            if ($kandang) {
                return ResponseFormatter::success(
                    $kandang,
                    'Data Kandang Berhasil di ambil'
                );
            } else {
                return ResponseFormatter::error(
                    null,
                    'Data Kandang tidak ada',
                    404
                );
            }
        }

        $kandang = Kandang::query();

        if ($nama_kandang) {
            $kandang->where('nama_kandang', 'like', '%' . $nama_kandang . '%');
        }
        if ($operator) {
            $kandang->where('operator', 'like', '%' . $operator . '%');
        }
        if ($kapasitas) {
            $kandang->where('kapasitas', '=', $kapasitas);
        }
        if ($lokasi) {
            $kandang->where('lokasi', 'like', '%' . $lokasi . '%');
        }
        if ($status !== null) {
            $kandang->where('status', (bool) $status);
        }

        return ResponseFormatter::success(
            $kandang->paginate($limit),
            'Data Kandang Berhasil di ambil'
        );
    }

    public function store(Request $request)
    {
        // Validasi data input
        $validatedData = $request->validate([
            'nama_kandang' => 'required|string|max:255|unique:kandang',
            'operator' => 'required|string|max:255',
            'kapasitas' => 'required|integer',
            'lokasi' => 'required|string|max:255',
            'status' => 'required|boolean',
            'jumlah_real' => 'nullable|integer',
        ]);

        // Simpan data ke tabel kandang
        $kandang = Kandang::create($validatedData);

        return ResponseFormatter::success(
            $kandang,
            'Data Kandang berhasil ditambahkan'
        );
    }

    public function update(Request $request, $id)
    {

        // Validasi data input
        $validatedData = $request->validate([
            'nama_kandang' => 'string|max:255|unique:kandang,nama_kandang,' . $id,
            'operator' => 'string|max:255',
            'kapasitas' => 'integer',
            'lokasi' => 'string|max:255',
            'status' => 'boolean',
            'jumlah_real' => 'nullable|integer',
        ]);

        // Cari data kandang berdasarkan ID
        $kandang = Kandang::find($id);

        if (!$kandang) {
            return ResponseFormatter::error(
                null,
                'Data Kandang tidak ada',
                404
            );
        }

        // Update data kandang
        $kandang->update($validatedData);

        return ResponseFormatter::success(
            $kandang,
            'Data Kandang berhasil diupdate'
        );
    }

    public function destroy($id)
    {
        $kandang = Kandang::withTrashed()->find($id);

        if ($kandang) {
            $kandang->forceDelete();
            return response()->json([
                'status' => 'success',
                'message' => 'Data Kandang berhasil dihapus secara permanen'
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Data Kandang tidak ditemukan'
        ], 404);
    }

    public function show($id)
    {
        $kandang = Kandang::find($id);

        if (!$kandang) {
            return response()->json([
                'meta' => [
                    'code' => 404,
                    'status' => 'error',
                    'message' => 'Data Kandang tidak ditemukan'
                ],
                'data' => null
            ]);
        }

        return response()->json([
            'meta' => [
                'code' => 200,
                'status' => 'success',
                'message' => 'Data Kandang Berhasil diambil'
            ],
            'data' => $kandang
        ]);
    }
}
