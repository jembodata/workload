<?php

namespace App\Filament\Resources\TaskResource\RelationManagers;

use App\Models\Task;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Filament\Support\Enums\MaxWidth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class IssuesRelationManager extends RelationManager
{
    protected static string $relationship = 'issues';

    public function form(Form $form): Form
    {
        return $form
            ->schema([

                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('issue_name')
                            ->label('Issue Title')
                            ->placeholder('Apa kendala yang dihadapi?')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Forms\Components\RichEditor::make('description')
                            ->label('Description')
                            ->placeholder('Jelaskan detail kendala di sini...')
                            ->toolbarButtons([
                                'blockquote',
                                'bold',
                                'bulletList',
                                'codeBlock',
                                'h2',
                                'h3',
                                'italic',
                                'link',
                                'orderedList',
                                'strike',
                            ])
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
                            ->closeOnDateSelection()
                            ->maxDate(fn($livewire) => $livewire->ownerRecord?->tanggal),

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
                            ->allowHtml()
                            ->default('Open')
                            ->selectablePlaceholder(false)
                            ->options(function () {
                                $statuses = [
                                    'Backlog' => [
                                        'label' => 'Backlog',
                                        'icon' => 'circle-dashed',
                                        'color' => '#9ca3af', // Gray
                                        'rating' => '1'
                                    ],
                                    'Open' => [
                                        'label' => 'Open',
                                        'icon' => 'circle',
                                        'color' => '#ffffff', // White/Border
                                        'rating' => '2'
                                    ],
                                    'In Progress' => [
                                        'label' => 'In Progress',
                                        'icon' => 'circle-dot-dashed',
                                        'color' => '#facc15', // Yellow
                                        'rating' => '3'
                                    ],
                                    'Done' => [
                                        'label' => 'Done',
                                        'icon' => 'check-circle-2',
                                        'color' => '#5e6ad2', // Linear Purple-Blue
                                        'rating' => '4'
                                    ],
                                    'Canceled' => [
                                        'label' => 'Canceled',
                                        'icon' => 'x-circle',
                                        'color' => '#9ca3af', // Gray
                                        'rating' => '5'
                                    ],
                                ];

                                $options = [];
                                foreach ($statuses as $key => $data) {

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
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('issue_name')
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
                    ->description(fn($record) => new HtmlString(Str::limit($record->description, 60)))
                    ->tooltip(fn(Model $record): string => strip_tags($record->description))
                    ->formatStateUsing(fn(string $state): string => strip_tags($state)),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->formatStateUsing(function (string $state): \Illuminate\Support\HtmlString {
                        // Mapping konfigurasi Lucide, Warna, dan Rating (Sesuai Form kamu)
                        $statuses = [
                            'backlog'     => ['icon' => 'circle-dashed',     'color' => '#9ca3af', 'label' => 'Backlog',     'rating' => '1'],
                            'open'        => ['icon' => 'circle',            'color' => '#0ea5e9', 'label' => 'Open',       'rating' => '2'],
                            'in_progress' => ['icon' => 'circle-dot-dashed', 'color' => '#eab308', 'label' => 'In Progress', 'rating' => '3'],
                            'done'        => ['icon' => 'check-circle-2',    'color' => '#5e6ad2', 'label' => 'Done',        'rating' => '4'],
                            'canceled'    => ['icon' => 'x-circle',          'color' => '#ef4444', 'label' => 'Canceled',    'rating' => '5'],
                            'duplicate'   => ['icon' => 'copy',              'color' => '#ef4444', 'label' => 'Duplicate',   'rating' => '6'],
                        ];

                        $config = $statuses[$state] ?? ['icon' => 'help-circle', 'color' => '#9ca3af', 'label' => $state, 'rating' => '0'];

                        return new \Illuminate\Support\HtmlString("
                            <div style='display:flex; align-items:center; width: 100%; gap:8px;'>
                                <img src='https://unpkg.com/lucide-static@latest/icons/{$config['icon']}.svg' 
                                                style='width:1.1rem; height:1.1rem; filter: invert(30%) sepia(100%) saturate(500%) hue-rotate(0deg);' 
                                                alt='icon' />
                                
                                <span style='font-size:0.85rem;'>{$config['label']}</span>
                                
                                <div style='flex-grow: 1;'></div>
                                
                                <span style='opacity:0.3; font-family:monospace; font-size:0.75rem;'>{$config['rating']}</span>
                            </div>
                        ");
                    })
                    ->sortable(),

                // Assignee (Avatar User)
                Tables\Columns\ImageColumn::make('user.avatar_url') // Pastikan ada kolom avatar di tabel users
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
                    ->modalWidth(MaxWidth::Medium),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->slideOver()
                    ->closeModalByClickingAway(false)
                    ->modalWidth(MaxWidth::Medium),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
