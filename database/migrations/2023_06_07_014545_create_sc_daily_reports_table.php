<?php

use App\Models\ScAccount;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sc_daily_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(ScAccount::class);
            $table->timestamp('day_at')->index();
            $table->float('weekday_cost_usd')->nullable();
            $table->float('weekday_usage_kwh')->nullable();
            $table->float('weekend_cost_usd')->nullable();
            $table->float('weekend_usage_kwh')->nullable();
            $table->float('temp_high_f')->nullable();
            $table->float('temp_low_f')->nullable();
            $table->float('alert_cost')->nullable();
            $table->float('overage_low_kwh')->nullable();
            $table->float('overage_high_kwh')->nullable();
            $table->float('average_daily_cost_usd')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sc_daily_reports');
    }
};
