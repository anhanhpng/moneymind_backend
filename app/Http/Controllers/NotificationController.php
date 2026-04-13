<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Notification;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $notifications = $request->user()->custom_notifications()->orderBy('created_at', 'desc')->get();
        return response()->json([
            'message' => 'Lấy danh sách thông báo thành công',
            'data' => $notifications
        ], 200);
    }

    public function markAsRead(Request $request, Notification $notification)
    {
        if ($notification->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Bạn không có quyền truy cập dữ liệu này'], 403);
        }

        $notification->update(['is_read' => true]);

        return response()->json([
            'message' => 'Đánh dấu thông báo đã đọc thành công',
            'data' => $notification
        ], 200);
    }
}
