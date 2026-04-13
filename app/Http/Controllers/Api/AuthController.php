<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Exception;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email:filter|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Dữ liệu không hợp lệ',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
            ]);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Đăng ký tài khoản thành công',
                'data' => [
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                    'user' => $user
                ]
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Đã có lỗi xảy ra khi tạo tài khoản',
                'error_detail' => $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email:filter',
            'password' => 'required|string',
        ]);

        try {
            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'message' => 'Tài khoản hoặc mật khẩu không đúng'
                ], 401);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Đăng nhập thành công',
                'data' => [
                    'access_token' => $token,
                    'token_type' => 'Bearer',
                    'user' => $user
                ]
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Đã có lỗi xảy ra trong quá trình đăng nhập',
                'error_detail' => $e->getMessage()
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        try {
            $request->user()->currentAccessToken()->delete();

            return response()->json([
                'message' => 'Đăng xuất thành công',
                'data' => null
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Đã có lỗi xảy ra khi đăng xuất',
                'error_detail' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteAccount(Request $request)
    {
        try {
            $user = $request->user();
            
            // Xóa tất cả các token đăng nhập hiện tại
            $user->tokens()->delete();
            
            // Xóa người dùng. Do các bảng (wallets, categories, transactions...) 
            // có foreign key user_id được cấu hình onDelete('cascade'), 
            // tất cả dữ liệu liên quan sẽ tự động bị xóa.
            $user->delete();

            return response()->json([
                'message' => 'Xoá tài khoản và toàn bộ dữ liệu thành công',
                'data' => null
            ], 200);
        } catch (Exception $e) {
            return response()->json([
                'message' => 'Đã có lỗi xảy ra khi xoá tài khoản',
                'error_detail' => $e->getMessage()
            ], 500);
        }
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|confirmed|different:current_password',
        ]);

        try {
            $user = $request->user();

            if (!Hash::check($request->current_password, $user->password)) {
                return response()->json([
                    'message' => 'Mật khẩu hiện tại không đúng'
                ], 400);
            }

            $user->password = Hash::make($request->new_password);
            $user->save();

            return response()->json([
                'message' => 'Đổi mật khẩu thành công',
                'data' => null
            ], 200);

        } catch (Exception $e) {
            return response()->json([
                'message' => 'Đã có lỗi xảy ra khi đổi mật khẩu',
                'error_detail' => $e->getMessage()
            ], 500);
        }
    }
}
