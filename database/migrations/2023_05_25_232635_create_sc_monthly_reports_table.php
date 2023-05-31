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
        Schema::create('sc_monthly_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(ScAccount::class);
            $table->timestamp('period_start_at')->index();
            $table->timestamp('period_end_at')->index();
            $table->float('cost_usd')->nullable();
            $table->float('usage_kwh')->nullable();
            $table->float('temp_high_f')->nullable();
            $table->float('temp_low_f')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sc_monthly_reports');
    }
};
