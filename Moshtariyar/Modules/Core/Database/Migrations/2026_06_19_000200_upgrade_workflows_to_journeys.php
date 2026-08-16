<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // افزودن ستون‌های جدید به workflows
        Schema::table('workflows', function (Blueprint $table) {
            if (!Schema::hasColumn('workflows', 'type')) {
                $table->string('type', 30)->default('simple')->after('description');
                // simple = تک‌مرحله‌ای (قدیمی)
                // journey = سفر مشتری (چندمرحله‌ای)
            }
            if (!Schema::hasColumn('workflows', 'trigger_segment_id')) {
                $table->foreignId('trigger_segment_id')->nullable()->after('type')->constrained('segments')->nullOnDelete();
            }
            if (!Schema::hasColumn('workflows', 'trigger_event')) {
                $table->string('trigger_event', 50)->nullable()->after('trigger_segment_id');
            }
            // تغییر نام ستون event به trigger_event در صورت نیاز
            if (Schema::hasColumn('workflows', 'event') && !Schema::hasColumn('workflows', 'trigger_event')) {
                // ستون event نگه داشته می‌شود برای سازگاری با کد قدیمی
            }
        });

        // جدول مراحل سفر مشتری
        if (!Schema::hasTable('journey_steps')) {
            Schema::create('journey_steps', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workflow_id')->constrained('workflows')->cascadeOnDelete();
                $table->integer('step_order')->default(0);
                $table->string('type', 30); // trigger, wait, condition, action, branch
                $table->string('label')->nullable(); // برچسب فارسی مرحله
                
                // برای wait
                $table->integer('wait_hours')->nullable();
                
                // برای condition
                $table->string('condition_field')->nullable(); // bought_after_trigger, in_segment, rfm_group
                $table->string('condition_value')->nullable();
                
                // برای action
                $table->string('action_type', 30)->nullable(); // sms, email, messenger, coupon, add_segment
                $table->string('action_channel', 30)->nullable();
                $table->text('action_template')->nullable();
                
                // برای branch
                $table->unsignedBigInteger('branch_yes_step_order')->nullable();
                $table->unsignedBigInteger('branch_no_step_order')->nullable();
                
                $table->timestamps();
            });
        }

        // جدول اجرای سفر برای هر مشتری
        if (!Schema::hasTable('journey_executions')) {
            Schema::create('journey_executions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('workflow_id')->constrained('workflows')->cascadeOnDelete();
                $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
                $table->integer('current_step_order')->default(0);
                $table->string('status', 30)->default('active'); // active, completed, cancelled
                $table->json('context')->nullable(); // داده‌های مرتبط (مثلاً order_id)
                $table->timestamp('next_run_at')->nullable();
                $table->timestamps();
                
                $table->index(['workflow_id', 'customer_id']);
                $table->index('next_run_at');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('journey_executions');
        Schema::dropIfExists('journey_steps');
        Schema::table('workflows', function (Blueprint $table) {
            $table->dropColumn(['type', 'trigger_segment_id', 'trigger_event']);
        });
    }
};
