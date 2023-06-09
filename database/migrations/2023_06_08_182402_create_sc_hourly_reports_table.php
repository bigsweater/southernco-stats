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
        Schema::create('sc_hourly_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(ScAccount::class);
            $table->timestamp('hour_at')->index();
            $table->float('cost_usd')->nullable();
            $table->float('usage_kwh')->nullable();
            $table->float('temp_f')->nullable();
            $table->integer('peak_hours_from')->nullable();
            $table->integer('peak_hours_to')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sc_hourly_reports');
    }
};
