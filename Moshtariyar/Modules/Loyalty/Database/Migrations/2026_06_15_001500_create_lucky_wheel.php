<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // بخش‌های گردونهٔ شانس
        Schema::create('wheel_prizes', function (Blueprint $table) {
            $table->id();
            $table->string('title', 80);                 // عنوان جایزه (مثلاً ۵۰ امتیاز)
            $table->enum('prize_type', ['point', 'wallet', 'nothing'])->default('point');
            $table->integer('amount')->default(0);
            $table->unsignedInteger('chance')->default(10); // شانس نسبی (وزن)
            $table->string('color', 20)->default('#38bdf8');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // سابقهٔ چرخش‌ها
        Schema::create('wheel_spins', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained('loyalty_members')->cascadeOnDelete();
            $table->foreignId('prize_id')->nullable()->constrained('wheel_prizes')->nullOnDelete();
            $table->string('prize_title', 80)->nullable();
            $table->timestamps();
            $table->index('member_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wheel_spins');
        Schema::dropIfExists('wheel_prizes');
    }
};
