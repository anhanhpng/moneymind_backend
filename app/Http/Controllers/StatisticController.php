<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use Carbon\Carbon;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

class StatisticController extends Controller
{
    public function spending(Request $request)
    {
        $type = $request->get('type', 'category'); // group by: category or wallet
        $period = $request->get('period', 'month'); // day, week, month
        $date = $request->get('date', Carbon::today()->format('Y-m-d'));
        $transactionType = $request->get('transaction_type', 'expense');

        $query = $request->user()->transactions()->where('type', $transactionType);

        if ($request->has('wallet_id')) {
            $query->where('wallet_id', $request->wallet_id);
        }
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $carbonDate = Carbon::parse($date);
        if ($period === 'day') {
            $query->whereDate('transaction_date', $carbonDate->format('Y-m-d'));
        } elseif ($period === 'week') {
            $query->whereBetween('transaction_date', [
                $carbonDate->copy()->startOfWeek()->format('Y-m-d'),
                $carbonDate->copy()->endOfWeek()->format('Y-m-d')
            ]);
        } else {
            $query->whereMonth('transaction_date', $carbonDate->month)
                  ->whereYear('transaction_date', $carbonDate->year);
        }

        $groupByColumn = $type === 'wallet' ? 'wallet_id' : 'category_id';
        $relation = $type === 'wallet' ? 'wallet' : 'category';

        $stats = $query->select($groupByColumn, DB::raw('SUM(amount) as total'))
                       ->groupBy($groupByColumn)
                       ->with($relation)
                       ->get();

        return response()->json([
            'message' => 'Lấy thống kê biểu đồ cột thành công',
            'data' => $stats
        ]);
    }

    public function pieChart(Request $request)
    {
        $type = $request->get('type', 'category'); // group by: category or wallet
        $period = $request->get('period', 'month'); // day, week, month
        $date = $request->get('date', Carbon::today()->format('Y-m-d'));
        $transactionType = $request->get('transaction_type', 'expense');

        $query = $request->user()->transactions()->where('type', $transactionType);

        if ($request->has('wallet_id')) {
            $query->where('wallet_id', $request->wallet_id);
        }
        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $carbonDate = Carbon::parse($date);
        if ($period === 'day') {
            $query->whereDate('transaction_date', $carbonDate->format('Y-m-d'));
        } elseif ($period === 'week') {
            $query->whereBetween('transaction_date', [
                $carbonDate->copy()->startOfWeek()->format('Y-m-d'),
                $carbonDate->copy()->endOfWeek()->format('Y-m-d')
            ]);
        } else {
            $query->whereMonth('transaction_date', $carbonDate->month)
                  ->whereYear('transaction_date', $carbonDate->year);
        }

        $groupByColumn = $type === 'wallet' ? 'wallet_id' : 'category_id';
        $relation = $type === 'wallet' ? 'wallet' : 'category';

        $stats = $query->select($groupByColumn, DB::raw('SUM(amount) as value'))
                       ->groupBy($groupByColumn)
                       ->with($relation)
                       ->get();

        $formattedData = $stats->map(function ($stat) use ($relation) {
            $relatedModel = $stat->$relation;
            return [
                'name' => $relatedModel ? $relatedModel->name : 'Unknown',
                'value' => (float) $stat->value,
                'color' => $relatedModel ? ($relatedModel->color ?? '#ccc') : '#ccc',
            ];
        });

        return response()->json([
            'message' => 'Lấy thống kê biểu đồ tròn thành công',
            'data' => $formattedData
        ]);
    }

    public function summary(Request $request)
    {
        $period = $request->get('period', 'month'); // day, week, month
        $date = $request->get('date', Carbon::today()->format('Y-m-d'));
        $transactionType = $request->get('transaction_type', 'expense');

        $carbonDate = Carbon::parse($date);

        $currentQuery = $request->user()->transactions()->where('type', $transactionType);
        $lastQuery = $request->user()->transactions()->where('type', $transactionType);

        if ($request->has('wallet_id')) {
            $currentQuery->where('wallet_id', $request->wallet_id);
            $lastQuery->where('wallet_id', $request->wallet_id);
        }
        if ($request->has('category_id')) {
            $currentQuery->where('category_id', $request->category_id);
            $lastQuery->where('category_id', $request->category_id);
        }

        if ($period === 'day') {
            $currentQuery->whereDate('transaction_date', $carbonDate->format('Y-m-d'));
            $lastDate = $carbonDate->copy()->subDay();
            $lastQuery->whereDate('transaction_date', $lastDate->format('Y-m-d'));
        } elseif ($period === 'week') {
            $currentQuery->whereBetween('transaction_date', [
                $carbonDate->copy()->startOfWeek()->format('Y-m-d'),
                $carbonDate->copy()->endOfWeek()->format('Y-m-d')
            ]);
            $lastDate = $carbonDate->copy()->subWeek();
            $lastQuery->whereBetween('transaction_date', [
                $lastDate->copy()->startOfWeek()->format('Y-m-d'),
                $lastDate->copy()->endOfWeek()->format('Y-m-d')
            ]);
        } else {
            $currentQuery->whereMonth('transaction_date', $carbonDate->month)
                         ->whereYear('transaction_date', $carbonDate->year);
            $lastDate = $carbonDate->copy()->subMonth();
            $lastQuery->whereMonth('transaction_date', $lastDate->month)
                      ->whereYear('transaction_date', $lastDate->year);
        }

        $currentTotal = $currentQuery->sum('amount');
        $lastTotal = $lastQuery->sum('amount');
        
        $difference = $currentTotal - $lastTotal;
        $percentage = $lastTotal > 0 ? ($difference / $lastTotal) * 100 : ($currentTotal > 0 ? 100 : 0);

        return response()->json([
            'message' => 'Lấy tổng quan giao dịch thành công',
            'data' => [
                'current_total' => (float) $currentTotal,
                'last_total' => (float) $lastTotal,
                'difference' => (float) $difference,
                'percentage' => round($percentage, 2),
            ]
        ]);
    }

    public function goals(Request $request)
    {
        $goals = $request->user()->goals()->with('category')->orderBy('end_date', 'desc')->get();

        $data = $goals->map(function ($goal) use ($request) {
            $spentQuery = $request->user()->transactions()
                ->where('type', 'expense')
                ->whereBetween('transaction_date', [$goal->start_date, $goal->end_date]);

            if ($goal->category_id) {
                $spentQuery->where('category_id', $goal->category_id);
            }

            $spentAmount = (float) $spentQuery->sum('amount');
            $goalAmount = (float) $goal->amount;
            $percentage = $goalAmount > 0 ? ($spentAmount / $goalAmount) * 100 : 0;

            return [
                'id' => $goal->id,
                'name' => $goal->name,
                'category_name' => $goal->category ? $goal->category->name : 'Tổng hợp',
                'amount' => $goalAmount,
                'spent' => $spentAmount,
                'percentage' => round($percentage, 2),
                'start_date' => $goal->start_date,
                'end_date' => $goal->end_date,
                'is_exceeded' => $spentAmount > $goalAmount,
            ];
        });

        return response()->json([
            'message' => 'Lấy thống kê mục tiêu thành công',
            'data' => $data
        ]);
    }
}
