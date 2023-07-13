<?php

namespace App\Filament\Widgets;

use App\CurrentUsageCacheKey;
use App\Models\ScAccount;
use App\Models\ScCredentials;
use App\Models\ScMonthlyReport;
use App\ScClient;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Card;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\HtmlString;

class CurrentStatsWidget extends BaseWidget
{
    public ?int $scAccountId = null;
    public ?array $stats = null;

    protected static ?string $pollingInterval = '900s';

    public function getScCredentialsProperty(): ?ScCredentials
    {
        return auth()->user()->scCredentials;
    }

    public function getScClientProperty(): ?ScClient
    {
        return $this->scCredentials
            ? app(ScClient::class, ['credentials' => $this->scCredentials])
            : null;
    }

    public function getScAccountProperty(): ?ScAccount
    {
        return ScAccount::find($this->scAccountId);
    }

    public function getReportProperty(): ?ScMonthlyReport
    {
        return ScMonthlyReport::whereBelongsTo($this->scAccount)
            ->latest('period_start_at')
            ->firstOrNew();
    }

    public function fetchStats()
    {
        $this->stats = Cache::remember(
            key: (string) new CurrentUsageCacheKey($this->report, $this->scAccountId),
            ttl: 900, // 15 minutes
            callback: fn () => $this->scClient->getCurrentUsageForMonthlyReport(
                $this->scAccount,
                $this->report
            )
        );
    }

    protected function getCards(): array
    {
        if (!$this->scCredentials) {
            return [
                Card::make('Current usage statistics', 'Missing credentials')
                    ->color('warning')
                    ->description('Please sign into your Georgia Power account below in order to retreive your usage statistics.')
                    ->descriptionIcon('heroicon-o-exclamation-circle', 'before')
            ];
        }

        if (!$this->scAccount) {
            return [
                Card::make('Current usage statistics', 'Missing account')
                    ->color('warning')
                    ->description('No Georgia Power account found. Please check your credentials and try again.')
                    ->descriptionIcon('heroicon-o-exclamation-circle', 'before')
            ];
        }

        if (
            !$this->report->period_end_at
            || $this->report->period_end_at->isBefore(now())
        ) {
            return [
                Card::make('Current usage statistics', 'Missing or outdated billing period')
                    ->color('warning')
                    ->description(new HtmlString('Your monthly reports are missing or outdated for this account. Visit the <a href="/monthly-reports">monthly reports page</a> to refresh them.'))
                    ->descriptionIcon('heroicon-o-exclamation-circle', 'before')
            ];
        }

        return [
            Card::make(
                'Current usage',
                $this->getCurrentUsageString()
            )->extraAttributes([
                'wire:init' => 'fetchStats',
                'wire:loading.class' => 'opacity-50',
            ])->description("Since {$this->report->period_start_at->diffForHumans()}"),

            Card::make(
                'Current cost',
                $this->getDollarsToDateString()
            )->extraAttributes([
                'wire:loading.class' => 'opacity-50',
            ])->description("Since {$this->report->period_start_at->diffForHumans()}"),

            Card::make(
                'Projected usage',
                $this->getProjectedUsageString()
            )->extraAttributes([
                'wire:loading.class' => 'opacity-50',
            ])->description("Between {$this->report->period_start_at->toFormattedDateString()} and {$this->report->period_end_at->toFormattedDateString()}"),

            Card::make(
                'Projected cost',
                $this->getProjectedCostString()
            )->extraAttributes([
                'wire:loading.class' => 'opacity-50',
            ])->description("Between {$this->report->period_start_at->toFormattedDateString()} and {$this->report->period_end_at->toFormattedDateString()}"),

            Card::make(
                'Average daily cost',
                $this->getAverageDailyCostString()
            )->extraAttributes([
                'wire:loading.class' => 'opacity-50',
            ]),

            Card::make(
                'Average daily usage',
                $this->getAverageDailyUsageString()
            )->extraAttributes([
                'wire:loading.class' => 'opacity-50',
            ]),
        ];
    }

    private function getCurrentUsageString(): string
    {
        $usage = Arr::get($this->stats, 'TotalkWhUsed');

        if (!$usage) {
            return 'Loading...';
        }

        return "{$usage} kWh";
    }

    private function getDollarsToDateString(): string
    {
        if (!$this->stats) {
            return 'Loading...';
        }

        $cost = Arr::get($this->stats, 'DollarsToDate', 0);

        if (intval($cost) === 0) {
            return 'Not yet available';
        }

        $rounded = number_format($cost, 2);
        return "\${$rounded}";
    }

    private function getProjectedUsageString(): string
    {
        if (!$this->stats) {
            return 'Loading...';
        }

        $low = Arr::get($this->stats, 'ProjectedUsageLow', 0);
        $high = Arr::get($this->stats, 'ProjectedUsageHigh', 0);

        if (intval($low) === 0 && intval($high) === 0) {
            return 'Not yet available';
        }

        return "{$low} — {$high} kWh";
    }

    private function getProjectedCostString(): string
    {
        if (!$this->stats) {
            return 'Loading...';
        }

        $low = Arr::get($this->stats, 'ProjectedBillAmountLow', 0);
        $high = Arr::get($this->stats, 'ProjectedBillAmountHigh', 0);

        if (intval($low) === 0 && intval($high) === 0) {
            return 'Not yet available';
        }

        $low = number_format($low, 2);
        $high = number_format($high, 2);

        return "\${$low} — \${$high}";
    }

    private function getAverageDailyCostString(): string
    {
        if (!$this->stats) {
            return 'Loading...';
        }

        $cost = Arr::get($this->stats, 'AverageDailyCost', 0);

        if (intval($cost) === 0) {
            return 'Not yet available';
        }

        $cost = number_format($cost, 2);

        return "\${$cost}";
    }

    private function getAverageDailyUsageString(): string
    {
        if (!$this->stats) {
            return 'Loading...';
        }

        $usage = Arr::get($this->stats, 'AverageDailyUsage', 0);

        if (intval($usage) === 0) {
            return 'Not yet available';
        }

        return "{$usage} kWh";
    }
}
