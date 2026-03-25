<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class Workcloud extends Page
{
    protected static ?string $title = 'Workload';
    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static string $view = 'filament.pages.workcloud';

    public ?string $activeTab = 'tab_1';
}