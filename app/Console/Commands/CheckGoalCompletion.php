<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckGoalCompletion extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'goals:evaluate';

    protected $description = 'Đánh giá các Mục tiêu (Goals) đã kết thúc chu kỳ để gửi thông báo tổng kết';

    public function handle()
    {
        $goals = \App\Models\Goal::with(['user', 'category'])
            ->where('end_date', '<', now()->startOfDay())
            ->where('is_completed_notified', false)
            ->get();

        $count = 0;
        foreach ($goals as $goal) {
            $user = $goal->user;
            if (!$user) continue;

            $spentQuery = $user->transactions()
                ->where('type', 'expense')
                ->whereBetween('transaction_date', [$goal->start_date, $goal->end_date]);

            if ($goal->category_id) {
                $spentQuery->where('category_id', $goal->category_id);
            }

            $spentAmount = $spentQuery->sum('amount');
            $categoryName = $goal->category ? $goal->category->name : 'Tổng hợp';

            if ($spentAmount <= $goal->amount) {
                $message = "Tuyệt vời! Bạn đã đạt mục tiêu {$categoryName}. Tiếp tục cố gắng nhé!";
            } else {
                $message = "Thật buồn! Chi tiêu {$categoryName} kì này của bạn đã vượt hạn mức mục tiêu rồi. Hãy tính toán chi tiêu tiết kiệm lại nhé";
            }

            $user->custom_notifications()->create([
                'message' => $message,
                'is_read' => false,
            ]);

            $goal->update(['is_completed_notified' => true]);
            $count++;
        }

        $this->info("Đã xử lý thông báo tổng kết cho {$count} mục tiêu.");
    }
}
