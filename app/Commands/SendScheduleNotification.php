<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Schedule;
use App\Http\Controllers\FCMController;

class SendScheduleNotification extends Command
{
    protected $signature = 'schedule:notify';
    protected $description = 'Send notifications for upcoming schedules';

    public function handle()
    {
        $schedules = Schedule::where('start_time', '>', now())
            ->where('start_time', '<=', now()->addMinutes(15))
            ->get();

        $fcmController = new FCMController();

        foreach ($schedules as $schedule) {
            $fcmController->sendNotification(
                $schedule->user_id,
                'スケジュール開始まもなく',
                "{$schedule->title}が15分後に開始します。"
            );
        }

        $this->info('Notifications sent successfully.');
    }
}