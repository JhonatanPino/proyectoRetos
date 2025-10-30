<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Challenge;
use App\Models\Category;
use App\Http\Resources\ChallengeResource;
use App\Models\Answer;
use Illuminate\Support\Facades\Http; 
use Illuminate\Support\Facades\DB;
use App\Models\UserAnswer;
use Illuminate\Support\Facades\Validator;

class ChallengeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api')->except(['index', 'show']);
        $this->middleware('role:admin')->only(['store','update','destroy']);
    }

    public function index(Request $request)
    {
        $query = Challenge::with(['category','answers']);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->query('category_id'));
        }

        $challenges = $query->get();
        return ChallengeResource::collection($challenges);
    }

    public function show(Challenge $challenge)
    {
        return new ChallengeResource($challenge->load(['category', 'answers']));
    }

    public function getChallengesByCategory(Request $request, $categoryId)
    {
        $challenges = Challenge::where('category_id', $categoryId)
            ->with(['answers'])
            ->get();

        // Obtener los retos con información de si el usuario ya respondió
        $userAnswers = UserAnswer::where('user_id', $request->user()->id)
            ->pluck('challenge_id')
            ->toArray();

        $challenges->each(function ($challenge) use ($userAnswers) {
            $challenge->user_has_answered = in_array($challenge->id, $userAnswers);
        });

        return response()->json([
            'message' => 'Retos obtenidos con éxito.',
            'data' => $challenges,
        ]);
    }

    public function store(Request $request)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'score_value' => 'required|integer|min:0',
            'answers' => 'required|array|min:1',
            'answers.*.description' => 'required|string',
            'answers.*.is_correct' => 'sometimes|boolean',
        ], [
            'name.required' => 'El nombre del reto es obligatorio.',
            'category_id.required' => 'La categoría es obligatoria.',
            'category_id.exists' => 'La categoría seleccionada no existe.',
            'description.required' => 'La descripción del reto es obligatoria.',
            'score_value.required' => 'El valor de puntuación es obligatorio.',
            'answers.required' => 'Debe enviar las respuestas asociadas al reto.',
            'answers.*.description.required' => 'Cada respuesta necesita una descripción.',
        ]);

        // Validar que haya al menos una respuesta marcada como correcta
        $hasCorrect = collect($validated['answers'])->contains(fn($a) => !empty($a['is_correct']));
        if (! $hasCorrect) {
            return response()->json(['message' => 'Debe marcar al menos una respuesta como correcta.'], 422);
        }

        DB::beginTransaction();
        try {
            $challenge = Challenge::create([
                'category_id' => $validated['category_id'],
                'name' => $validated['name'],
                'description' => $validated['description'],
                'score_value' => $validated['score_value'],
            ]);

            // Crear respuestas; garantizar exactamente una correcta
            $marked = false;
            foreach ($validated['answers'] as $ans) {
                $isCorrect = false;
                if (! $marked && ! empty($ans['is_correct'])) {
                    $isCorrect = true;
                    $marked = true;
                }
                Answer::create([
                    'challenge_id' => $challenge->id,
                    'description' => $ans['description'],
                    'is_correct' => $isCorrect,
                ]);
                
            }

            DB::commit();

            return response()->json([
                'message' => 'Reto creado exitosamente.',
                'data' => new ChallengeResource($challenge->load('answers','category'))
            ], 201);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error creando el reto.',
                'error' => $e->getMessage()
            ],500);
        }
    }

    public function update(Request $request, Challenge $challenge)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'No autorizado. Sólo administradores pueden actualizar retos.'], 403);
        }

        $validated = $request->validate([
            'category_id' => ['sometimes','integer','exists:categories,id'],
            'name' => ['sometimes','string','max:255'],
            'description' => ['sometimes','string'],
            'score_value' => ['sometimes','integer','min:0'],
            'answers' => ['sometimes','array'],
            'answers.*.id' => ['sometimes','integer','exists:answers,id'],
            'answers.*.description' => ['required_with:answers','string'],
            'answers.*.is_correct' => ['sometimes','boolean'],
        ], [
            'answers.*.description.required_with' => 'Cada respuesta necesita una descripción.',
        ]);

        // Si vienen respuestas, validar duplicados dentro de la petición
        if (isset($validated['answers'])) {
            $descs = array_map(fn($a) => trim((string) ($a['description'] ?? '')), $validated['answers']);
            if (count($descs) !== count(array_unique($descs))) {
                return response()->json(['message' => 'Cada respuesta debe tener una descripción única dentro del mismo reto.'], 422);
            }
        }

        DB::beginTransaction();
        try {
            // actualizar campos del reto
            $updateData = array_intersect_key($validated, array_flip(['category_id','name','description','score_value']));
            if (! empty($updateData)) {
                $challenge->update($updateData);
            }

            // sincronizar respuestas si se enviaron
            if (isset($validated['answers'])) {
                $submitted = $validated['answers'];
                $submittedIds = array_filter(array_map(fn($a) => $a['id'] ?? null, $submitted));
                $existing = $challenge->answers()->get()->keyBy('id');

                // eliminar respuestas que no vienen en la petición
                foreach ($existing as $id => $ansModel) {
                    if (! in_array($id, $submittedIds, true)) {
                        $ansModel->delete();
                    }
                }

                // crear/actualizar enviadas
                foreach ($submitted as $ans) {
                    $desc = trim($ans['description'] ?? '');
                    $isCorrect = ! empty($ans['is_correct']);

                    if (! empty($ans['id'])) {
                        $ansModel = $existing[$ans['id']] ?? null;
                        if (! $ansModel) {
                            DB::rollBack();
                            return response()->json(['message' => 'La respuesta enviada no pertenece al reto.'], 422);
                        }
                        $ansModel->update([
                            'description' => $desc,
                            'is_correct' => $isCorrect,
                        ]);
                    } else {
                        Answer::create([
                            'challenge_id' => $challenge->id,
                            'description' => $desc,
                            'is_correct' => $isCorrect,
                        ]);
                    }
                }

                // asegurar al menos una correcta
                $correctCount = $challenge->answers()->where('is_correct', true)->count();
                if ($correctCount === 0) {
                    DB::rollBack();
                    return response()->json(['message' => 'Debe marcar al menos una respuesta como correcta.'], 422);
                }

                // si hay más de una correcta dejar sólo la primera
                if ($correctCount > 1) {
                    $first = $challenge->answers()->where('is_correct', true)->orderBy('id')->first();
                    Answer::where('challenge_id', $challenge->id)
                        ->where('id', '!=', $first->id)
                        ->update(['is_correct' => false]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Reto actualizado correctamente.',
                'data' => new ChallengeResource($challenge->fresh()->load(['category','answers']))
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Error actualizando el reto.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Request $request, Challenge $challenge)
    {
        if ($request->user()->role !== 'admin') {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $challenge->delete();
        return response()->json([
            'message' => 'Reto eliminado correctamente.'
        ], 200);
    }

    public function byCategory($categoryId)
    {
        $category = Category::findOrFail($categoryId);
        $challenges = $category->challenges()->with(['category', 'answers'])->get();
        return ChallengeResource::collection($challenges);
    }

    public function submit(Request $request, Challenge $challenge)
    {
        $user = $request->user();
        
        $v = Validator::make($request->all(), [
            'selected_answer_id' => 'required|integer|exists:answers,id',
        ]);

        if ($v->fails()) {
            return response()->json(['errors' => $v->errors()], 422);
        }

        $selectedAnswerId = (int) $v->validated()['selected_answer_id'];

        $answer = Answer::find($selectedAnswerId);
        if (! $answer || $answer->challenge_id !== $challenge->id) {
            return response()->json(['message' => 'La respuesta seleccionada no pertenece a este reto.'], 422);
        }

        if ($user->role !== 'user') {
            return response()->json(['message' => 'Solo usuarios tipo user pueden enviar respuestas.'], 403);
        }

        $alreadyCorrect = UserAnswer::where('user_id', $user->id)
            ->where('challenge_id', $challenge->id)
            ->where('is_correct_submission', true)
            ->exists();

        if ($alreadyCorrect) {
            return response()->json(['message' => 'Ya respondiste correctamente este reto anteriormente.'], 409);
        }

        DB::beginTransaction();
        try {
            $isCorrect = (bool) $answer->is_correct;

            $userAnswer = UserAnswer::create([
                'user_id' => $user->id,
                'challenge_id' => $challenge->id,
                'selected_answer_id' => $selectedAnswerId,
                'is_correct_submission' => $isCorrect,
                'submitted_at' => now(),
            ]);

            if ($isCorrect && ! $alreadyCorrect) {
                $user->increment('score', (int) $challenge->score_value);
            }

            DB::commit();

            return response()->json([
                'message' => $isCorrect ? 'Respuesta correcta. Puntos asignados.' : 'Respuesta registrada. Incorrecta.',
                'data' => [
                    'user_answer_id' => $userAnswer->id,
                    'is_correct' => $isCorrect,
                    'user_score' => $user->fresh()->score,
                ]
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error registrando la respuesta.','error' => $e->getMessage()], 500);
        }
    }

    public function generateRandom(Request $request)
    {
        $v = Validator::make($request->all(), [
            'category_id'   => ['required','integer','exists:categories,id'],
            'score_value'   => ['sometimes','integer','min:0'],
            'answers_count' => ['sometimes','integer','min:2','max:6'],
        ]);
        if ($v->fails()) return response()->json(['errors'=>$v->errors()], 422);

        $data = $v->validated();
        $answersCount = $data['answers_count'] ?? 4;
        $score = $data['score_value'] ?? 10;

        // cargar categoría para incluir su nombre en el prompt y que la IA genere un reto coherente
        $category = Category::find($data['category_id']);
        $categoryName = $category?->name ?? 'General';

        //$prompt = "Genera un reto en la categoría '{$categoryName}' en JSON con campos: name (titulo), description (enunciado) y answers (array de {$answersCount} objetos {description,is_correct}). Debe haber exactamente 1 is_correct=true. Devuélveme SOLO JSON.";
        
        $prompt = "Eres un generador de retos educativos. Genera un reto único y variado en la categoría '{$categoryName}' en formato JSON con los siguientes campos:
            - name: el título del reto, que debe ser claro y relacionado con la categoría.
            - description: el enunciado del reto, que debe ser breve pero informativo.
            - answers: un array de {$answersCount} objetos, cada uno con los campos:
            - description: el texto de la respuesta.
            - is_correct: un booleano que indica si la respuesta es correcta.
            Debe haber exactamente 1 respuesta con is_correct=true. Asegúrate de que el reto sea único y no se repita con otros retos en la misma categoría. Usa un lenguaje sencillo y evita términos técnicos innecesarios. Devuélveme SOLO JSON válido, sin texto adicional.";
        $apiKey = env('OPENAI_API_KEY');
        if (! $apiKey) return response()->json(['message'=>'OPENAI_API_KEY no configurada'], 500);

        try {
            $resp = Http::withToken($apiKey)->post('https://api.openai.com/v1/chat/completions', [
                'model' => env('OPENAI_MODEL','gpt-4o-mini'),
                'messages' => [
                    ['role'=>'system','content'=>'Eres un asistente que genera preguntas tipo test en JSON.'],
                    ['role'=>'user','content'=>$prompt],
                ],
                'temperature' => 0.2,
                'max_tokens' => 600,
            ]);

            if (! $resp->ok()) {
                return response()->json(['message'=>'Error proveedor IA','detail'=>$resp->body()], 502);
            }

            $body = $resp->json();
            $text = $body['choices'][0]['message']['content'] ?? ($body['choices'][0]['text'] ?? null);
            if (! $text) return response()->json(['message'=>'Respuesta IA vacía'], 502);

            // Extraer primer JSON válido
            if (preg_match('/\{(?:[^{}]|(?R))*\}/s', $text, $m)) {
                $jsonText = $m[0];
            } else {
                $jsonText = $text;
            }

            $parsed = json_decode($jsonText, true);
            if (json_last_error() !== JSON_ERROR_NONE || empty($parsed['answers']) || empty($parsed['name'])) {
                return response()->json(['message'=>'No se pudo parsear JSON válido de la IA','raw'=>$text], 502);
            }

            // Validar si el reto ya existe en la categoría
            $existingChallenge = Challenge::where('category_id', $data['category_id'])
                ->where('name', $parsed['name'])
                ->first();

            if ($existingChallenge) {
                return response()->json(['message' => 'El reto generado ya existe en esta categoría. Intenta nuevamente.'], 409);
            }

            if (count($parsed['answers']) !== $answersCount) {
                return response()->json(['message'=>"La IA devolvió ".count($parsed['answers'])." respuestas; se esperaban {$answersCount}"], 422);
            }
            $correctCount = collect($parsed['answers'])->where('is_correct', true)->count();
            if ($correctCount !== 1) {
                return response()->json(['message'=>'La IA debe marcar exactamente una respuesta como correcta'], 422);
            }

            DB::beginTransaction();
            $challenge = Challenge::create([
                'category_id' => $data['category_id'],
                'name' => substr($parsed['name'],0,255),
                'description' => substr($parsed['description'] ?? '',0,2000),
                'score_value' => $score,
            ]);
            foreach ($parsed['answers'] as $ans) {
                Answer::create([
                    'challenge_id' => $challenge->id,
                    'description' => substr($ans['description'] ?? '',0,1000),
                    'is_correct' => !empty($ans['is_correct']),
                ]);
            }
            DB::commit();

            return response()->json(['message'=>'Reto generado por IA creado.','data'=> new ChallengeResource($challenge->load(['answers','category']))], 201);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['message'=>'Error generando reto','error'=>$e->getMessage()], 500);
        }
    }

    
}