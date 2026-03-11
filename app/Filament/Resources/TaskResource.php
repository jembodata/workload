<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TaskResource\Pages;
// use Guava\FilamentModalRelationManagers\Actions\RelationManagerAction;
use App\Filament\Resources\TaskResource\RelationManagers\IssuesRelationManager;
use App\Models\Task;
use App\Models\Issue;
use Carbon\CarbonPeriod;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Support\Enums\MaxWidth;
use Filament\Notifications\Notification;
use Guava\FilamentModalRelationManagers\Actions\Table\RelationManagerAction;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\HtmlString;

class TaskResource extends Resource
{
    protected static ?string $model = Task::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $query = parent::getEloquentQuery();

        Task::where('is_long_term', true)
            ->whereNotIn('status', ['closed', 'postponed', 'opened'])
            ->whereDate('tanggal_akhir', '<', now()->toDateString())
            ->update(['status' => 'overdue']);

        Task::where('is_long_term', false)
            ->whereNotIn('status', ['closed', 'postponed', 'opened'])
            ->whereDate('tanggal', '<', now()->toDateString())
            ->update(['status' => 'overdue']);

        return $query;
    }

    // Form schema di bawah ini dipisah dari method form() agar bisa dipanggil di RelationManager lain, misal TasksRelationManager
    public static function form(Form $form): Form
    {
        return $form->schema(static::getTaskFormSchema());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->striped()
            ->heading('Status')
            ->defaultGroup('project.project_name')
            // ->header(view('tables.legend'))

            ->columns([

                Tables\Columns\TextColumn::make('task_name')
                    ->label('Item')
                    ->searchable()
                    ->wrap(),

                Tables\Columns\TextInputColumn::make('output')
                    ->label('Task')
                    // ->grow(true)
                    ->width('300px')
                    ->tooltip(fn(Model $record): string => "{$record->output}")
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('staff.name')
                    ->label('PIC')
                    ->searchable(),

                // Indikator long term
                Tables\Columns\TextColumn::make('is_long_term')
                    ->label(new HtmlString('Long <br/> Term'))
                    ->Badge()
                    ->color(fn($state) => $state ? 'success' : 'danger')
                    ->formatStateUsing(fn($state) => $state ? 'Yes' : 'No')
                    ->toggleable(isToggledHiddenByDefault: true),

                // Jika bukan long term → tampilkan tanggal & estimasi jam
                Tables\Columns\TextColumn::make('tanggal')
                    ->label('Target')
                    ->date()
                    ->sortable()
                    ->hidden(fn($record) => $record?->is_long_term)
                    ->toggleable(isToggledHiddenByDefault: false),


                Tables\Columns\TextColumn::make('tanggal_akhir')
                    ->label(new HtmlString('Tanggal <br/> Akhir'))
                    ->placeholder('0')
                    ->date()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->hidden(fn($record) => $record?->is_long_term),

                Tables\Columns\TextColumn::make('estimasi_jam')
                    ->label('Workload')
                    ->placeholder('0')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->hidden(fn($record) => $record?->is_long_term),

                Tables\Columns\TextColumn::make('allocation_hours')
                    ->label(new HtmlString('Alokasi <br/> Jam'))
                    ->formatStateUsing(fn($state) => $state ?? 0) // kalau null → 0
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->hidden(fn($record) => $record?->is_long_term),


                Tables\Columns\TextColumn::make('priority')
                    ->label('Priority')
                    ->Badge()
                    ->toggleable(isToggledHiddenByDefault: false)
                    ->colors([
                        'danger' => 'urgent',
                        'warning' => 'high',
                        'info' => 'medium',
                        'success' => 'low',
                        'teal' => 'not_priority',
                    ])
                    ->formatStateUsing(fn($state) => match ($state) {
                        'urgent' => 'Urgent (1)',
                        'high' => 'High (2)',
                        'medium' => 'Medium (3)',
                        'low' => 'Low (4)',
                        'not_priority' => 'Not Priority (0)',
                        default => $state,
                    }),

                Tables\Columns\SelectColumn::make('progress')
                    ->label('%')
                    ->grow(false)
                    ->width('50px') // Membatasi lebar kolom tabel (td)
                    ->alignment(\Filament\Support\Enums\Alignment::Center) // Menengahkan isi dan header
                    ->options(
                        collect(range(0, 100, 5))
                            ->mapWithKeys(fn($i) => [$i => $i . '%'])
                            ->toArray()
                    )
                    ->selectablePlaceholder(false)
                    ->extraAttributes([
                        // Memaksa lebar komponen select dan mengurangi padding dalamnya
                        'style' => 'width: 45px; margin: 0 auto; font-size: 0.85rem;',
                    ])
                    ->extraCellAttributes([
                        // Menghilangkan padding horizontal pada cell agar lebih rapat
                        'class' => 'px-1',
                    ]),

                // Tables\Columns\SelectColumn::make('progress')
                //     ->label('%')
                //     ->grow(false)
                //     ->width('10px')
                //     ->selectablePlaceholder(false)
                //     ->options(
                //         collect(range(0, 100, 5))
                //             ->mapWithKeys(fn($i) => [$i => $i . '%'])
                //             ->toArray()
                //     )
                //     ->extraAttributes(['class' => 'w-8 text-center'])
                //     ->toggleable(isToggledHiddenByDefault: false),


                Tables\Columns\TextColumn::make('status')
                    ->label(new HtmlString('Evaluasi <br/> Efektivitas'))
                    ->badge()
                    ->formatStateUsing(fn($state) => match ($state) {
                        'opened' => 'Opened',
                        'progress' => 'Progress',
                        'closed' => 'Closed',
                        'overdue' => 'Overdue',
                        'postponed' => 'Postponed',
                        default => ucfirst($state),
                    })

                    ->colors([
                        'primary' => 'opened',
                        'warning' => 'progress',
                        'success' => 'closed',
                        'danger'  => 'overdue',
                        'cyan'    => 'postponed', // status "postponed" pakai warna biru (#000080)
                    ]),


                Tables\Columns\TextColumn::make('total_overdue')
                    ->label(new HtmlString('Total <br/> Overdue'))
                    ->badge()
                    ->color(fn($state) => $state > 0 ? 'danger' : 'zinc')
                    ->toggleable(isToggledHiddenByDefault: false),

                Tables\Columns\TextColumn::make('issues_count')
                    ->counts('issues')
                    ->label(new HtmlString('Total <br/> Issues'))
                    ->badge()
                    ->color(fn($state) => $state > 0 ? 'warning' : 'gray')
                    ->sortable(),
            ])

            ->filters([

                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'opened' => 'Opened',
                        'progress' => 'Progress',
                        'closed' => 'Closed',
                        'overdue' => 'Overdue',
                        'postponed' => 'Postponed',
                    ])
                    ->multiple(),

                Tables\Filters\SelectFilter::make('staff.name')
                    ->relationship('staff', 'name')
                    ->label('PIC')
                    ->searchable()
                    ->preload()
                    ->multiple(),

                Tables\Filters\SelectFilter::make('priority')
                    ->options([
                        'urgent'       => 'Urgent',
                        'high'         => 'High',
                        'medium'       => 'Medium',
                        'low'          => 'Low',
                        'not_priority' => 'Not Priority',
                    ])
                    ->multiple(),

                DateRangeFilter::make('tanggal')
                    ->label('Tanggal Project'),

                Tables\Filters\SelectFilter::make('is_long_term')
                    ->native(false)
                    ->label('Long Term')
                    ->options([
                        true  => 'Yes',
                        false => 'No',
                    ]),
            ])


            ->headerActions([
                Tables\Actions\Action::make('legend')
                    ->label('Petunjuk Warna')
                    ->view('tables.legend'),
            ])

            ->actions([
                // Tables\Actions\EditAction::make()
                //     ->slideOver()
                //     ->modalWidth(MaxWidth::Medium),

                RelationManagerAction::make('issues-relation-manager')
                    ->label('View issues')
                    ->slideOver()
                    ->closeModalByClickingAway(false)
                    ->icon('heroicon-m-document-magnifying-glass')
                    ->relationManager(IssuesRelationManager::make()),

                Tables\Actions\EditAction::make()
                    ->slideOver()
                    ->closeModalByClickingAway(false)
                    ->modalWidth(MaxWidth::Medium),

                Tables\Actions\ViewAction::make()
                    ->slideOver()
                    ->closeModalByClickingAway(false)
                    ->modalWidth(MaxWidth::Medium),

                Tables\Actions\ReplicateAction::make()
                    ->form(fn(Form $form) => static::form($form)->columns(2))
                    ->slideOver()
                    ->modalWidth(MaxWidth::Medium),

                Tables\Actions\Action::make('closed')
                    ->label('Closed')
                    ->color('success')
                    ->icon('heroicon-o-check')
                    // ->requiresConfirmation()
                    ->visible(fn($record) => $record->status !== 'closed')
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'closed',
                        ]);

                        Notification::make()
                            ->title('Task berhasil diupdate')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\ExportBulkAction::make()
                        ->label('Export Data')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->exporter(\App\Filament\Exports\TasksExporter::class),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
            IssuesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTasks::route('/'),
            //'create' => Pages\CreateTask::route('/create'),
            // 'edit' => Pages\EditTask::route('/{record}/edit'),
        ];
    }

    // Form schema untuk Task (bisa dipanggil di RelationManager lain, misal TasksRelationManager)
    public static function getTaskFormSchema(): array
    {
        return [

            Forms\Components\Fieldset::make('Task')
                ->schema([
                    Forms\Components\Select::make('project_id')
                        ->relationship('project', 'project_name')
                        ->label('Project')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->createOptionForm([
                            Forms\Components\Section::make('Informasi Proyek')
                                ->description('Detail utama mengenai proyek baru.')
                                ->schema([
                                    Forms\Components\TextInput::make('project_name')
                                        ->label('Nama Proyek')
                                        ->required()
                                        ->maxLength(255)
                                        ->columnSpanFull(),

                                    Forms\Components\TextInput::make('client_name')
                                        ->label('Nama Klien')
                                        ->maxLength(255)
                                        ->columnSpanFull(),

                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\DatePicker::make('start_date')
                                                ->label('Tanggal Mulai')
                                                ->native(false)
                                                ->closeOnDateSelection(),

                                            Forms\Components\DatePicker::make('end_date')
                                                ->label('Tanggal Selesai')
                                                ->native(false)
                                                ->closeOnDateSelection()
                                                ->afterOrEqual('start_date'), // Mencegah tgl selesai mendahului tgl mulai
                                        ]),
                                ]),

                            Forms\Components\Section::make('Status & Kesehatan')
                                ->schema([
                                    Forms\Components\Grid::make(2)
                                        ->schema([
                                            Forms\Components\Select::make('status')
                                                ->label('Status Proyek')
                                                ->options([
                                                    'planned' => 'Planned',
                                                    'active' => 'Active',
                                                    'on_hold' => 'On Hold',
                                                    'completed' => 'Completed',
                                                ])
                                                ->default('planned')
                                                ->native(false)
                                                ->required(),

                                            Forms\Components\Select::make('health')
                                                ->label('Kesehatan Proyek')
                                                ->native(false)
                                                ->allowHtml()
                                                ->default('on_track')
                                                ->selectablePlaceholder(false)
                                                ->required()
                                                ->options(function () {
                                                    $healths = [
                                                        'on_track' => [
                                                            'label' => 'On Track',
                                                            'icon' => 'check-circle',
                                                            'color' => '#22c55e', // Green
                                                            'rating' => '1'
                                                        ],
                                                        'at_risk' => [
                                                            'label' => 'At Risk',
                                                            'icon' => 'alert-triangle',
                                                            'color' => '#eab308', // Yellow
                                                            'rating' => '2'
                                                        ],
                                                        'off_track' => [
                                                            'label' => 'Off Track',
                                                            'icon' => 'alert-circle',
                                                            'color' => '#ef4444', // Red
                                                            'rating' => '3'
                                                        ],
                                                    ];

                                                    $options = [];
                                                    foreach ($healths as $key => $data) {
                                                        // Kita gunakan filter CSS untuk memberikan warna pada SVG dari Lucide CDN
                                                        $colorFilter = match ($key) {
                                                            'on_track' => 'invert(60%) sepia(50%) saturate(1000%) hue-rotate(100deg) brightness(90%) contrast(90%)',
                                                            'at_risk' => 'invert(80%) sepia(80%) saturate(1000%) hue-rotate(10deg) brightness(100%) contrast(100%)',
                                                            'off_track' => 'invert(40%) sepia(90%) saturate(2000%) hue-rotate(340deg) brightness(90%) contrast(100%)',
                                                            default => ''
                                                        };

                                                        $options[$key] = "
                                                            <div style='display:flex; align-items:center; width:100%; min-width:150px;'>
                                                                <div style='display:flex; align-items:center; gap:10px;'>
                                                                    <img src='https://unpkg.com/lucide-static@latest/icons/{$data['icon']}.svg' 
                                                                        style='width:1.1rem; height:1.1rem; {$colorFilter}' 
                                                                        alt='icon' />
                                                                    <span style='font-size: 0.9rem;'>{$data['label']}</span>
                                                                </div>
                                                                <div style='flex-grow: 1;'></div>
                                                                <span style='opacity:0.4; font-family:monospace; font-size: 0.85rem;'>{$data['rating']}</span>
                                                            </div>";
                                                    }
                                                    return $options;
                                                }),
                                        ]),

                                    Forms\Components\Textarea::make('description')
                                        ->label('Deskripsi')
                                        ->rows(3)
                                        ->columnSpanFull(),
                                ]),
                        ])
                        // Konfigurasi agar form muncul sebagai Slide Over
                        ->createOptionAction(
                            fn(\Filament\Forms\Components\Actions\Action $action) =>
                            $action->slideOver()
                                ->modalWidth(\Filament\Support\Enums\MaxWidth::Large)
                                ->modalHeading('Buat Proyek Baru')
                        ),
                    Forms\Components\TextInput::make('task_name')
                        ->label('Item')
                        ->required()
                        ->maxLength(100),

                    // Checkbox Long Term Project di bawah Task Name
                    Forms\Components\Checkbox::make('is_long_term')
                        ->label('Long Term Project')
                        ->reactive(),

                    Forms\Components\Select::make('staff_id')
                        ->relationship('staff', 'name')
                        ->label('PIC')
                        ->searchable()
                        ->preload()
                        ->required()
                        ->createOptionForm([
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->maxLength(100),
                            Forms\Components\Select::make('role_id')
                                ->relationship('role', 'name')
                                ->searchable()
                                ->preload()
                                ->required()
                                ->createOptionForm([
                                    Forms\Components\TextInput::make('name')
                                        ->label('Nama Role')
                                        ->required()
                                        ->maxLength(255),
                                    Forms\Components\Textarea::make('description')
                                        ->label('Deskripsi Role')
                                        ->required()
                                        ->maxLength(255),
                                ])
                                ->createOptionAction(fn(\Filament\Forms\Components\Actions\Action $action) => $action->slideOver()->modalWidth(MaxWidth::Medium)),
                            Forms\Components\ColorPicker::make('color'),
                        ])
                        ->createOptionAction(fn(\Filament\Forms\Components\Actions\Action $action) => $action->slideOver()->modalWidth(MaxWidth::Medium)),

                    Forms\Components\Textarea::make('input')
                        ->label('Project')
                        ->maxLength(255),

                    Forms\Components\Textarea::make('output')
                        ->label('Task')
                        ->maxLength(255),

                    // Jika bukan long term → tampilkan tanggal & estimasi jam
                    Forms\Components\DatePicker::make('tanggal')
                        ->label(function (Get $get) {
                            return match ($get('is_long_term')) {
                                true => 'Start Date Project',
                                default => 'Target'
                            };
                        })
                        ->closeOnDateSelection()
                        ->disabledDates(function () {
                            $start = now()->startOfMonth();
                            $end   = now()->addMonths(6)->endOfMonth();
                            $period = CarbonPeriod::create($start, $end);

                            $weekends = [];

                            foreach ($period as $date) {
                                if ($date->isWeekend()) {
                                    $weekends[] = $date->format('Y-m-d');
                                }
                            }

                            return $weekends;
                        })
                        ->native(false)
                        ->reactive()
                        ->required(),

                    Forms\Components\TextInput::make('estimasi_jam')
                        ->numeric()
                        ->maxValue(8)
                        ->minValue(1)
                        ->visible(fn(Get $get) => !$get('is_long_term'))
                        ->required(fn(Get $get) => !$get('is_long_term')),

                    Forms\Components\DatePicker::make('tanggal_akhir')
                        ->label('End Date Project')
                        ->closeOnDateSelection()
                        ->disabledDates(function () {
                            $start = now()->startOfMonth();
                            $end   = now()->addMonths(6)->endOfMonth();
                            $period = CarbonPeriod::create($start, $end);

                            $weekends = [];

                            foreach ($period as $date) {
                                if ($date->isWeekend()) {
                                    $weekends[] = $date->format('Y-m-d');
                                }
                            }

                            return $weekends;
                        })
                        ->native(false)
                        ->visible(fn(Get $get) => $get('is_long_term'))
                        ->required(fn(Get $get) => $get('is_long_term')),

                    // Jika long term → tampilkan alokasi jam + start/end date
                    Forms\Components\TextInput::make('allocation_hours')
                        ->label('Alokasi Jam per Hari')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(8)
                        ->visible(fn(Get $get) => $get('is_long_term'))
                        ->required(fn(Get $get) => $get('is_long_term')),

                    Forms\Components\Select::make('status')
                        ->label('Evaluasi Efektivitas')
                        ->required()
                        ->native(false)
                        ->options([
                            'opened' => 'Opened',
                            'progress' => 'Progress',
                            'closed' => 'Closed',
                            'overdue' => 'Overdue',
                            'postponed' => 'Postponed',
                        ]),


                    Forms\Components\Select::make('priority')
                        ->label('Priority')
                        ->required()
                        ->native(false)
                        ->options([
                            'not_priority' => '
                                <div style="display:flex; justify-content:space-between; align-items:center; width:100%;">
                                    <span>⚪ No Priority</span>
                                    <span style="opacity:0.5; margin-left:160px;">0</span>
                                </div>',
                            'urgent' => '
                                <div style="display:flex; justify-content:space-between; align-items:center; width:100%;">
                                    <span>❗ Urgent</span>
                                    <span style="opacity:0.5; margin-left:190px;">1</span>
                                </div>',
                            'high' => '
                                <div style="display:flex; justify-content:space-between; align-items:center; width:100%;">
                                    <span>🟠 High</span>
                                    <span style="opacity:0.5; margin-left:200px;">2</span>
                                </div>',
                            'medium' => '
                                <div style="display:flex; justify-content:space-between; align-items:center; width:100%;">
                                    <span>🟡 Medium</span>
                                    <span style="opacity:0.5; margin-left:178px;">3</span>
                                </div>',
                            'low' => '
                                <div style="display:flex; justify-content:space-between; align-items:center; width:100%;">
                                    <span>🟢 Low</span>
                                    <span style="opacity:0.5; margin-left:205px;">4</span>
                                </div>',
                        ])

                        ->default('not_priority')
                        ->allowHtml(),

                    Forms\Components\TextInput::make('progress')
                        ->label('%')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(100)
                        ->default(0)
                        ->suffix('%')
                        ->required(),
                ])
                ->columns(1)
        ];
    }
}
