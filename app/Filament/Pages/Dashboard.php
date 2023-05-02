<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\ScCredentialsWidget;
use \Filament\Pages\Dashboard as BasePage;

class Dashboard extends BasePage
{
    public function getWidgets(): array
    {
        return [
            ScCredentialsWidget::class,
        ];
    }
}
