<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Transaction;

class TransactionController extends Controller
{
    public function all()
    {
        return Transaction::all();
    }
    public function create(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'type' => 'required|string|in:purchase,sale',
            'amount' => 'required|numeric',
            'keterangan' => 'nullable|string'
        ]);

        $transaction = Transaction::create($request->all());

        return response()->json($transaction, 201);
    }

    public function update($id, Request $request)
    {
        $request->validate([
            'amount' => 'sometimes|required|numeric',
            'keterangan' => 'sometimes|nullable|string'
        ]);

        $transaction = Transaction::findOrFail($id);
        $transaction->update($request->only(['amount', 'keterangan']));

        return response()->json($transaction);
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
