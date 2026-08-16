<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('strategic_briefings', function (Blueprint $table) {
            $table->id();
            $table->date('report_date');
            $table->text('content');
            $table->integer('churn_count')->default(0);
            $table->integer('growth_count')->default(0);
            $table->integer('health_score')->default(0);
            $table->boolean('is_read')->default(false);
            $table->timestamps();
            $table->index('report_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('strategic_briefings');
    }
};