<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sc_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class);
            $table->bigInteger('account_number')->index();
            $table->integer('account_type');
            $table->integer('company');
            $table->boolean('is_primary');
            $table->text('description');
            $table->timestamps();
        });
    }
};
