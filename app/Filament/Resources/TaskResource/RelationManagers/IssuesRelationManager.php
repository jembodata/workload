<?php

namespace App\Filament\Resources\TaskResource\RelationManagers;

use App\Models\Staff;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class IssuesRelationManager extends RelationManager
{
    protected static string $relationship = 'issues';

    public function form(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\RichEditor::make('issue_name')
                            ->label('Issue')
                            ->placeholder('Apa kendala yang dihadapi?')
                            ->required()
                            ->toolbarButtons([
                                'bold',
                                'bulletList',
                                'orderedList',
                                'h2',
                                'h3',
                                'italic',
                            ])
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\Repeater::make('actionPlans')
                            ->label('Action Plan')
                            ->relationship('actionPlans')
                            ->orderColumn('sort_order')
                            ->columns(12)
                            ->cloneable()
                            ->schema([
                                Forms\Components\RichEditor::make('description')
                                    ->label('Description')
                                    ->placeholder('Jelaskan detail action plan di sini...')
                                    ->toolbarButtons([
                                        'bold',
                                        'bulletList',
                                        'orderedList',
                                        'italic',
                                    ])
                                    ->columnSpanFull(),
                                Forms\Components\Select::make('status')
                                    ->label('Status')
                                    ->native(false)
                                    ->position('top')
                                    ->extraAttributes(['class' => 'issue-status-select'])
                                    ->default('opened')
                                    ->allowHtml()
                                    ->options(static::statusSelectOptions())
                                    // ->options([
                                    //     'opened' => 'Opened',
                                    //     'progress' => 'Progress',
                                    //     'closed' => 'Closed',
                                    //     'overdue' => 'Overdue',
                                    //     'postponed' => 'Postponed',
                                    // ])
                                    ->required()
                                    ->columnSpan([
                                        'default' => 12,
                                        'md' => 6,
                                    ]),
                                Forms\Components\Select::make('pic_staff_id')
                                    ->label('PIC')
                                    ->native(false)
                                    ->searchable()
                                    ->preload()
                                    ->options(fn() => Staff::query()->orderBy('name')->pluck('name', 'id')->toArray())
                                    ->columnSpan([
                                        'default' => 12,
                                        'md' => 6,
                                    ]),
                            ])
                            ->defaultItems(1)
                            ->addActionLabel('Tambah Action Plan')
                            ->collapsible()
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Attributes')
                    ->compact()
                    ->columns(2) // Membagi menjadi 2 kolom
                    ->schema([
                        Forms\Components\Select::make('staff_id')
                            ->label('Assignee')
                            ->relationship('staff', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->placeholder('Pilih orang...'),

                        Forms\Components\DatePicker::make('due_date')
                            ->label('Due Date')
                            ->native(false)
                            ->displayFormat('d M Y')
                            ->closeOnDateSelection(),
                        // ->maxDate(fn($livewire) => $livewire->ownerRecord?->tanggal),

                        Forms\Components\Select::make('priority')
                            ->label('Priority')
                            ->native(false)
                            ->allowHtml()
                            ->default('none')
                            ->selectablePlaceholder(false)
                            ->options(function () {
                                $priorities = [
                                    'urgent' => [
                                        'label' => 'Urgent',
                                        'icon' => 'alert-circle',
                                        'color' => '#ef4444',
                                        'rating' => '1'
                                    ],
                                    'high' => [
                                        'label' => 'High',
                                        'icon' => 'signal-high',
                                        'color' => '#f97316',
                                        'rating' => '2'
                                    ],
                                    'medium' => [
                                        'label' => 'Medium',
                                        'icon' => 'signal-medium',
                                        'color' => '#eab308',
                                        'rating' => '3'
                                    ],
                                    'low' => [
                                        'label' => 'Low',
                                        'icon' => 'signal-low',
                                        'color' => '#22c55e',
                                        'rating' => '4'
                                    ],
                                    'none' => [
                                        'label' => 'No Priority',
                                        'icon' => 'minus',
                                        'color' => '#9ca3af',
                                        'rating' => '0'
                                    ],
                                ];

                                $options = [];
                                foreach ($priorities as $key => $data) {
                                    // Kita menggunakan Helper lucide dari paket blade-lucide-icons jika terinstall, 
                                    // atau menggunakan URL CDN svg jika ingin simpel tanpa install plugin.
                                    $options[$key] = "
                                    <div style='display:flex; align-items:center; width:100%; min-width:100px;'>
                                        <div style='display:flex; align-items:center; gap:10px;'>
                                            <img src='https://unpkg.com/lucide-static@latest/icons/{$data['icon']}.svg' 
                                                style='width:1.1rem; height:1.1rem; filter: invert(30%) sepia(100%) saturate(500%) hue-rotate(0deg);' 
                                                alt='icon' />
                                            <span style='font-size: 0.9rem;'>{$data['label']}</span>
                                        </div>
                                        <div style='flex-grow: 1;'></div>
                                        <span style='opacity:0.4; font-family:monospace; font-size: 0.85rem;'>{$data['rating']}</span>
                                    </div>";
                                }
                                return $options;
                            }),

                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->native(false)
                            ->position('top')
                            ->extraAttributes(['class' => 'issue-status-select'])
                            ->default('opened')
                            ->selectablePlaceholder(false)
                            // ->options([
                            //     'opened' => 'Opened',
                            //     'progress' => 'Progress',
                            //     'closed' => 'Closed',
                            //     'overdue' => 'Overdue',
                            //     'postponed' => 'Postponed',
                            // ])
                            ->allowHtml()
                            ->options(static::statusSelectOptions()),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('issue_name')
            ->modifyQueryUsing(fn (Builder $query) => $query->with(['actionPlans.pic']))
            ->defaultSort('priority', 'asc')
            ->groups([
                Tables\Grouping\Group::make('status')
                    ->label('Status')
                    ->collapsible(),
            ])
            ->defaultGroup('status')
            ->columns([

                Tables\Columns\TextColumn::make('priority')
                    ->label('')
                    ->width('60px')
                    ->formatStateUsing(function (string $state): \Illuminate\Support\HtmlString {
                        $priorities = [
                            'urgent' => ['icon' => 'alert-circle',   'color' => '#ef4444', 'rating' => '1'],
                            'high'   => ['icon' => 'signal-high',    'color' => '#f97316', 'rating' => '2'],
                            'medium' => ['icon' => 'signal-medium',  'color' => '#eab308', 'rating' => '3'],
                            'low'    => ['icon' => 'signal-low',     'color' => '#22c55e', 'rating' => '4'],
                            'none'   => ['icon' => 'minus',          'color' => '#9ca3af', 'rating' => '0'],
                        ];

                        $config = $priorities[$state] ?? $priorities['none'];

                        return new \Illuminate\Support\HtmlString("
                            <div style='display:flex; align-items:center; gap:6px;'>
                                <img src='https://unpkg.com/lucide-static@latest/icons/{$config['icon']}.svg' 
                                                style='width:1.1rem; height:1.1rem; filter: invert(30%) sepia(100%) saturate(500%) hue-rotate(0deg);' 
                                                alt='icon' />
                                <span style='opacity:0.5; font-family:monospace; font-size:0.8rem;'>{$config['rating']}</span>
                            </div>
                        ");
                    })
                    ->sortable(), // Pastikan kamu bisa sort berdasarkan rating

                Tables\Columns\TextColumn::make('issue_name')
                    ->label('Issue')
                    ->limit(60)
                    ->width('300px')
                    ->searchable()
                    ->grow(false)
                    // ->description(fn(Model $record) => new HtmlString(Str::limit($this->actionPlanPreviewText($record), 60)))
                    // ->tooltip(fn(Model $record): string => $this->actionPlanPreviewText($record))
                    ->formatStateUsing(fn(string $state): string => strip_tags($state)),

                Tables\Columns\TextColumn::make('status')
                    // ->label(new HtmlString('Evaluasi <br/> Efektivitas'))
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'opened'    => 'Opened',
                        'progress'  => 'Progress',
                        'closed'    => 'Closed',
                        'overdue'   => 'Overdue',
                        'postponed' => 'Postponed',
                        default     => ucfirst($state),
                    })
                    ->icon(fn(string $state): string => match ($state) {
                        'opened'    => 'heroicon-o-play-circle',
                        'progress'  => 'heroicon-o-arrow-path',
                        'closed'    => 'heroicon-o-check-circle',
                        'overdue'   => 'heroicon-o-x-circle',
                        'postponed' => 'heroicon-o-pause-circle',
                        default     => 'heroicon-o-information-circle',
                    })
                    ->color(fn(string $state): string => match ($state) {
                        'opened'    => 'info',
                        'progress'  => 'warning',
                        'closed'    => 'success',
                        'overdue'   => 'danger',
                        'postponed' => 'gray',
                        default     => 'primary',
                    }),

                Tables\Columns\TextColumn::make('action_plan_count')
                    ->label('Action Plan')
                    ->state(fn (Model $record): int => $this->actionPlanCount($record))
                    ->badge()
                    ->alignCenter(),

                // Assignee (Avatar User)
                Tables\Columns\ImageColumn::make('staff.avatar_url')
                    ->label('Assignee')
                    ->circular()
                    ->defaultImageUrl(fn($record) => "https://ui-avatars.com/api/?name=" . urlencode($record->staff?->name) . "&color=FFFFFF&background=030712"),

                // Due Date
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date('d M Y')
                    ->sortable()
                    ->color(fn($record) => $record->due_date?->isPast() ? 'danger' : 'gray'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->slideOver()
                    ->createAnother(false)
                    ->closeModalByClickingAway(false)
                    ->modalWidth(MaxWidth::FiveExtraLarge),
            ])
            ->actions([
                Tables\Actions\Action::make('viewActionPlans')
                    ->label('View Action Plan')
                    ->icon('heroicon-o-list-bullet')
                    ->color('gray')
                    ->slideOver()
                    ->closeModalByClickingAway(true)
                    ->modalWidth(MaxWidth::FiveExtraLarge)
                    ->modalHeading('Action Plan')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close')
                    ->modalContent(fn (Model $record): HtmlString => new HtmlString($this->renderActionPlanListHtml($record))),
                Tables\Actions\EditAction::make()
                    ->slideOver()
                    ->closeModalByClickingAway(false)
                    ->modalWidth(MaxWidth::FiveExtraLarge),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected function actionPlanPreviewText(Model $record): string
    {
        $plans = $record->relationLoaded('actionPlans')
            ? $record->actionPlans
            : $record->actionPlans()->orderBy('sort_order')->get();

        if ($plans->isNotEmpty()) {
            return $plans
                ->map(fn ($plan) => $this->normalizeDescriptionText((string) ($plan->description ?? '')))
                ->filter()
                ->implode(' ');
        }

        return '';
    }

    protected function legacyDescriptionPreviewText(mixed $legacyDescription): string
    {
        if (!is_string($legacyDescription) || trim($legacyDescription) === '') {
            return '';
        }

        $decoded = json_decode($legacyDescription, true);

        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return collect($decoded)
                ->map(fn (mixed $item) => is_array($item) ? $this->normalizeDescriptionText((string) ($item['description'] ?? '')) : '')
                ->filter()
                ->implode(' ');
        }

        return $this->normalizeDescriptionText($legacyDescription);
    }

    protected function normalizeDescriptionText(string $text): string
    {
        $decoded = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $decoded = str_replace("\u{00A0}", ' ', $decoded);
        $decoded = trim(strip_tags($decoded));

        return trim((string) preg_replace('/\s+/', ' ', $decoded));
    }

    protected function actionPlanCount(Model $record): int
    {
        $plans = $record->relationLoaded('actionPlans')
            ? $record->actionPlans
            : $record->actionPlans()->orderBy('sort_order')->get();

        return $plans
            ->filter(fn ($plan) => $this->normalizeDescriptionText((string) ($plan->description ?? '')) !== '')
            ->count();
    }

    /**
     * @return array<int, array{description:string,status:string,pic:string}>
     */
    protected function getActionPlanList(Model $record): array
    {
        $plans = $record->relationLoaded('actionPlans')
            ? $record->actionPlans
            : $record->actionPlans()->orderBy('sort_order')->get();

        if ($plans->isNotEmpty()) {
            return $plans
                ->map(function ($plan): ?array {
                    $rawDescription = (string) ($plan->description ?? '');
                    $description = $this->normalizeDescriptionText($rawDescription);

                    if ($description === '') {
                        return null;
                    }

                    $status = (string) ($plan->status ?? 'opened');
                    $pic = (string) ($plan->pic?->name ?? '-');

                    return [
                        'description' => $rawDescription,
                        'status' => $status,
                        'pic' => $pic,
                    ];
                })
                ->filter()
                ->values()
                ->all();
        }

        return [];
    }

    protected function renderActionPlanListHtml(Model $record): string
    {
        $plans = $this->getActionPlanList($record);

        if (empty($plans)) {
            return "<div style='padding:.25rem 0;color:#6b7280;'>Belum ada action plan.</div>";
        }

        $html = "<div style='display:flex;flex-direction:column;gap:.5rem;padding:.25rem .125rem;'>";

        foreach ($plans as $index => $plan) {
            $description = $this->renderActionPlanDescriptionHtml((string) ($plan['description'] ?? ''));
            $status = e(ucfirst((string) ($plan['status'] ?? 'opened')));
            $pic = e((string) ($plan['pic'] ?? '-'));

            $html .= "
                <div style='border:1px solid #e5e7eb;border-radius:.5rem;padding:.625rem .75rem;'>
                    <div style='font-weight:600;margin-bottom:.25rem;'>#" . ($index + 1) . "</div>
                    <div style='font-size:.925rem;line-height:1.45;'>
                        <style>
                            .issue-action-plan-content p { margin: 0 0 .5rem 0; }
                            .issue-action-plan-content ul,
                            .issue-action-plan-content ol {
                                margin: .35rem 0 .55rem 1.2rem;
                                padding-left: .8rem;
                            }
                            .issue-action-plan-content ul { list-style: disc outside !important; }
                            .issue-action-plan-content ol { list-style: decimal outside !important; }
                            .issue-action-plan-content ol li,
                            .issue-action-plan-content ul li {
                                display: list-item !important;
                            }
                            .issue-action-plan-content li { margin: .15rem 0; }
                            .issue-action-plan-content h1,
                            .issue-action-plan-content h2,
                            .issue-action-plan-content h3,
                            .issue-action-plan-content h4,
                            .issue-action-plan-content h5,
                            .issue-action-plan-content h6 { margin: .4rem 0 .25rem; font-weight: 600; }
                            .issue-action-plan-content blockquote {
                                margin: .4rem 0;
                                padding: .25rem .6rem;
                                border-left: 3px solid #e5e7eb;
                                color: #4b5563;
                                background: #f9fafb;
                            }
                            .issue-action-plan-content a { color: #2563eb; text-decoration: underline; }
                        </style>
                        <div class='issue-action-plan-content'>$description</div>
                    </div>
                    <div style='margin-top:.375rem;font-size:.8rem;color:#6b7280;display:flex;gap:.75rem;flex-wrap:wrap;'>
                        <span>Status: <strong style='color:#111827;'>$status</strong></span>
                        <span>PIC: <strong style='color:#111827;'>$pic</strong></span>
                    </div>
                </div>
            ";
        }

        $html .= "</div>";

        return $html;
    }

    /**
     * @return array<int, array{description:string,status:string,pic:string}>
     */
    protected function legacyActionPlansFromDescription(mixed $legacyDescription): array
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

                    $rawDescription = (string) ($item['description'] ?? '');
                    $description = $this->normalizeDescriptionText($rawDescription);
                    if ($description === '') {
                        return null;
                    }

                    return [
                        'description' => $rawDescription,
                        'status' => (string) ($item['status'] ?? 'opened'),
                        'pic' => '-',
                    ];
                })
                ->filter()
                ->values()
                ->all();
        }

        $plainText = $this->normalizeDescriptionText($legacyDescription);
        if ($plainText === '') {
            return [];
        }

        return [[
            'description' => e($plainText),
            'status' => 'opened',
            'pic' => '-',
        ]];
    }

    protected function renderActionPlanDescriptionHtml(string $raw): string
    {
        $decoded = html_entity_decode($raw, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $decoded = str_replace("\u{00A0}", ' ', $decoded);
        $decoded = preg_replace('#<(script|style)\b[^>]*>.*?</\1>#is', '', $decoded) ?? $decoded;
        $decoded = preg_replace('/\s+on[a-z]+\s*=\s*([\'"]).*?\1/iu', '', $decoded) ?? $decoded;
        $decoded = preg_replace('/\s+on[a-z]+\s*=\s*[^>\s]+/iu', '', $decoded) ?? $decoded;
        $decoded = preg_replace('/(href|src)\s*=\s*([\'"])\s*javascript:[^\'"]*\2/iu', '$1="#"', $decoded) ?? $decoded;
        $safeHtml = trim($decoded);

        if ($safeHtml === '') {
            return '<span style="color:#6b7280;">-</span>';
        }

        if ($safeHtml === strip_tags($safeHtml)) {
            return nl2br(e($safeHtml), false);
        }

        return $safeHtml;
    }

    private static function statusSelectOptions(): array
    {
        $statuses = [
            'opened' => [
                'label' => 'Opened',
                'icon' => 'play-circle',
            ],
            'progress' => [
                'label' => 'Progress',
                'icon' => 'loader-circle',
            ],
            'closed' => [
                'label' => 'Closed',
                'icon' => 'check-circle-2',
            ],
            'overdue' => [
                'label' => 'Overdue',
                'icon' => 'x-circle',
            ],
            'postponed' => [
                'label' => 'Postponed',
                'icon' => 'pause-circle',
            ],
        ];

        $options = [];

        foreach ($statuses as $key => $status) {
            $options[$key] = sprintf(
                "<div style='display:flex;align-items:center;gap:10px;width:100%%;'>
                    <img src='https://unpkg.com/lucide-static@latest/icons/%s.svg' alt='%s' style='width:14px;height:14px;opacity:.75;' />
                    <span style='font-size:13px;'>%s</span>
                </div>",
                $status['icon'],
                $status['label'],
                $status['label']
            );
        }

        return $options;
    }
}
