<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\MonthlyReportsBackfillWidget;
use App\Filament\Widgets\MonthlyReportsTableWidget;
use Filament\Pages\Page;

class MonthlyReports extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    protected static string $view = 'filament.pages.monthly-reports';

    public function getWidgets(): array
    {
        return [
            MonthlyReportsTableWidget::class,
            MonthlyReportsBackfillWidget::class,
        ];
    }

    public function getColumns(): int
    {
        return 2;
    }
}
