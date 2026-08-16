<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kb_chat_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('visitor_key', 80)->nullable()->index();
            $table->string('channel', 40)->default('club');
            $table->string('ip', 64)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
        });

        Schema::create('kb_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('kb_chat_sessions')->cascadeOnDelete();
            $table->enum('sender', ['user', 'bot', 'staff'])->default('user');
            $table->text('message');
            $table->json('matched_articles')->nullable();
            $table->boolean('needs_ticket')->default(false);
            $table->timestamps();
            $table->index(['session_id', 'sender']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kb_chat_messages');
        Schema::dropIfExists('kb_chat_sessions');
    }
};
