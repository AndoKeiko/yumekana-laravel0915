<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $table = 'tasks';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    protected $fillable = [
        'user_id',
        'goal_id',
        'name',
        'description',
        'elapsed_time',
        'estimated_time',
        'priority',
        'order',
        'review_interval',
        'repetition_count',
        'last_notification_sent',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'goal_id' => 'integer',
        'elapsed_time' => 'integer',
        'estimated_time' => 'integer',
        'priority' => 'integer',
        'order' => 'integer',
        'review_interval' => 'string',
        'repetition_count' => 'integer',
        'last_notification_sent' => 'datetime',
    ];

    public const REVIEW_INTERVALS = [
      'next_day', '7_days', '14_days', '28_days', '56_days', 'completed'
  ];

    public function goal()
    {
        return $this->belongsTo(Goal::class, 'goal_id', 'id');
    }
}