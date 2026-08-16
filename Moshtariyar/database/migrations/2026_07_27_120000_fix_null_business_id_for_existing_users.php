<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('businesses')) {
            return;
        }

        // اولین کسب‌وکار فعال را پیدا کن
        $اولین_کسب = DB::table('businesses')->where('is_active', true)->orderBy('id')->first();
        if (! $اولین_کسب) {
            $اولین_کسب = DB::table('businesses')->orderBy('id')->first();
        }

        if (! $اولین_کسب) {
            return;
        }

        $شناسه_کسب = $اولین_کسب->id;

        // تمام کاربرانی که مدیر کل نیستند و کسب‌وکار ندارند را به اولین کسب‌وکار متصل کن
        DB::table('users')
            ->whereNull('business_id')
            ->where('is_superadmin', false)
            ->update(['business_id' => $شناسه_کسب]);

        // اگر کاربری هنوز بدون کسب‌وکار ماند (به خاطر is_superadmin null)، درست کن
        DB::table('users')
            ->whereNull('business_id')
            ->where(function($q){
                $q->where('is_superadmin', false)->orWhereNull('is_superadmin');
            })
            ->where('role', '!=', 'superadmin')
            ->update(['business_id' => $شناسه_کسب]);
    }

    public function down(): void
    {
        // برگشت ندارد چون اطلاعات را درست کردیم
    }
};