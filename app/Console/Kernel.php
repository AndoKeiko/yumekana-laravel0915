<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Models\User;
use App\Models\Schedule as UserSchedule;
use App\Notifications\ScheduleNotification;
use Carbon\Carbon;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule)
    {
        $schedule->call(function () {
            // 15分後に開始するスケジュールを取得
            $upcomingSchedules = UserSchedule::where('start_time', '>', now())
                ->where('start_time', '<=', now()->addMinutes(15))
                ->with('user')  // ユーザーリレーションをEager Loading
                ->get();

            foreach ($upcomingSchedules as $userSchedule) {
                if ($userSchedule->user && $userSchedule->user->fcm_token) {
                    $userSchedule->user->notify(new ScheduleNotification(
                        'スケジュール開始まもなく',
                        "{$userSchedule->title}が15分後に開始します。"
                    ));
                }
            }
        })->everyMinute();  // 毎分実行

        // 他のスケジュールタスクをここに追加...
    }
}
