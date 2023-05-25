<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ScCredentialsWidget;
use App\Filament\Widgets\ScAccountsWidget;
use \Filament\Pages\Dashboard as BasePage;

class Dashboard extends BasePage
{
    public function getWidgets(): array
    {
        return [
            ScCredentialsWidget::class,
            ScAccountsWidget::class,
        ];
    }
}
