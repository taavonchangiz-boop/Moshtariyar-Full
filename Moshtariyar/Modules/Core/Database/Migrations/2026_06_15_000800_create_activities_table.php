<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // فعالیت/یادداشت/یادآوری (قابل اتصال به مشتری یا سرنخ - polymorphic سبک)
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            $table->string('subject_type', 30);          // customer / lead
            $table->unsignedBigInteger('subject_id');
            $table->enum('type', ['note', 'call', 'meeting', 'task', 'email', 'sms'])->default('note');
            $table->text('body')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();  // کارشناس
            $table->timestamp('due_at')->nullable();      // زمان یادآوری/سررسید
            $table->boolean('done')->default(false);
            $table->timestamps();

            $table->index(['subject_type', 'subject_id']);
            $table->index('due_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activities');
    }
};
