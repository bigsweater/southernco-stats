<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class MonthlyReportsTableWidget extends Widget
{
    protected int | array | string $columnSpan = 2;

    protected static string $view = 'filament.widgets.monthly-reports-table-widget';
}
