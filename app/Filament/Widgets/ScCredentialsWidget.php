<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class ScCredentialsWidget extends Widget
{
    protected int | array | string $columnSpan = 1;

    protected static string $view = 'filament.widgets.sc-credentials-widget';
}
