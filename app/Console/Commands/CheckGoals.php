<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckGoals extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'goals:check';

    protected $description = 'Check user goals and send notifications if exceeded or achieved';

    public function handle()
    {
        $today = \Carbon\Carbon::today()->format('Y-m-d');
        $yesterday = \Carbon\Carbon::yesterday()->format('Y-m-d');

        $goals = \App\Models\Goal::with('category')->get();

        foreach ($goals as $goal) {
            $query = \App\Models\Transaction::where('user_id', $goal->user_id)
                ->where('type', 'expense')
                ->whereBetween('transaction_date', [$goal->start_date, $goal->end_date]);

            if ($goal->category_id) {
                $query->where('category_id', $goal->category_id);
            }

            $totalExpense = $query->sum('amount');
            $categoryName = $goal->category ? $goal->category->name : 'tổng';

            // Check active goal for over-budget
            if ($today >= $goal->start_date && $today <= $goal->end_date) {
                if ($totalExpense > $goal->amount) {
                    $diff = $totalExpense - $goal->amount;
                    $message = "Chi tiêu {$categoryName} của bạn đã vượt mức mục tiêu {$diff}đ. Hãy tiết kiệm lại nhé!";
                    
                    \App\Models\Notification::create([
                        'user_id' => $goal->user_id,
                        'message' => $message,
                        'is_read' => false,
                    ]);
                }
            }

            // Check just ended goal for success
            if ($goal->end_date === $yesterday) {
                if ($totalExpense <= $goal->amount) {
                    $message = "Tuyệt vời! Chi tiêu {$categoryName} của bạn đợt trước đã đạt mục tiêu rồi. Tiếp tục phát huy nhé!";
                    
                    \App\Models\Notification::create([
                        'user_id' => $goal->user_id,
                        'message' => $message,
                        'is_read' => false,
                    ]);
                }
            }
        }

        $this->info('Goals checked successfully.');
    }
}
