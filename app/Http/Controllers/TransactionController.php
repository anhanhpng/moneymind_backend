<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = $request->user()->transactions()->with(['category', 'wallet']);

            if ($request->has('wallet_id')) {
                $query->where('wallet_id', $request->wallet_id);
            }
            
            if ($request->has('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            if ($request->has('wallet_name')) {
                $query->whereHas('wallet', function($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->wallet_name . '%');
                });
            }

            if ($request->has('type')) {
                $query->where('type', $request->type);
            }

            if ($request->has('from_date')) {
                $query->whereDate('transaction_date', '>=', $request->from_date);
            }

            if ($request->has('to_date')) {
                $query->whereDate('transaction_date', '<=', $request->to_date);
            }

            return response()->json([
                'message' => 'Lấy danh sách giao dịch thành công',
                'data' => $query->orderBy('transaction_date', 'desc')->paginate(15)
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Đã có lỗi xảy ra khi lấy danh sách giao dịch',
                'error_detail' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'wallet_id' => 'required|exists:wallets,id',
            'category_id' => 'required|exists:categories,id',
            'amount' => 'required|numeric|min:0.01',
            'type' => 'required|in:income,expense',
            'transaction_date' => 'required|date',
            'description' => 'nullable|string'
        ]);

        DB::beginTransaction();
        try {
            $wallet = Wallet::where('id', $request->wallet_id)->where('user_id', $request->user()->id)->firstOrFail();
            $transaction = $request->user()->transactions()->create($request->all());

            if ($transaction->type === 'income') {
                $wallet->balance += $transaction->amount;
            } else {
                $wallet->balance -= $transaction->amount;
            }
            $wallet->save();

            DB::commit();
            return response()->json([
                'message' => 'Thêm mới giao dịch thành công',
                'data' => $transaction->load(['category', 'wallet'])
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Đã có lỗi xảy ra khi tạo giao dịch',
                'error_detail' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Request $request, Transaction $transaction)
    {
        if ($transaction->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Bạn không có quyền truy cập dữ liệu này'], 403);
        }
        
        return response()->json([
            'message' => 'Lấy thông tin giao dịch thành công',
            'data' => $transaction->load(['category', 'wallet'])
        ], 200);
    }

    public function update(Request $request, Transaction $transaction)
    {
        if ($transaction->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Bạn không có quyền truy cập dữ liệu này'], 403);
        }

        $request->validate([
            'wallet_id' => 'exists:wallets,id',
            'category_id' => 'exists:categories,id',
            'amount' => 'numeric|min:0.01',
            'type' => 'in:income,expense',
            'transaction_date' => 'date',
            'description' => 'nullable|string'
        ]);

        DB::beginTransaction();
        try {
            // Revert old transaction effect on wallet
            $oldWallet = Wallet::find($transaction->wallet_id);
            if ($transaction->type === 'income') {
                $oldWallet->balance -= $transaction->amount;
            } else {
                $oldWallet->balance += $transaction->amount;
            }
            $oldWallet->save();

            $transaction->update($request->all());

            // Apply new transaction effect on wallet
            $newWallet = Wallet::find($transaction->wallet_id);
            if ($transaction->type === 'income') {
                $newWallet->balance += $transaction->amount;
            } else {
                $newWallet->balance -= $transaction->amount;
            }
            $newWallet->save();

            DB::commit();
            
            return response()->json([
                'message' => 'Cập nhật giao dịch thành công',
                'data' => $transaction->load(['category', 'wallet'])
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Đã có lỗi xảy ra khi cập nhật giao dịch',
                'error_detail' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, Transaction $transaction)
    {
        if ($transaction->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Bạn không có quyền truy cập dữ liệu này'], 403);
        }

        DB::beginTransaction();
        try {
            $wallet = Wallet::find($transaction->wallet_id);
            if ($transaction->type === 'income') {
                $wallet->balance -= $transaction->amount;
            } else {
                $wallet->balance += $transaction->amount;
            }
            $wallet->save();

            $transaction->delete();

            DB::commit();
            return response()->json([
                'message' => 'Xoá giao dịch thành công',
                'data' => null
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Đã có lỗi xảy ra khi xoá giao dịch',
                'error_detail' => $e->getMessage()
            ], 500);
        }
    }
}
