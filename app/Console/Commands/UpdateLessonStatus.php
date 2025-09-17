<?php

namespace App\Console\Commands;

use App\Models\Lesson;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateLessonStatus extends Command
{
    protected $signature = 'lessons:update-status';
    protected $description = 'Update lesson status from Scheduled to Completed after the lesson ends';

    public function handle()
    {
        $timezone = config('app.timezone', 'Africa/Cairo');
        $now = Carbon::now($timezone);

        $lessons = Lesson::scheduled()
            ->select('id', 'date', 'time')
            ->get();

        $updatedCount = 0;

        foreach ($lessons as $lesson) {
            $lessonDateTime = Carbon::parse("{$lesson->date} {$lesson->time}", $timezone);

            $lessonEndTime = $lessonDateTime->copy()->addMinutes(90);

            if ($now->greaterThan($lessonEndTime)) {
                $lesson->update(['status' => 2]);
                $updatedCount++;
                Log::info("Lesson ID {$lesson->id} updated to Completed.", [
                    'date' => $lesson->date,
                    'time' => $lesson->time,
                    'ended_at' => $lessonEndTime->toDateTimeString(),
                ]);
            }
        }

        $this->info("Updated {$updatedCount} lessons to Completed status.");
        Log::info("UpdateLessonStatus command executed. Updated {$updatedCount} lessons.");

        return 0;
    }
}