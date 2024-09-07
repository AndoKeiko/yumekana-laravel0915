<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TaskController extends Controller
{
    public function index($goalId, Request $request)
    {
      Log::info('Received data:', $request->all());
      $tasks = Task::where('goal_id', $goalId)->orderBy('order')->get();
      Log::info('Retrieved tasks:', ['count' => $tasks->count()]);
      return response()->json([
          'message' => 'Tasks retrieved successfully',
          'tasks' => $tasks,
      ], 200);
    }

    public function saveTask(Request $request, $goalId)
    {
        $validatedData = $request->validate([
          'tasks' => 'required|array',
          'tasks.*.name' => 'required|string',
          'tasks.*.description' => 'nullable|string',
          'tasks.*.estimated_time' => 'required|integer',
          'tasks.*.priority' => 'required|integer|in:1,2,3',
          'tasks.*.review_interval' => 'required|string|in:' . implode(',', Task::REVIEW_INTERVALS),
        ]);

        DB::beginTransaction();
        try {
            Task::where('goalId', $goalId)->delete();

            $tasks = collect($validatedData['tasks'])->map(function ($task, $index) use ($goalId, $request) {
              return Task::create([
                  'user_id' => $request->user()->id,
                  'goal_id' => $goalId,
                  'name' => $task['name'],
                  'description' => $task['description'] ?? null,
                  'estimated_time' => $task['estimated_time'],
                  'elapsed_time' => 0,
                  'priority' => $task['priority'],
                  'order' => $index + 1,
                  'review_interval' => $task['review_interval'],
                  'repetition_count' => 0,
              ]);
          });

          DB::commit();
          return response()->json([
              'message' => 'Tasks saved successfully',
              'tasks' => $tasks,
          ], 200);
      } catch (\Exception $e) {
          DB::rollBack();
          Log::error('Task saving failed', ['error' => $e->getMessage(), 'goal_id' => $goalId]);
          return response()->json([
              'message' => 'Task saving failed',
              'error' => $e->getMessage(),
          ], 500);
      }
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
          'goal_id' => 'required|integer',
          'name' => 'required|string',
          'description' => 'nullable|string',
          'estimated_time' => 'required|integer',
          'priority' => 'required|integer|in:1,2,3',
          'review_interval' => 'required|string|in:' . implode(',', Task::REVIEW_INTERVALS),
        ]);

        DB::beginTransaction();
        try {
          $maxOrder = Task::where('goal_id', $validatedData['goal_id'])->max('order') ?? 0;

          $task = Task::create([
              'user_id' => $request->user()->id,
              'goal_id' => $validatedData['goal_id'],
              'name' => $validatedData['name'],
              'description' => $validatedData['description'] ?? null,
              'estimated_time' => $validatedData['estimated_time'],
              'elapsed_time' => 0,
              'priority' => $validatedData['priority'],
              'order' => $maxOrder + 1,
              'review_interval' => $validatedData['review_interval'],
              'repetition_count' => 0,
          ]);

            DB::commit();
            return response()->json([
                'message' => 'Task created successfully',
                'task' => $task,
            ], 201);
        } catch (\Exception $e) {
          DB::rollBack();
          Log::error('Task creation failed', ['error' => $e->getMessage(), 'goal_id' => $validatedData['goal_id']]);
          return response()->json([
              'message' => 'Task creation failed',
              'error' => $e->getMessage(),
          ], 500);
        }
    }

    public function updateOrder(Request $request)
    {
        $validatedData = $request->validate([
            'tasks' => 'required|array',
            'tasks.*.taskId' => 'required|integer',
            'tasks.*.taskOrder' => 'required|integer',
        ]);

        DB::beginTransaction();
        try {
            foreach ($validatedData['tasks'] as $task) {
              Task::where('id', $task['id'])
              ->update(['order' => $task['order']]);
            }
            DB::commit();
            return response()->json(['message' => 'Task orders updated successfully'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Task order update failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Task order update failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateElapsedTime(Request $request, $taskId)
    {
        $validatedData = $request->validate([
            'elapsed_time' => 'required|integer',
        ]);

        try {
            $task = Task::findOrFail($taskId);
            $task->elapsed_time = $validatedData['elapsed_time'];
            $task->save();

            return response()->json([
                'message' => 'Task elapsed time updated successfully',
                'task' => $task,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Task elapsed time update failed', ['error' => $e->getMessage(), 'task_id' => $taskId]);
            return response()->json([
                'message' => 'Task elapsed time update failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function updateReviewInterval(Request $request, $taskId)
    {
        $validatedData = $request->validate([
            'review_interval' => 'required|string|in:' . implode(',', Task::REVIEW_INTERVALS),
        ]);

        try {
            $task = Task::findOrFail($taskId);
            $task->review_interval = $validatedData['review_interval'];
            $task->repetition_count += 1;
            $task->save();

            return response()->json([
                'message' => 'Task review interval updated successfully',
                'task' => $task,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Task review interval update failed', ['error' => $e->getMessage(), 'task_id' => $taskId]);
            return response()->json([
                'message' => 'Task review interval update failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}