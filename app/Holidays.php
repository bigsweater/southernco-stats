<?php

namespace App;

use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;

class Holidays
{
    public static function independenceDay(int $year): CarbonImmutable
    {
        return Carbon::create($year, 7, 4, 0, 0, 0)->toImmutable();
    }

    public static function laborDay(int $year): CarbonImmutable
    {
        $date = Carbon::create($year, 9, 1, 0, 0, 0);

        if ($date->dayOfWeek !== Carbon::MONDAY) {
            $date->next(Carbon::MONDAY);
        }

        return $date->toImmutable();
    }
}
