<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('issue_action_plans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('issue_id')->constrained('issues')->onDelete('cascade');
            $table->text('description');
            $table->string('status')->default('opened');
            $table->foreignId('pic_staff_id')->nullable()->constrained('staff')->onDelete('set null');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['issue_id', 'sort_order']);
        });

        $now = now();

        DB::table('issues')
            ->select(['id', 'description'])
            ->orderBy('id')
            ->chunkById(200, function ($issues) use ($now): void {
                foreach ($issues as $issue) {
                    $alreadyMigrated = DB::table('issue_action_plans')
                        ->where('issue_id', $issue->id)
                        ->exists();

                    if ($alreadyMigrated) {
                        continue;
                    }

                    $plans = $this->extractLegacyPlans($issue->description);

                    if (empty($plans)) {
                        continue;
                    }

                    $rows = [];

                    foreach ($plans as $index => $plan) {
                        $rows[] = [
                            'issue_id' => $issue->id,
                            'description' => $plan['description'],
                            'status' => $plan['status'],
                            'pic_staff_id' => $plan['pic_staff_id'],
                            'sort_order' => $index,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }

                    DB::table('issue_action_plans')->insert($rows);
                }
            }, 'id');
    }

    public function down(): void
    {
        Schema::dropIfExists('issue_action_plans');
    }

    /**
     * @return array<int, array{description:string,status:string,pic_staff_id:int|null}>
     */
    private function extractLegacyPlans(mixed $legacyDescription): array
    {
        if (!is_string($legacyDescription) || trim($legacyDescription) === '') {
            return [];
        }

        $decoded = json_decode($legacyDescription, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return collect($decoded)
                ->map(function (mixed $item): ?array {
                    if (!is_array($item)) {
                        return null;
                    }

                    $description = $this->normalizeText((string) ($item['description'] ?? ''));
                    if ($description === '') {
                        return null;
                    }

                    $status = $this->normalizeStatus((string) ($item['status'] ?? 'opened'));
                    $picRaw = $item['pic_staff_id'] ?? $item['pic'] ?? null;
                    $pic = is_numeric($picRaw) ? (int) $picRaw : null;

                    return [
                        'description' => $description,
                        'status' => $status,
                        'pic_staff_id' => $pic,
                    ];
                })
                ->filter()
                ->values()
                ->all();
        }

        $plainText = $this->normalizeText($legacyDescription);

        if ($plainText === '') {
            return [];
        }

        return [[
            'description' => $plainText,
            'status' => 'opened',
            'pic_staff_id' => null,
        ]];
    }

    private function normalizeText(string $text): string
    {
        $decoded = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $decoded = str_replace("\u{00A0}", ' ', $decoded);
        $decoded = trim(strip_tags($decoded));

        return trim((string) preg_replace('/\s+/', ' ', $decoded));
    }

    private function normalizeStatus(string $status): string
    {
        $normalized = Str::of($status)
            ->lower()
            ->replace('-', '_')
            ->replace(' ', '_')
            ->trim()
            ->toString();

        return match ($normalized) {
            'open', 'todo', 'to_do', 'backlog', 'duplicate' => 'opened',
            'in_progress' => 'progress',
            'close', 'done' => 'closed',
            'postpone', 'canceled', 'cancelled' => 'postponed',
            'opened', 'progress', 'closed', 'overdue', 'postponed' => $normalized,
            default => 'opened',
        };
    }
};

