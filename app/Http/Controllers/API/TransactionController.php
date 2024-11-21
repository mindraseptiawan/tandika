<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\Cashflow;
use App\Helpers\ResponseFormatter;

class TransactionController extends Controller
{
    public function all()
    {

        $transaction = Transaction::with('user')->get();

        return ResponseFormatter::success(
            $transaction,
            'Data transaction berhasil diambil'
        );
    }
    public function laporan()
    {

        $transaction = Transaction::with('user')->get();

        return ResponseFormatter::success(
            $transaction,
            'Data transaction berhasil diambil'
        );
    }
    public function create(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'type' => 'required|string|in:purchase,sale,salary',
            'amount' => 'required|numeric',
            'keterangan' => 'nullable|string'
        ]);

        $transaction = Transaction::create($request->all());

        $previousBalance = Cashflow::latest()->first()->balance ?? 0;

        if ($request->type == 'salary') {
            // Create a new Cashflow record for salary
            Cashflow::create([
                'transaction_id' => $transaction->id,
                'type' => 'out',
                'amount' => $transaction->amount,
                'balance' => $previousBalance - $transaction->amount,
            ]);
        } elseif ($request->type == 'purchase') {
            // Create a new Cashflow record for purchase
            Cashflow::create([
                'transaction_id' => $transaction->id,
                'type' => 'out',
                'amount' => $transaction->amount,
                'balance' => $previousBalance - $transaction->amount,
            ]);
        } elseif ($request->type == 'sale') {
            // Create a new Cashflow record for sale
            Cashflow::create([
                'transaction_id' => $transaction->id,
                'type' => 'in',
                'amount' => $transaction->amount,
                'balance' => $previousBalance + $transaction->amount,
            ]);
        }

        return ResponseFormatter::success(
            $transaction,
            'Data transaction berhasil ditambahkan'
        );
    }

    public function update($id, Request $request)
    {
        $request->validate([
            'type' => 'sometimes|required|string|in:purchase,sale,salary',
            'amount' => 'sometimes|required|numeric',
            'keterangan' => 'sometimes|nullable|string'
        ]);

        $transaction = Transaction::findOrFail($id);
        $oldAmount = $transaction->amount;
        $transaction->update($request->only(['amount', 'keterangan']));

        $previousBalance = Cashflow::latest()->first()->balance ?? 0;
        $diffAmount = $transaction->amount - $oldAmount;

        if ($transaction->type == 'salary') {
            // Update Cashflow record for salary
            $cashFlow = Cashflow::where('transaction_id', $id)->first();
            if ($cashFlow) {
                $cashFlow->update([
                    'amount' => $transaction->amount,
                    'balance' => $previousBalance - $diffAmount,
                ]);
            } else {
                Cashflow::create([
                    'transaction_id' => $transaction->id,
                    'type' => 'out',
                    'amount' => $transaction->amount,
                    'balance' => $previousBalance - $transaction->amount,
                ]);
            }
        } elseif ($transaction->type == 'purchase') {
            // Update Cashflow record for purchase
            $cashFlow = Cashflow::where('transaction_id', $id)->first();
            if ($cashFlow) {
                $cashFlow->update([
                    'amount' => $transaction->amount,
                    'balance' => $previousBalance - $diffAmount,
                ]);
            } else {
                Cashflow::create([
                    'transaction_id' => $transaction->id,
                    'type' => 'out',
                    'amount' => $transaction->amount,
                    'balance' => $previousBalance - $transaction->amount,
                ]);
            }
        } elseif ($transaction->type == 'sale') {
            // Update Cashflow record for sale
            $cashFlow = Cashflow::where('transaction_id', $id)->first();
            if ($cashFlow) {
                $cashFlow->update([
                    'amount' => $transaction->amount,
                    'balance' => $previousBalance + $diffAmount,
                ]);
            } else {
                Cashflow::create([
                    'transaction_id' => $transaction->id,
                    'type' => 'in',
                    'amount' => $transaction->amount,
                    'balance' => $previousBalance + $transaction->amount,
                ]);
            }
        }

        // Update semua data transaksi yang terkait dengan perubahan balance
        $transactions = Transaction::where('id', '>', $id)->get();
        foreach ($transactions as $t) {
            $cashFlow = Cashflow::where('transaction_id', $t->id)->first();
            if ($cashFlow) {
                if ($t->type == 'salary' || $t->type == 'purchase') {
                    $cashFlow->update([
                        'balance' => $cashFlow->balance - $diffAmount,
                    ]);
                } elseif ($t->type == 'sale') {
                    $cashFlow->update([
                        'balance' => $cashFlow->balance + $diffAmount,
                    ]);
                }
            }
        }

        return ResponseFormatter::success(
            $transaction,
            'Data transaction berhasil diupdate'
        );
    }

    public function destroy($id)
    {
        $transaction = Transaction::findOrFail($id);
        $transaction->delete();

        return response()->json(null, 204);
    }

    public function show($id)
    {
        $transaction = Transaction::findOrFail($id);

        return response()->json($transaction);
    }

    public function getTransactionsByType($type)
    {
        $transactions = Transaction::where('type', $type)->get();

        return response()->json($transactions);
    }
}
