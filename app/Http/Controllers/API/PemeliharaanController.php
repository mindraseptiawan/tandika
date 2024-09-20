<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use App\Models\Kandang;
use App\Models\Pemeliharaan;
use App\Models\Pakan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PemeliharaanController extends Controller
{
    // Mengambil semua data pemeliharaan atau berdasarkan kandang_id
    public function all(Request $request, $kandang_id = null)
    {
        $kandang_id = $request->input('kandang_id', $kandang_id);

        if ($kandang_id) {
            $kandang = Kandang::find($kandang_id);

            if (!$kandang) {
                return ResponseFormatter::error(
                    null,
                    'Data Kandang tidak ditemukan',
                    404
                );
            }

            $pemeliharaans = $kandang->pemeliharaans()->paginate(100);

            return ResponseFormatter::success(
                $pemeliharaans,
                'Data Pemeliharaan Kandang berhasil diambil'
            );
        } else {
            $pemeliharaans = Pemeliharaan::paginate(100);

            return ResponseFormatter::success(
                $pemeliharaans,
                'Data Pemeliharaan berhasil diambil'
            );
        }
    }


    // Membuat data pemeliharaan baru
    public function create(Request $request)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'kandang_id' => 'required|exists:kandang,id',
            'jenis_pakan_id' => 'required|exists:pakan,id',
            'umur' => 'required|integer',
            'jumlah_ayam' => 'required|integer',
            'jumlah_pakan' => 'nullable|integer|min:1',
            'sisa' => 'nullable|integer',
            'mati' => 'nullable|integer',
            'keterangan' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error(
                $validator->errors(),
                'Validation Error',
                422
            );
        }

        // Ambil data dari request
        $data = $request->only([
            'kandang_id',
            'jenis_pakan_id',
            'umur',
            'jumlah_ayam',
            'jumlah_pakan',
            'sisa',
            'mati',
            'keterangan',
        ]);

        // Update jumlah real ayam di kandang sesuai jumlah baru dari pemeliharaan
        $kandang = Kandang::find($request->input('kandang_id'));

        if ($kandang) {
            // Cek apakah jumlah ayam yang baru melebihi kapasitas kandang
            if ($request->input('jumlah_ayam') > $kandang->kapasitas) {
                return ResponseFormatter::error(
                    'Jumlah ayam melebihi kapasitas kandang',
                    'Error',
                    400
                );
            }

            // Update jumlah real ayam di kandang sesuai jumlah ayam pada pemeliharaan
            $kandang->jumlah_real = $request->input('jumlah_ayam');
            $kandang->save();
        }

        // Update stok pakan
        $pakan = Pakan::find($request->input('jenis_pakan_id'));
        $jumlahPakan = $request->input('jumlah_pakan', 0);

        if ($pakan && $jumlahPakan > 0) {
            if ($pakan->sisa < $jumlahPakan) {
                return ResponseFormatter::error(
                    'Stok pakan tidak mencukupi',
                    'Error',
                    400
                );
            }
            $pakan->sisa -= $jumlahPakan;
            $pakan->save();
        }

        // Buat data pemeliharaan baru
        $pemeliharaan = Pemeliharaan::create($data);

        return ResponseFormatter::success(
            $pemeliharaan,
            'Data Pemeliharaan berhasil ditambahkan'
        );
    }




    // Mengupdate data pemeliharaan
    public function update(Request $request, $id)
    {
        // Validasi input
        $validator = Validator::make($request->all(), [
            'kandang_id' => 'required|exists:kandangs,id',
            'jenis_pakan_id' => 'required|exists:pakans,id',
            'umur' => 'required|integer',
            'jumlah_ayam' => 'required|integer',
            'jumlah_pakan' => 'nullable|integer|min:1',
            'sisa' => 'nullable|integer',
            'mati' => 'nullable|integer',
            'keterangan' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return ResponseFormatter::error(
                $validator->errors(),
                'Validation Error',
                422
            );
        }

        // Ambil data pemeliharaan berdasarkan ID
        $pemeliharaan = Pemeliharaan::findOrFail($id);
        $data = $request->only([
            'kandang_id',
            'jenis_pakan_id',
            'umur',
            'jumlah_ayam',
            'jumlah_pakan',
            'sisa',
            'mati',
            'keterangan',
        ]);

        // Update jumlah real ayam di kandang sesuai jumlah baru dari pemeliharaan
        $kandang = Kandang::find($request->input('kandang_id'));
        if ($kandang) {
            // Cek apakah jumlah ayam yang baru melebihi kapasitas kandang
            if ($request->input('jumlah_ayam') > $kandang->kapasitas) {
                return ResponseFormatter::error(
                    'Jumlah ayam melebihi kapasitas kandang',
                    'Error',
                    400
                );
            }

            // Update jumlah real ayam di kandang sesuai jumlah ayam pada pemeliharaan
            $kandang->jumlah_real = $request->input('jumlah_ayam');
            $kandang->save();
        }

        // Update stok pakan
        $pakan = Pakan::find($request->input('jenis_pakan_id'));
        $jumlahPakanBaru = $request->input('jumlah_pakan', 0);
        $jumlahPakanLama = $pemeliharaan->jumlah_pakan;

        if ($pakan && $jumlahPakanBaru > 0) {
            // Tambahkan stok pakan lama kembali sebelum pengurangan baru
            $pakan->sisa += $jumlahPakanLama;

            // Periksa apakah stok pakan mencukupi setelah perubahan
            if ($pakan->sisa < $jumlahPakanBaru) {
                return ResponseFormatter::error(
                    'Stok pakan tidak mencukupi',
                    'Error',
                    400
                );
            }

            // Kurangi stok pakan dengan jumlah yang baru
            $pakan->sisa -= $jumlahPakanBaru;
            $pakan->save();
        }

        // Update data pemeliharaan
        $pemeliharaan->update($data);

        return ResponseFormatter::success(
            $pemeliharaan,
            'Data Pemeliharaan berhasil diperbarui'
        );
    }




    // Menghapus data pemeliharaan
    public function destroy($id)
    {
        $pemeliharaan = Pemeliharaan::find($id);

        if (!$pemeliharaan) {
            return ResponseFormatter::error(
                null,
                'Data Pemeliharaan tidak ditemukan',
                404
            );
        }

        $pemeliharaan->delete();

        return ResponseFormatter::success(
            null,
            'Data Pemeliharaan berhasil dihapus'
        );
    }


    // Mengambil data pemeliharaan berdasarkan ID
    public function show($id)
    {
        $pemeliharaan = Pemeliharaan::find($id);

        if (!$pemeliharaan) {
            return ResponseFormatter::error(
                null,
                'Data Pemeliharaan tidak ditemukan',
                404
            );
        }

        return ResponseFormatter::success(
            $pemeliharaan,
            'Data Pemeliharaan berhasil diambil'
        );
    }
}
