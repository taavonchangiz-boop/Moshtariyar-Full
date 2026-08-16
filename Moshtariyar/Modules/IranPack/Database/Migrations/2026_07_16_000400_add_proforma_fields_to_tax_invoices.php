<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tax_invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('tax_invoices', 'invoice_kind')) {
                $table->string('invoice_kind', 30)->default('official')->after('reference_number');
            }
            if (! Schema::hasColumn('tax_invoices', 'due_at')) {
                $table->timestamp('due_at')->nullable()->after('issued_at');
            }
            if (! Schema::hasColumn('tax_invoices', 'notes')) {
                $table->text('notes')->nullable()->after('error');
            }
        });
    }

    public function down(): void
    {
        Schema::table('tax_invoices', function (Blueprint $table) {
            if (Schema::hasColumn('tax_invoices', 'notes')) {
                $table->dropColumn('notes');
            }
            if (Schema::hasColumn('tax_invoices', 'due_at')) {
                $table->dropColumn('due_at');
            }
            if (Schema::hasColumn('tax_invoices', 'invoice_kind')) {
                $table->dropColumn('invoice_kind');
            }
        });
    }
};