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
                $query->whereHas('wallet', function ($q) use ($request) {
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

            $this->checkGoalExceeded($request->user(), $transaction);

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

            $this->checkGoalExceeded($request->user(), $transaction);

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

    public function export(Request $request)
    {
        try {
            $query = $request->user()->transactions()->with(['category', 'wallet']);

            // Áp dụng các bộ lọc tương tự index nếu có truyền lên
            if ($request->has('wallet_id')) {
                $query->where('wallet_id', $request->wallet_id);
            }
            if ($request->has('category_id')) {
                $query->where('category_id', $request->category_id);
            }
            if ($request->has('wallet_name')) {
                $query->whereHas('wallet', function ($q) use ($request) {
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

            $transactions = $query->orderBy('transaction_date', 'desc')->get();

            $totalIncome = $transactions->where('type', 'income')->sum('amount');
            $totalExpense = $transactions->where('type', 'expense')->sum('amount');
            $netAmount = $totalIncome - $totalExpense;

            $headers = [
                "Content-type"        => "text/csv; charset=UTF-8",
                "Content-Disposition" => "attachment; filename=transactions_export.csv",
                "Pragma"              => "no-cache",
                "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
                "Expires"             => "0"
            ];

            $callback = function() use ($transactions, $totalIncome, $totalExpense, $netAmount) {
                $file = fopen('php://output', 'w');
                // Thêm BOM để Excel đọc đúng UTF-8
                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));

                fputcsv($file, ['Ngày', 'Danh mục', 'Ví', 'Loại', 'Số tiền', 'Mô tả']);

                foreach ($transactions as $transaction) {
                    fputcsv($file, [
                        $transaction->transaction_date,
                        $transaction->category ? $transaction->category->name : 'N/A',
                        $transaction->wallet ? $transaction->wallet->name : 'N/A',
                        $transaction->type === 'income' ? 'Thu nhập' : 'Chi tiêu',
                        $transaction->amount,
                        $transaction->description ?? ''
                    ]);
                }

                // Dòng phân cách
                fputcsv($file, []);
                fputcsv($file, ['--- THỐNG KÊ TỔNG QUAN ---']);
                fputcsv($file, ['Tổng thu nhập', $totalIncome]);
                fputcsv($file, ['Tổng chi tiêu', $totalExpense]);
                fputcsv($file, ['Số dư (Thu - Chi)', $netAmount]);

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Đã có lỗi xảy ra khi xuất file CSV',
                'error_detail' => $e->getMessage()
            ], 500);
        }
    }

    public function sendExportMail(Request $request)
    {
        try {
            $query = $request->user()->transactions()->with(['category', 'wallet']);

            if ($request->has('wallet_id')) {
                $query->where('wallet_id', $request->wallet_id);
            }
            if ($request->has('category_id')) {
                $query->where('category_id', $request->category_id);
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

            $transactions = $query->orderBy('transaction_date', 'desc')->get();

            $totalIncome = $transactions->where('type', 'income')->sum('amount');
            $totalExpense = $transactions->where('type', 'expense')->sum('amount');
            $netAmount = $totalIncome - $totalExpense;

            // Generate CSV to string memory
            $handle = fopen('php://temp', 'r+');
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, ['Ngày', 'Danh mục', 'Ví', 'Loại', 'Số tiền', 'Mô tả']);

            foreach ($transactions as $transaction) {
                fputcsv($handle, [
                    $transaction->transaction_date,
                    $transaction->category ? $transaction->category->name : 'N/A',
                    $transaction->wallet ? $transaction->wallet->name : 'N/A',
                    $transaction->type === 'income' ? 'Thu nhập' : 'Chi tiêu',
                    $transaction->amount,
                    $transaction->description ?? ''
                ]);
            }

            fputcsv($handle, []);
            fputcsv($handle, ['--- THỐNG KÊ TỔNG QUAN ---']);
            fputcsv($handle, ['Tổng thu nhập', $totalIncome]);
            fputcsv($handle, ['Tổng chi tiêu', $totalExpense]);
            fputcsv($handle, ['Số dư (Thu - Chi)', $netAmount]);

            rewind($handle);
            $csvContent = stream_get_contents($handle);
            fclose($handle);

            // Send Mail
            \Illuminate\Support\Facades\Mail::to($request->user()->email)
                ->send(new \App\Mail\TransactionReportMail($request->user(), $csvContent));

            return response()->json([
                'message' => 'Đã gửi báo cáo về email thành công',
                'data' => null
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Đã có lỗi xảy ra khi gửi email',
                'error_detail' => $e->getMessage()
            ], 500);
        }
    }

    private function checkGoalExceeded($user, $transaction)
    {
        if ($transaction->type !== 'expense') {
            return;
        }

        $goals = $user->goals()
            ->where('start_date', '<=', $transaction->transaction_date)
            ->where('end_date', '>=', $transaction->transaction_date)
            ->where(function ($query) use ($transaction) {
                $query->whereNull('category_id')
                      ->orWhere('category_id', $transaction->category_id);
            })
            ->where('is_exceeded_notified', false) // Only un-notified
            ->with('category')
            ->get();

        foreach ($goals as $goal) {
            $spentQuery = $user->transactions()
                ->where('type', 'expense')
                ->whereBetween('transaction_date', [$goal->start_date, $goal->end_date]);
                
            if ($goal->category_id) {
                $spentQuery->where('category_id', $goal->category_id);
            }
            
            $spentAmount = $spentQuery->sum('amount');
            
            if ($spentAmount > $goal->amount) {
                $categoryName = $goal->category ? $goal->category->name : 'Tổng hợp';
                $message = "Cảnh báo: Chi tiêu {$categoryName} của bạn đã vượt hạn mức. Hãy tiết kiệm lại nhé!";
                
                $user->custom_notifications()->create([
                    'message' => $message,
                    'is_read' => false,
                ]);

                $goal->update(['is_exceeded_notified' => true]);
            }
        }
    }
}
