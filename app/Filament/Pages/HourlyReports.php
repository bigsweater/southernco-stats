<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\HourlyReportsBackfillWidget;
use App\Filament\Widgets\HourlyReportsListWidget;
use Filament\Pages\Page;

class HourlyReports extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.hourly-reports';

    public function getWidgets(): array
    {
        return [
            HourlyReportsListWidget::class,
            HourlyReportsBackfillWidget::class,
        ];
    }

    public function getColumns(): int
    {
        return 2;
    }
}
