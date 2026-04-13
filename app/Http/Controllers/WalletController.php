<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use Illuminate\Http\Request;
use Exception;

class WalletController extends Controller
{
    public function index(Request $request)
    {
        try {
            $wallets = $request->user()->wallets;
            return response()->json([
                'message' => 'Lấy danh sách ví thành công',
                'data' => $wallets
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Đã có lỗi xảy ra khi lấy danh sách ví',
                'error_detail' => $e->getMessage()
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                \Illuminate\Validation\Rule::unique('wallets')->where(function ($query) use ($request) {
                    return $query->where('user_id', $request->user()->id);
                })
            ],
            'balance' => 'numeric'
        ]);

        try {
            $wallet = $request->user()->wallets()->create($request->all());
            return response()->json([
                'message' => 'Thêm mới ví tiền thành công',
                'data' => $wallet
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Đã có lỗi xảy ra khi tạo ví',
                'error_detail' => $e->getMessage()
            ], 500);
        }
    }

    public function show(Request $request, Wallet $wallet)
    {
        if ($wallet->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Bạn không có quyền truy cập dữ liệu này'], 403);
        }
        return response()->json([
            'message' => 'Lấy thông tin ví thành công',
            'data' => $wallet
        ], 200);
    }

    public function update(Request $request, Wallet $wallet)
    {
        if ($wallet->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Bạn không có quyền truy cập dữ liệu này'], 403);
        }

        $request->validate([
            'name' => [
                'string',
                'max:255',
                \Illuminate\Validation\Rule::unique('wallets')->where(function ($query) use ($request) {
                    return $query->where('user_id', $request->user()->id);
                })->ignore($wallet->id)
            ],
            'balance' => 'numeric'
        ]);

        try {
            $wallet->update($request->all());
            return response()->json([
                'message' => 'Cập nhật ví thành công',
                'data' => $wallet
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Đã có lỗi xảy ra khi cập nhật ví',
                'error_detail' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, Wallet $wallet)
    {
        if ($wallet->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Bạn không có quyền truy cập dữ liệu này'], 403);
        }

        try {
            $wallet->delete();
            return response()->json([
                'message' => 'Xoá ví thành công',
                'data' => null
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Đã có lỗi xảy ra khi xoá ví',
                'error_detail' => $e->getMessage()
            ], 500);
        }
    }
}
