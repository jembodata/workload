<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReportHistoryResource\Pages;
use App\Models\ReportHistory;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ReportHistoryResource extends Resource
{
    protected static ?string $model = ReportHistory::class;

    protected static ?string $navigationIcon = null;
    protected static ?string $navigationLabel = 'Report Histories';
    protected static ?string $modelLabel = 'Report History';
    protected static ?string $pluralModelLabel = 'Report Histories';

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('printed_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('printed_at')
                    ->label('Printed At')
                    ->dateTime('d M Y H:i', 'Asia/Jakarta')
                    ->sortable(),
                Tables\Columns\TextColumn::make('document_no')
                    ->label('No. Document')
                    ->searchable(),
                Tables\Columns\TextColumn::make('title_id')
                    ->label('Title')
                    ->limit(45)
                    ->searchable(),
                Tables\Columns\TextColumn::make('revision')
                    ->badge()
                    ->sortable(),
                Tables\Columns\TextColumn::make('orientation')
                    ->badge()
                    ->formatStateUsing(fn(string $state): string => ucfirst($state)),
                Tables\Columns\TextColumn::make('page_label')
                    ->label('Page'),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Printed By')
                    ->placeholder('-')
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('orientation')
                    ->options([
                        'portrait' => 'Portrait',
                        'landscape' => 'Landscape',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('viewPdf')
                    ->label('View PDF')
                    ->icon('heroicon-o-document')
                    ->visible(fn(ReportHistory $record): bool => filled($record->pdf_path))
                    ->url(fn(ReportHistory $record): string => route('task-report.history.pdf', ['history' => $record]))
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('viewDocx')
                    ->label('View DOCX')
                    ->icon('heroicon-o-document-text')
                    ->color('gray')
                    ->visible(fn(ReportHistory $record): bool => filled($record->docx_path))
                    ->url(fn(ReportHistory $record): string => route('task-report.history.docx', ['history' => $record]))
                    ->openUrlInNewTab(),
                Tables\Actions\DeleteAction::make()
                    ->label('Delete')
                    ->requiresConfirmation()
                    ->modalHeading('Hapus History Report')
                    ->modalDescription('Data history dan file report di storage akan dihapus permanen.')
                    ->before(function (ReportHistory $record): void {
                        $paths = array_filter([
                            $record->pdf_path,
                            $record->docx_path,
                        ]);

                        foreach ($paths as $path) {
                            if (!Str::startsWith((string) $path, 'reports/history/')) {
                                continue;
                            }

                            if (Storage::disk('local')->exists($path)) {
                                Storage::disk('local')->delete($path);
                            }
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->label('Delete Selected')
                    ->requiresConfirmation()
                    ->modalHeading('Hapus History Report Terpilih')
                    ->modalDescription('Semua data history terpilih dan file report di storage akan dihapus permanen.')
                    ->before(function ($records): void {
                        $paths = $records
                            ->flatMap(fn(ReportHistory $record) => array_filter([$record->pdf_path, $record->docx_path]))
                            ->unique()
                            ->values();

                        foreach ($paths as $path) {
                            if (!Str::startsWith((string) $path, 'reports/history/')) {
                                continue;
                            }

                            if (Storage::disk('local')->exists($path)) {
                                Storage::disk('local')->delete($path);
                            }
                        }
                    }),
            ]);
    }

    public static function getNavigationGroup(): ?string
    {
        return 'Setting';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReportHistories::route('/'),
        ];
    }
}
