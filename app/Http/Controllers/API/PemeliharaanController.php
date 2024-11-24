<?php

namespace App\Http\Controllers\API;

use App\Helpers\ResponseFormatter;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\Kandang;
use App\Models\Pemeliharaan;
use App\Models\Pakan;
use App\Models\StockMovement;
use App\Models\Purchase;
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
        // Basic validation rules
        $rules = [
            'kandang_id' => 'required|exists:kandang,id',
            'purchase_id' => 'required|exists:purchases,id',
            'jumlah_ayam' => 'required|integer',
            'mati' => 'nullable|integer',
            'keterangan' => 'nullable|string',
        ];

        // Add conditional validation for pakan
        if ($request->has('jumlah_pakan') && $request->jumlah_pakan > 0) {
            $rules['jenis_pakan_id'] = 'required|exists:pakan,id';
            $rules['jumlah_pakan'] = 'required|integer|min:1';
        } else {
            $rules['jenis_pakan_id'] = 'nullable';
            $rules['jumlah_pakan'] = 'nullable';
        }

        $validator = Validator::make($request->all(), $rules);

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
                'purchase_id',
                'jumlah_ayam',
                'mati',
                'keterangan',
            ]);
            $purchase = Purchase::findOrFail($request->purchase_id);
            if ($purchase->kandang_id != $request->kandang_id) {
                throw new \Exception('Batch ini bukan milik kandang yang dipilih');
            }
            // Only add pakan-related data if jumlah_pakan is provided and greater than 0
            if ($request->has('jumlah_pakan') && $request->jumlah_pakan > 0) {
                $data['jenis_pakan_id'] = $request->jenis_pakan_id;
                $data['jumlah_pakan'] = $request->jumlah_pakan;
            }

            $kandang = Kandang::findOrFail($request->input('kandang_id'));
            $oldJumlahReal = $kandang->jumlah_real;

            if ($request->input('jumlah_ayam') > $kandang->kapasitas) {
                throw new \Exception('Jumlah ayam melebihi kapasitas kandang');
            }

            // Update Kandang jumlah_real
            $kandang->jumlah_real = $request->input('jumlah_ayam');
            $kandang->save();

            // Handle Pakan only if jumlah_pakan is provided and greater than 0
            if ($request->has('jumlah_pakan') && $request->jumlah_pakan > 0) {
                $pakan = Pakan::findOrFail($request->input('jenis_pakan_id'));
                $jumlahPakan = $request->input('jumlah_pakan');

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
                    'purchase_id' => $purchase->id,
                    'type' => $stockChange > 0 ? 'in' : 'out',
                    'quantity' => abs($stockChange),
                    'reason' => 'other',
                    'reference_id' => $pemeliharaan->id,
                    'reference_type' => 'App\Models\Pemeliharaan',
                    'notes' => "Perubahan jumlah ayam mati: {$request->input('mati', 0)}",
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
        // Basic validation rules
        $rules = [
            'kandang_id' => 'required|exists:kandang,id',
            'umur' => 'required|integer',
            'jumlah_ayam' => 'required|integer',
            'mati' => 'nullable|integer',
            'keterangan' => 'nullable|string',
        ];

        // Add conditional validation for pakan
        if ($request->has('jumlah_pakan') && $request->jumlah_pakan > 0) {
            $rules['jenis_pakan_id'] = 'required|exists:pakan,id';
            $rules['jumlah_pakan'] = 'required|integer|min:1';
        } else {
            $rules['jenis_pakan_id'] = 'nullable';
            $rules['jumlah_pakan'] = 'nullable';
        }

        $validator = Validator::make($request->all(), $rules);

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
                'umur',
                'jumlah_ayam',
                'mati',
                'keterangan',
            ]);

            // Only add pakan-related data if jumlah_pakan is provided and greater than 0
            if ($request->has('jumlah_pakan') && $request->jumlah_pakan > 0) {
                $data['jenis_pakan_id'] = $request->jenis_pakan_id;
                $data['jumlah_pakan'] = $request->jumlah_pakan;
            } else {
                $data['jenis_pakan_id'] = null;
                $data['jumlah_pakan'] = null;
            }

            $kandang = Kandang::findOrFail($request->input('kandang_id'));
            $oldJumlahReal = $kandang->jumlah_real;

            if ($request->input('jumlah_ayam') > $kandang->kapasitas) {
                throw new \Exception('Jumlah ayam melebihi kapasitas kandang');
            }

            // Update Kandang jumlah_real
            $kandang->jumlah_real = $request->input('jumlah_ayam');
            $kandang->save();

            // Handle Pakan
            if ($request->has('jumlah_pakan') && $request->jumlah_pakan > 0) {
                $pakan = Pakan::findOrFail($request->input('jenis_pakan_id'));
                $jumlahPakanBaru = $request->input('jumlah_pakan');
                $jumlahPakanLama = $pemeliharaan->jumlah_pakan ?? 0;

                // Kembalikan stok lama
                if ($jumlahPakanLama > 0) {
                    $pakan->sisa += $jumlahPakanLama;
                }

                // Cek dan kurangi stok baru
                if ($pakan->sisa < $jumlahPakanBaru) {
                    throw new \Exception('Stok pakan tidak mencukupi');
                }
                $pakan->sisa -= $jumlahPakanBaru;
                $pakan->save();
            } else if ($pemeliharaan->jumlah_pakan > 0) {
                // Jika sebelumnya ada pakan dan sekarang dihapus, kembalikan stok
                $pakan = Pakan::findOrFail($pemeliharaan->jenis_pakan_id);
                $pakan->sisa += $pemeliharaan->jumlah_pakan;
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
                    'reason' => 'other',
                    'reference_id' => $pemeliharaan->id,
                    'reference_type' => 'App\Models\Pemeliharaan',
                    'notes' => "Perubahan jumlah ayam mati: {$request->input('mati', 0)}",
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
