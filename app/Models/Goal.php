<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Goal extends Model
{
  use HasFactory;
  protected $table = 'goals';
  protected $primaryKey = 'id';
  public $incrementing = true;
  protected $keyType = 'int';

  protected $fillable = [
    'user_id',
    'name',
    'current_status',
    'period_start',
    'period_end',
    'description',
    'status',
    'progress_percentage',
];

protected $casts = [
    'user_id' => 'integer',
    'current_status' => 'string',
    'period_start' => 'date',
    'period_end' => 'date',
    'description' => 'string',
    'status' => 'string',
    'progress_percentage' => 'integer',
];

  public function tasks()
  {
    return $this->hasMany(Task::class, 'goal_id', 'id');
  }
}
