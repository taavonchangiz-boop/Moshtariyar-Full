<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_roles', function (Blueprint $table) {
            $table->id();
            $table->string('key', 30)->unique();
            $table->string('label', 120);
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('crm_role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('crm_roles')->cascadeOnDelete();
            $table->string('permission', 80);
            $table->timestamps();
            $table->unique(['role_id', 'permission'], 'uniq_crm_role_permission');
        });

        $now = now();
        $roles = [
            ['key'=>'admin','label'=>'مدیر کل','description'=>'دسترسی کامل به همه بخش‌ها','is_system'=>1,'is_active'=>1,'created_at'=>$now,'updated_at'=>$now],
            ['key'=>'sales','label'=>'کارشناس فروش','description'=>'مشتریان، سفارش‌ها، سرنخ‌ها و فروشگاه','is_system'=>1,'is_active'=>1,'created_at'=>$now,'updated_at'=>$now],
            ['key'=>'support','label'=>'کارشناس پشتیبانی','description'=>'مشتریان، سفارش‌ها و تیکت‌ها','is_system'=>1,'is_active'=>1,'created_at'=>$now,'updated_at'=>$now],
        ];
        foreach ($roles as $r) DB::table('crm_roles')->updateOrInsert(['key'=>$r['key']], $r);

        $permissionMap = [
            'admin' => ['*'],
            'sales' => ['dashboard.view','customers.view','customers.export','orders.view','orders.manage','orders.invoice','leads.view','leads.manage','woocommerce.view','tax.view'],
            'support' => ['dashboard.view','customers.view','orders.view','tickets.view','tickets.manage'],
        ];
        foreach ($permissionMap as $roleKey => $perms) {
            $roleId = DB::table('crm_roles')->where('key', $roleKey)->value('id');
            foreach ($perms as $perm) {
                DB::table('crm_role_permissions')->updateOrInsert(['role_id'=>$roleId,'permission'=>$perm], ['created_at'=>$now,'updated_at'=>$now]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_role_permissions');
        Schema::dropIfExists('crm_roles');
    }
};
