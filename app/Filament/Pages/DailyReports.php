<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\DailyReportsBackfillWidget;
use App\Filament\Widgets\DailyReportsTableWidget;
use Filament\Pages\Page;

class DailyReports extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.daily-reports';

    public function getWidgets(): array
    {
        return [
            DailyReportsTableWidget::class,
            DailyReportsBackfillWidget::class,
        ];
    }

    public function getColumns(): int
    {
        return 2;
    }
}
