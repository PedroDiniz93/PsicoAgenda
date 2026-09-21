<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GameKitVisualActivity;
use App\Models\GameKitVisualActivityUsage;
use App\Services\GameKitAiService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class GameKitVisualActivityController extends Controller
{
    private const TYPES = ['coloring', 'cutting', 'coloring_cutting'];

    private const STYLES = ['simple', 'intermediate', 'detailed'];

    private const LIMIT = 5;

    public function __construct(private readonly GameKitAiService $ai) {}

    public function index(Request $request)
    {
        $psychologistId = $this->psychologistId($request);
        $activities = GameKitVisualActivity::where('psychologist_id', $psychologistId)
            ->whereIn('status', ['ready', 'failed'])
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn (GameKitVisualActivity $activity) => $this->payload($activity));

        return response()->json([
            'activities' => $activities,
            'quota' => $this->quota($psychologistId),
        ]);
    }

    public function generate(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', 'string', 'in:'.implode(',', self::TYPES)],
            'style' => ['required', 'string', 'in:'.implode(',', self::STYLES)],
            'theme' => ['required', 'string', 'min:3', 'max:160'],
            'therapeutic_goal' => ['required', 'string', 'min:3', 'max:500'],
        ]);
        $this->rejectSensitiveInput($data['theme'].' '.$data['therapeutic_goal']);

        $psychologistId = $this->psychologistId($request);
        $weekStart = now()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $usage = null;
        $activity = null;

        DB::transaction(function () use ($psychologistId, $weekStart, &$usage, &$activity, $data): void {
            $usage = GameKitVisualActivityUsage::firstOrCreate(
                ['psychologist_id' => $psychologistId, 'week_start' => $weekStart],
                ['generated_count' => 0]
            );
            $usage = GameKitVisualActivityUsage::whereKey($usage->id)->lockForUpdate()->firstOrFail();
            if ($usage->generated_count >= self::LIMIT) {
                abort(429, 'O limite de 5 imagens por semana foi atingido.');
            }
            $usage->increment('generated_count');
            $activity = GameKitVisualActivity::create([
                ...$data,
                'psychologist_id' => $psychologistId,
                'status' => 'generating',
            ]);
        });

        try {
            $image = $this->ai->generateVisualImage($data);
            $path = 'gamekit/visual-activities/'.$psychologistId.'/'.Str::uuid().'.png';
            Storage::disk('public')->put($path, $image['binary']);
            $activity->update([
                'status' => 'ready',
                'storage_path' => $path,
                'mime_type' => $image['mime_type'],
                'width' => $image['width'],
                'height' => $image['height'],
                'provider' => $image['provider'],
                'model' => $image['model'],
                'prompt_version' => $image['prompt_version'],
                'failure_reason' => null,
            ]);

            return response()->json([
                'activity' => $this->payload($activity->fresh()),
                'quota' => $this->quota($psychologistId),
            ], 201);
        } catch (RuntimeException $exception) {
            $activity?->update(['status' => 'failed', 'failure_reason' => $exception->getMessage()]);
            GameKitVisualActivityUsage::whereKey($usage->id)->decrement('generated_count');

            return response()->json(['message' => $exception->getMessage()], 422);
        }
    }

    public function download(Request $request, int $id)
    {
        $activity = $this->owned($request, $id);
        abort_if($activity->status !== 'ready' || ! $activity->storage_path, 404);

        return response()->file(Storage::disk('public')->path($activity->storage_path), [
            'Content-Type' => $activity->mime_type ?? 'image/png',
            'Content-Disposition' => 'inline; filename="atividade-visual-'.$activity->id.'.png"',
        ]);
    }

    public function publish(Request $request, int $id)
    {
        $activity = $this->owned($request, $id);
        abort_if($activity->status !== 'ready' || ! $activity->storage_path, 422, 'Gere uma imagem antes de compartilhar.');
        $token = Str::random(64);
        $activity->update([
            'public_token_hash' => hash('sha256', $token),
            'public_token_expires_at' => now()->addHours(24),
            'public_status' => 'active',
        ]);

        return response()->json([
            'url' => rtrim(config('app.frontend_url', config('app.url')), '/').'/gamekit/visual/play/'.$token,
            'expires_at' => $activity->public_token_expires_at,
        ]);
    }

    public function archive(Request $request, int $id)
    {
        $activity = $this->owned($request, $id);
        $activity->update(['status' => 'archived', 'public_status' => 'revoked']);

        return response()->json(['status' => 'archived']);
    }

    public function publicView(string $token)
    {
        $activity = GameKitVisualActivity::where('public_token_hash', hash('sha256', $token))
            ->where('status', 'ready')
            ->where('public_status', 'active')
            ->where('public_token_expires_at', '>', now())
            ->firstOrFail();

        return response()->json([
            'activity' => [
                'id' => $activity->id,
                'type' => $activity->type,
                'theme' => $activity->theme,
                'image_url' => route('gamekit.visual.public-image', ['token' => $token]),
                'instruction' => match ($activity->type) {
                    'cutting' => 'Recorte seguindo as linhas indicadas.',
                    'coloring_cutting' => 'Pinte a imagem e depois recorte nas linhas indicadas.',
                    default => 'Pinte a imagem do seu jeito.',
                },
            ],
        ]);
    }

    public function publicImage(string $token)
    {
        $activity = $this->publicActivity($token);

        return response()->file(Storage::disk('public')->path($activity->storage_path), [
            'Content-Type' => $activity->mime_type ?? 'image/png',
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    private function publicActivity(string $token): GameKitVisualActivity
    {
        return GameKitVisualActivity::where('public_token_hash', hash('sha256', $token))
            ->where('status', 'ready')
            ->where('public_status', 'active')
            ->where('public_token_expires_at', '>', now())
            ->firstOrFail();
    }

    private function payload(GameKitVisualActivity $activity): array
    {
        return [
            'id' => $activity->id,
            'type' => $activity->type,
            'style' => $activity->style,
            'theme' => $activity->theme,
            'status' => $activity->status,
            'mime_type' => $activity->mime_type,
            'width' => $activity->width,
            'height' => $activity->height,
            'created_at' => $activity->created_at,
            'failure_reason' => $activity->failure_reason,
            'image_url' => $activity->status === 'ready' ? route('gamekit.visual.download', ['id' => $activity->id]) : null,
            'public_active' => $activity->public_status === 'active' && $activity->public_token_expires_at?->isFuture(),
        ];
    }

    private function quota(int $psychologistId): array
    {
        $weekStart = now()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $usage = GameKitVisualActivityUsage::where('psychologist_id', $psychologistId)->whereDate('week_start', $weekStart->toDateString())->first();
        $used = (int) ($usage?->generated_count ?? 0);

        return [
            'used' => $used,
            'limit' => self::LIMIT,
            'remaining' => max(0, self::LIMIT - $used),
            'resets_at' => now()->endOfWeek(Carbon::SUNDAY)->addSecond()->toIso8601String(),
        ];
    }

    private function owned(Request $request, int $id): GameKitVisualActivity
    {
        return GameKitVisualActivity::where('psychologist_id', $this->psychologistId($request))->findOrFail($id);
    }

    private function rejectSensitiveInput(string $value): void
    {
        if (filter_var($value, FILTER_VALIDATE_EMAIL) || preg_match('/\b\d{3}\.?\d{3}\.?\d{3}-?\d{2}\b/', $value)) {
            abort(422, 'Remova dados pessoais do tema ou objetivo terapêutico.');
        }
    }

    private function psychologistId(Request $request): int
    {
        $id = $request->user()->loadMissing('psychologist')->psychologist?->id;
        abort_if(! $id, 403, 'Perfil de psicólogo não encontrado.');

        return (int) $id;
    }
}
