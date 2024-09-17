<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Goal;

class TaskController extends Controller
{
  public function index($goalId, Request $request)
  {
    $goal = Goal::findOrFail($goalId);
    Log::info('Received data:', $request->all());
    $tasks = Task::where('goal_id', $goalId)->orderBy('order')->get();
    Log::info('Retrieved tasks:', ['count' => $tasks->count()]);

    return response()->json([
      'message' => 'Tasks retrieved successfully',
      'goal_name' => $goal->name,
      'tasks' => $tasks,
    ], 200);
  }


  public function saveTask(Request $request, $goalId)
  {
    $validatedData = $request->validate([
      'tasks' => 'required|array',
      'tasks.*.id' => 'nullable|integer',
      'tasks.*.name' => 'required|string',
      'tasks.*.description' => 'nullable|string',
      'tasks.*.estimated_time' => 'required|integer',
      'tasks.*.priority' => 'required|integer|in:1,2,3',
      'tasks.*.review_interval' => 'required|string|in:' . implode(',', Task::REVIEW_INTERVALS),
    ]);

    DB::beginTransaction();
    try {
      $goalId = $request->input('goalId', $goalId);
      Log::info('Starting task save process', ['goal_id' => $goalId, 'task_count' => count($validatedData['tasks'])]);

      $existingTaskIds = Task::where('goal_id', $goalId)->pluck('id')->toArray();
      Log::info('Existing task IDs', ['existing_ids' => $existingTaskIds]);

      $updatedCount = 0;
      $createdCount = 0;

      $tasks = collect($validatedData['tasks'])->map(function ($task, $index) use ($goalId, $request, &$existingTaskIds, &$updatedCount, &$createdCount) {
        if (isset($task['id']) && $task['id'] !== null) {
          $taskModel = Task::find($task['id']);
          if ($taskModel) {
            $taskModel->update([
              'name' => $task['name'],
              'description' => $task['description'] ?? null,
              'estimated_time' => $task['estimated_time'],
              'priority' => $task['priority'],
              'order' => $index + 1,
              'review_interval' => $task['review_interval'],
            ]);
            Log::info('Updated existing task', ['task_id' => $taskModel->id, 'task_name' => $taskModel->name]);
            $existingTaskIds = array_diff($existingTaskIds, [$taskModel->id]);
            $updatedCount++;
            return $taskModel;
          }
        }

        $newTask = Task::create([
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
        Log::info('Created new task', ['task_id' => $newTask->id, 'task_name' => $newTask->name]);
        $createdCount++;
        return $newTask;
      });

      if (!empty($existingTaskIds)) {
        $deletedCount = Task::whereIn('id', $existingTaskIds)->delete();
        Log::info('Deleted unused tasks', ['deleted_task_ids' => $existingTaskIds, 'deleted_count' => $deletedCount]);
      }

      DB::commit();
      Log::info('Tasks saved successfully', [
        'goal_id' => $goalId,
        'updated_count' => $updatedCount,
        'created_count' => $createdCount,
        'deleted_count' => count($existingTaskIds),
        'total_saved_count' => $tasks->count()
      ]);

      return response()->json([
        'message' => 'Tasks saved successfully',
        'tasks' => $tasks,
        'stats' => [
          'updated' => $updatedCount,
          'created' => $createdCount,
          'deleted' => count($existingTaskIds),
          'total' => $tasks->count()
        ]
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
  public function destroy($goalId, $taskId)
  {
    try {
      $task = Task::where('goal_id', $goalId)->findOrFail($taskId);
      $task->delete();
      return response()->json(['message' => 'タスクが正常に削除されました'], 200);
    } catch (ModelNotFoundException $e) {
      return response()->json(['message' => '指定されたタスクが見つかりません'], 404);
    } catch (Exception $e) {
      Log::error('タスク削除中のエラー: ' . $e->getMessage());
      return response()->json(['message' => 'タスクの削除中にエラーが発生しました', 'error' => $e->getMessage()], 500);
    }
  }
  public function update(Request $request, $goalId, $taskId): JsonResponse
  {
    try {
      $task = Task::where('goal_id', $goalId)->findOrFail($taskId);

      $validatedData = $request->validate([
        'name' => 'required|string|max:255',
        'description' => 'nullable|string',
        'elapsed_time' => 'sometimes|required|integer',
        'estimated_time' => 'sometimes|required|integer',
        'start_date' => 'nullable|date',
        'start_time' => 'nullable|date_format:H:i',
        'priority' => 'required|integer|min:1|max:3',
        'order' => 'sometimes|required|integer|min:0',
        'review_interval' => 'nullable|in:next_day,7_days,14_days,28_days,56_days,completed',
        'repetition_count' => 'sometimes|required|integer|min:1',
        'last_notification_sent' => 'nullable|date',
      ]);

      $task->update($validatedData);

      return response()->json([
        'message' => 'task updated successfully',
        'task' => $task,
      ]);
    } catch (ModelNotFoundException $e) {
      return response()->json(['error' => 'タスクが見つかりません'], 404);
    }
  }
  public function updateElapsedTime(Request $request, $goalId, $taskId)
  {
    $validatedData = $request->validate([
      'elapsed_time' => 'required|integer',
    ]);
    try {
      $task = Task::where('goal_id', $goalId)->findOrFail($taskId);
      $task->elapsed_time = $validatedData['elapsed_time'];
      $task->save();

      return response()->json([
        'message' => 'Elapsed time updated successfully',
        'task' => $task,
      ]);
    } catch (ModelNotFoundException $e) {
      return response()->json(['message' => '指定されたタスクが見つかりません'], 404);
    } catch (\Exception $e) {
      Log::error('Task elapsed time update failed', ['error' => $e->getMessage(), 'task_id' => $taskId]);
      return response()->json([
        'message' => 'Task elapsed time update failed',
        'error' => $e->getMessage(),
      ], 500);
    }
  }
  public function updateReviewInterval(Request $request, $goalId, $taskId)
  {
    $validatedData = $request->validate([
      'review_interval' => 'required|string|in:' . implode(',', Task::REVIEW_INTERVALS),
    ]);

    try {
      $task = Task::where('goal_id', $goalId)->findOrFail($taskId);
      $task->review_interval = $validatedData['review_interval'];
      $task->repetition_count += 1;
      $task->save();

      return response()->json([
        'message' => 'Task review interval updated successfully',
        'task' => $task,
      ], 200);
    } catch (ModelNotFoundException $e) {
      return response()->json(['message' => '指定されたタスクが見つかりません'], 404);
    } catch (\Exception $e) {
      Log::error('Task review interval update failed', ['error' => $e->getMessage(), 'task_id' => $taskId]);
      return response()->json([
        'message' => 'Task review interval update failed',
        'error' => $e->getMessage(),
      ], 500);
    }
  }
}
