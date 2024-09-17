<?php

namespace App\Http\Controllers;

use App\Models\Goal;
use App\Models\Task;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Http\JsonResponse;

class GoalsController extends Controller
{
  public function index(Request $request)
  {
    $perPage = $request->input('per_page', 10);
    $goals = Goal::paginate($perPage);
    return response()->json([
      'message' => 'Goals retrieved successfully',
      'goals' => $goals,
  ], 200);
  }


  public function store(Request $request): JsonResponse
  {
    Log::info('Received request data:', $request->all());
        $validatedData = $request->validate([
          'user_id' => 'required|exists:users,id',
          'name' => 'required|string|max:255',
          'current_status' => 'nullable|string',
          'period_start' => 'required|date',
          'period_end' => 'required|date|after:period_start',
          'description' => 'nullable|string',
          'status' => 'required|integer|min:0',
          'total_time' => 'required|integer|min:0',
          'progress_percentage' => 'required|integer|min:0|max:100',
        ]);

    $goal = Goal::create($validatedData);

    return response()->json([
      'message' => 'User created successfully',
      'Goals' => $goal,
    ], 201);
  }

  public function show($id): JsonResponse
  {
    try {
      $goal = Goal::findOrFail($id);
      return response()->json($goal);
    } catch (ModelNotFoundException $e) {
        return response()->json(['error' => 'Goal not found'], 404);
    }
  }

    public function update(Request $request, $id): JsonResponse
    {
      try {
        $goal = Goal::findOrFail($id);

        $validatedData = $request->validate([
          'name' => 'sometimes|required|string|max:255',
          'current_status' => 'nullable|string',
          'period_start' => 'sometimes|required|date',
          'period_end' => 'sometimes|required|date|after:period_start',
          'description' => 'nullable|string',
          'status' => 'sometimes|required|integer|min:0',
          'total_time' => 'sometimes|required|integer|min:0',
          'progress_percentage' => 'sometimes|required|integer|min:0|max:100',
        ]);

        $goal->update($validatedData);

        return response()->json([
            'message' => 'Goal updated successfully',
            'goal' => $goal,
        ]);
      } catch (ModelNotFoundException $e) {
        return response()->json(['error' => '目標が見つかりません'], 404);
    }
  }

  public function getUserGoals($userId): JsonResponse
  {
      $goals = Goal::where('user_id', $userId)->get();
      return response()->json($goals);
  }

  public function chat(Request $request, $id)
  {
    Log::info("Chat request received for goal: " . $id);
    Log::info("Request method: " . $request->method());
    Log::info("Request data: " . json_encode($request->all()));
    $request->validate([
      'message' => 'required|string',
    ]);
    if (!isset($id)) {
      return response()->json(['error' => 'Goal ID is not provided'], 400);
    }
    $goal = Goal::findOrFail($id);
    if (!$goal) {
      return response()->json(['error' => 'Goal not found'], 404);
    }
    $userMessage = $request->input('description');
    $prompt = "目標: {$goal->name}\n"
    . "現在の状況: {$goal->current_status}\n"
    . "目標期間開始: {$goal->period_start}\n"
    . "目標期間終了: {$goal->period_end}\n\n"
    . "その他注釈事項: {$userMessage}\n\n"
    . "この情報に基づいて、目標期間内に目標を達成するための学習スケジュールを作成してください。\n"
    . "以下の点に注意してスケジュールを作成してください：\n"
    . "1. 各タスクや活動に推奨される時間を含めてください。時間は数値（小数点以下1桁まで）で表してください。\n"
    . "2. スケジュールは日単位、週単位、または月単位で構成し、具体的な活動内容を記載してください。\n"
    . "3. 週単位の時間（例：週5時間）や月単位の時間（例：月20時間）は、目標期間内の総時間に変換してください。\n"
    . "4. すべての時間は、目標期間全体での合計時間として計算してください。\n"
    . "5. 回答はタスクごとに改行してください。\n\n"
    . "回答は日本語で、以下のJSONフォーマットにて作成してください：\n\n"
    . "[\n"
    . "  {\n"
    . "    \"taskName\": \"[タスク名]\",\n"
    . "    \"taskTime\": [時間数（数値）],\n"
    . "    \"taskPriority\": [重要度（1-3の数値）]\n"
    . "  },\n"
    . "  {\n"
    . "    \"taskName\": \"[タスク名]\",\n"
    . "    \"taskTime\": [時間数（数値）],\n"
    . "    \"taskPriority\": [重要度（1-3の数値）]\n"
    . "  }\n"
    . "  ...\n"
    . "]";

    $result = OpenAI::chat()->create([
      'model' => 'gpt-4o-mini',
      'messages' => [
        ['role' => 'system', 'content' => $prompt],
      ],
    ]);

    $content = $result->choices[0]->message->content;
    $content = preg_replace('/```json\s*|\s*```/', '', $content);
    $tasks = json_decode($content, true);

    if (is_array($tasks)) {
      foreach ($tasks as $task) {
        Task::create([
          'goal_id' => $id,
          'user_id' => $request->user()->id,
          'name' => $task['taskName'],
          'estimated_time' => $task['taskTime'],
          'priority' => $task['taskPriority'],
      ]);
      }
    } else {
      $jsonError = json_last_error_msg();
      Log::error('Failed to parse tasks', ['content' => $content, 'error' => $jsonError]);
      return response()->json(['error' => 'Failed to parse tasks: ' . $jsonError], 500);
    }

    return response()->json([
      'response' => $content,
      'message' => 'Chat completed successfully',
    ]);
  }

  private function convertPriorityToInt($priority)
  {
    switch (strtolower($priority)) {
      case '高':
        return 3;
      case '中':
        return 2;
      case '低':
        return 1;
      default:
        return 2;
    }
  }

  public function destroy($id)
  {
    try {
      $goal = Goal::find($id);
      $goal->delete();
      return response()->json(['message' => '目標が正常に削除されました'], 200);
    } catch (ModelNotFoundException $e) {
      return response()->json(['message' => '指定された目標が見つかりません'], 404);
    } catch (Exception $e) {
      Log::error('目標削除中のエラー: ' . $e->getMessage());
      return response()->json(['message' => '目標の削除中にエラーが発生しました', 'error' => $e->getMessage()], 500);
    }
  }

  public function getChatHistory($id): JsonResponse
  {
      $goal = Goal::findOrFail($id);
      return response()->json(['chat_history' => $goal->chatHistory]);
  }
}
