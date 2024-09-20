<?php

namespace App\Http\Controllers;

use App\Models\Goal;
use App\Models\Task;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use OpenAI\Laravel\Facades\OpenAI;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Exception;

class GoalsController extends Controller
{

  public function __construct()
  {
    $this->middleware('auth');
  }

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
    Log::info('Received goal creation request:', $request->all());
    try {
      try {
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
      } catch (\Illuminate\Validation\ValidationException $e) {
        Log::error('Validation failed:', $e->errors());
        return response()->json(['error' => $e->errors()], 422);
      }

      try {
        $goal = Goal::create($validatedData);
      } catch (\Exception $e) {
        Log::error('Failed to create goal:', ['error' => $e->getMessage()]);
        return response()->json(['error' => 'Failed to create goal'], 500);
      }

      return response()->json([
        'message' => 'User created successfully',
        'Goals' => $goal,
      ], 201);
    } catch (\Exception $e) {
      Log::error('Unexpected error in goal creation:', ['error' => $e->getMessage()]);
      return response()->json(['error' => 'An unexpected error occurred'], 500);
    }
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

    try {
      $request->validate([
        'message' => 'required|string',
      ]);

      $goal = Goal::findOrFail($id);
      $userMessage = $request->input('message');

      $prompt = $this->buildPrompt($goal, $userMessage);
      $aiResponse = $this->getAIResponse($prompt);
      $tasks = $this->parseAIResponse($aiResponse);

      DB::beginTransaction();
      try {
        $createdTasks = $this->createTasks($tasks, $id, $request->user()->id);
        DB::commit();
      } catch (Exception $e) {
        DB::rollBack();
        Log::error('Failed to create tasks: ' . $e->getMessage());
        return response()->json(['error' => 'Failed to create tasks'], 500);
      }

      return response()->json([
        'message' => 'Chat completed successfully',
        'response' => $aiResponse,
        'tasks' => $createdTasks,
      ]);
      Log::info('Response sent to frontend:', [
        'message' => 'Chat completed successfully',
        'response' => $aiResponse,
        'tasks' => $createdTasks,
      ]);
    } catch (ValidationException $e) {
      Log::error('Validation failed: ' . json_encode($e->errors()));
      return response()->json(['error' => $e->errors()], 422);
    } catch (ModelNotFoundException $e) {
      Log::error('Goal not found: ' . $e->getMessage());
      return response()->json(['error' => 'Goal not found'], 404);
    } catch (Exception $e) {
      Log::error('Unexpected error in chat: ' . $e->getMessage());
      return response()->json(['error' => 'An unexpected error occurred'], 500);
    }
  }

  private function buildPrompt(Goal $goal, string $userMessage): string
  {
    return "目標: {$goal->name}\n"
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
  }

  private function getAIResponse(string $prompt): string
  {
    try {
      $result = OpenAI::chat()->create([
        'model' => 'gpt-4o-mini',
        'messages' => [
          ['role' => 'system', 'content' => $prompt],
        ],
      ]);

      $content = $result->choices[0]->message->content;
      Log::info('AI Response received: ' . $content);
      return preg_replace('/```json\s*|\s*```/', '', $content);
    } catch (\Exception $e) {
      Log::error('Failed to get AI response: ' . $e->getMessage());
      throw new \Exception('Failed to generate AI response');
    }
  }

  private function parseAIResponse(string $response): array
  {
    try {
      $tasks = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
      if (!is_array($tasks)) {
        throw new \Exception('Decoded response is not an array');
      }
      return $tasks;
    } catch (\JsonException $e) {
      Log::error('Failed to parse AI response: ' . $e->getMessage());
      throw new \Exception('Failed to parse AI response');
    }
  }

  private function createTasks(array $tasks, int $goalId, int $userId): array
  {
      $createdTasks = [];
      foreach ($tasks as $task) {
          $createdTask = Task::create([
              'goal_id' => $goalId,
              'user_id' => $userId,
              'name' => $task['taskName'],
              'estimated_time' => $task['taskTime'],
              'priority' => $task['taskPriority'],
          ]);
          $createdTasks[] = [
              'id' => $createdTask->id,
              'taskName' => $createdTask->name,
              'taskTime' => $createdTask->estimated_time,
              'taskPriority' => $createdTask->priority,
          ];
      }
      return $createdTasks;
  }


  public function convertPriorityToInt($priority)
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
