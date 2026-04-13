<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Goal;
use Illuminate\Support\Facades\DB;
use Exception;

class GoalController extends Controller
{
    public function index(Request $request)
    {
        try {
            $goals = $request->user()->goals()->with('category')->get();
            return response()->json([
                'message' => 'Lấy danh sách mục tiêu thành công',
                'data' => $goals
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Đã có lỗi xảy ra',
                'error_detail' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'category_id' => 'nullable|exists:categories,id',
            'name' => 'nullable|string|max:255',
            'amount' => 'required|numeric|min:0.01',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        try {
            $goal = $request->user()->goals()->create($request->all());
            $goal->load('category');

            $categoryName = $goal->category ? $goal->category->name : 'Tổng hợp';
            $amountFormatted = number_format($goal->amount, 0, ',', '.') . 'đ';
            $message = "Bạn đã tạo mục tiêu {$categoryName} với {$amountFormatted}. Hãy cố gắng hoàn thành mục tiêu này nhé!";

            $request->user()->custom_notifications()->create([
                'message' => $message,
                'is_read' => false,
            ]);

            return response()->json([
                'message' => 'Thêm mục tiêu thành công',
                'data' => $goal
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Đã có lỗi xảy ra khi tạo mục tiêu',
                'error_detail' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Request $request, Goal $goal)
    {
        if ($goal->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Bạn không có quyền truy cập dữ liệu này'], 403);
        }

        return response()->json([
            'message' => 'Lấy thông tin mục tiêu thành công',
            'data' => $goal->load('category')
        ], 200);
    }

    public function update(Request $request, Goal $goal)
    {
        if ($goal->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Bạn không có quyền truy cập dữ liệu này'], 403);
        }

        $request->validate([
            'category_id' => 'nullable|exists:categories,id',
            'name' => 'nullable|string|max:255',
            'amount' => 'numeric|min:0.01',
            'start_date' => 'date',
            'end_date' => 'date|after_or_equal:start_date',
        ]);

        try {
            $goal->update($request->all());
            return response()->json([
                'message' => 'Cập nhật mục tiêu thành công',
                'data' => $goal->load('category')
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Đã có lỗi xảy ra khi cập nhật mục tiêu',
                'error_detail' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, Goal $goal)
    {
        if ($goal->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Bạn không có quyền truy cập dữ liệu này'], 403);
        }

        try {
            $goal->delete();
            return response()->json([
                'message' => 'Xoá mục tiêu thành công',
                'data' => null
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Đã có lỗi xảy ra khi xoá mục tiêu',
                'error_detail' => $e->getMessage()
            ], 500);
        }
    }
}
