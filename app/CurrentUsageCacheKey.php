<?php

namespace App;

use App\Models\ScMonthlyReport;
use Stringable;

class CurrentUsageCacheKey implements Stringable
{
    public function __construct(
        public ScMonthlyReport $report,
        public int $accountId,
    ) {}

    public static function make(ScMonthlyReport $report, int $accountId): static
    {
        return new static($report, $accountId);
    }

    public function __toString()
    {
        return "{$this->report->period_start_at->getTimestamp()}_{$this->accountId}_current_usage";
    }
}
