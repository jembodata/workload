<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProjectResource\Pages;
use App\Filament\Resources\ProjectResource\RelationManagers;
use Filament\Support\Enums\MaxWidth;
use Guava\FilamentModalRelationManagers\Actions\Table\RelationManagerAction;
use App\Filament\Resources\ProjectResource\RelationManagers\TasksRelationManager;
use App\Models\Project;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected bool $canCreateAnother = false;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
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
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('project_name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('health')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'on_track' => 'success',
                        'at_risk'  => 'warning',
                        'off_track' => 'danger',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'on_track' => 'On Track',
                        'at_risk'  => 'At Risk',
                        'off_track' => 'Off Track',
                    }),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'planned'   => 'gray',
                        'active'    => 'info',
                        'on_hold'   => 'warning',
                        'completed' => 'success',
                    }),

                Tables\Columns\TextColumn::make('end_date')
                    ->label('Deadline')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                RelationManagerAction::make('tasks-relation-manager')
                    ->label('View Tasks')
                    ->slideOver()
                    ->closeModalByClickingAway(false)
                    ->icon('heroicon-m-document-magnifying-glass')
                    ->relationManager(TasksRelationManager::make()),

                Tables\Actions\EditAction::make()
                    ->slideOver()
                    ->closeModalByClickingAway(false)
                    ->modalWidth(MaxWidth::Large),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            TasksRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProjects::route('/'),
            // 'create' => Pages\CreateProject::route('/create'),
            // 'edit' => Pages\EditProject::route('/{record}/edit'),
        ];
    }
}
