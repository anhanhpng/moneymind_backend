<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Exception;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        try {
            $categories = $request->user()->categories;
            return response()->json([
                'message' => 'Lấy danh sách danh mục thành công',
                'data' => $categories
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Đã có lỗi xảy ra khi lấy danh sách danh mục',
                'error_detail' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:income,expense',
            'color' => 'nullable|string|max:50',
            'icon' => 'nullable|string|max:50',
        ]);

        try {
            $category = $request->user()->categories()->create($request->all());
            return response()->json([
                'message' => 'Thêm mới danh mục thành công',
                'data' => $category
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Đã có lỗi xảy ra khi tạo danh mục',
                'error_detail' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Request $request, Category $category)
    {
        if ($category->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Bạn không có quyền truy cập dữ liệu này'], 403);
        }
        return response()->json([
            'message' => 'Lấy dữ liệu thành công',
            'data' => $category
        ], 200);
    }

    public function update(Request $request, Category $category)
    {
        if ($category->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Bạn không có quyền truy cập dữ liệu này'], 403);
        }

        $request->validate([
            'name' => 'string|max:255',
            'type' => 'string|in:income,expense',
            'color' => 'nullable|string|max:50',
            'icon' => 'nullable|string|max:50',
        ]);

        try {
            $category->update($request->all());
            return response()->json([
                'message' => 'Cập nhật danh mục thành công',
                'data' => $category
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Đã có lỗi xảy ra khi cập nhật danh mục',
                'error_detail' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, Category $category)
    {
        if ($category->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Bạn không có quyền truy cập dữ liệu này'], 403);
        }

        try {
            $category->delete();
            return response()->json([
                'message' => 'Xoá danh mục thành công',
                'data' => null
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Đã có lỗi xảy ra khi xoá danh mục',
                'error_detail' => $e->getMessage()
            ], 500);
        }
    }
}
