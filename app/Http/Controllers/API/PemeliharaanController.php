<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\Kandang;
use App\Models\Pemeliharaan;
use App\Models\Pakan;
use App\Models\StockMovement;
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

        DB::beginTransaction();

        try {
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

            $kandang = Kandang::findOrFail($request->input('kandang_id'));
            $oldJumlahReal = $kandang->jumlah_real;

            if ($request->input('jumlah_ayam') > $kandang->kapasitas) {
                throw new \Exception('Jumlah ayam melebihi kapasitas kandang');
            }

            // Update Kandang jumlah_real
            $kandang->jumlah_real = $request->input('jumlah_ayam');
            $kandang->save();

            // Handle Pakan
            $pakan = Pakan::findOrFail($request->input('jenis_pakan_id'));
            $jumlahPakan = $request->input('jumlah_pakan', 0);

            if ($jumlahPakan > 0) {
                if ($pakan->sisa < $jumlahPakan) {
                    throw new \Exception('Stok pakan tidak mencukupi');
                }
                $pakan->sisa -= $jumlahPakan;
                $pakan->save();
            }

            // Create Pemeliharaan record
            $pemeliharaan = Pemeliharaan::create($data);

            // Create StockMovement for the change in chicken count
            $stockChange = $request->input('jumlah_ayam') - $oldJumlahReal;
            if ($stockChange != 0) {
                StockMovement::create([
                    'kandang_id' => $kandang->id,
                    'type' => $stockChange > 0 ? 'in' : 'out',
                    'quantity' => abs($stockChange),
                    'reason' => 'Pemeliharaan Create',
                    'reference_id' => $pemeliharaan->id,
                    'reference_type' => 'App\Models\Pemeliharaan',
                    'notes' => "Perubahan jumlah ayam dari pemeliharaan. Mati: {$request->input('mati', 0)}",
                ]);
            }

            DB::commit();

            return ResponseFormatter::success(
                $pemeliharaan,
                'Data Pemeliharaan berhasil ditambahkan'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseFormatter::error(
                null,
                'Terjadi kesalahan: ' . $e->getMessage(),
                500
            );
        }
    }

    public function update(Request $request, $id)
    {
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

        DB::beginTransaction();

        try {
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

            $kandang = Kandang::findOrFail($request->input('kandang_id'));
            $oldJumlahReal = $kandang->jumlah_real;

            if ($request->input('jumlah_ayam') > $kandang->kapasitas) {
                throw new \Exception('Jumlah ayam melebihi kapasitas kandang');
            }

            // Update Kandang jumlah_real
            $kandang->jumlah_real = $request->input('jumlah_ayam');
            $kandang->save();

            // Handle Pakan
            $pakan = Pakan::findOrFail($request->input('jenis_pakan_id'));
            $jumlahPakanBaru = $request->input('jumlah_pakan', 0);
            $jumlahPakanLama = $pemeliharaan->jumlah_pakan;

            if ($jumlahPakanBaru > 0) {
                $pakan->sisa += $jumlahPakanLama;
                if ($pakan->sisa < $jumlahPakanBaru) {
                    throw new \Exception('Stok pakan tidak mencukupi');
                }
                $pakan->sisa -= $jumlahPakanBaru;
                $pakan->save();
            }

            // Update Pemeliharaan record
            $pemeliharaan->update($data);

            // Create StockMovement for the change in chicken count
            $stockChange = $request->input('jumlah_ayam') - $oldJumlahReal;
            if ($stockChange != 0) {
                StockMovement::create([
                    'kandang_id' => $kandang->id,
                    'type' => $stockChange > 0 ? 'in' : 'out',
                    'quantity' => abs($stockChange),
                    'reason' => 'Pemeliharaan Update',
                    'reference_id' => $pemeliharaan->id,
                    'reference_type' => 'App\Models\Pemeliharaan',
                    'notes' => "Perubahan jumlah ayam dari pemeliharaan. Mati: {$request->input('mati', 0)}",
                ]);
            }

            DB::commit();

            return ResponseFormatter::success(
                $pemeliharaan,
                'Data Pemeliharaan berhasil diperbarui'
            );
        } catch (\Exception $e) {
            DB::rollBack();
            return ResponseFormatter::error(
                null,
                'Terjadi kesalahan: ' . $e->getMessage(),
                500
            );
        }
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
