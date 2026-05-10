<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Goal extends Model
{
    protected $fillable = [
        'user_id',
        'category_id',
        'name',
        'amount',
        'start_date',
        'end_date',
        'is_exceeded_notified',
        'is_completed_notified',
        'is_80_percent_notified',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
