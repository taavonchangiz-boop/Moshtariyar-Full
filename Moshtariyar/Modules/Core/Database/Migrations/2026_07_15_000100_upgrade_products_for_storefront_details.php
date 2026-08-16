<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (! Schema::hasColumn('products', 'product_type')) {
                $table->string('product_type', 40)->default('simple')->after('category');
            }
            if (! Schema::hasColumn('products', 'status')) {
                $table->string('status', 30)->default('publish')->after('product_type');
            }
            if (! Schema::hasColumn('products', 'slug')) {
                $table->string('slug')->nullable()->after('name')->index();
            }
            if (! Schema::hasColumn('products', 'short_description')) {
                $table->text('short_description')->nullable()->after('status');
            }
            if (! Schema::hasColumn('products', 'description')) {
                $table->longText('description')->nullable()->after('short_description');
            }
            if (! Schema::hasColumn('products', 'image')) {
                $table->string('image')->nullable()->after('description');
            }
            if (! Schema::hasColumn('products', 'gallery')) {
                $table->json('gallery')->nullable()->after('image');
            }
            if (! Schema::hasColumn('products', 'attributes')) {
                $table->json('attributes')->nullable()->after('gallery');
            }
            if (! Schema::hasColumn('products', 'regular_price')) {
                $table->decimal('regular_price', 15, 2)->default(0)->after('price');
            }
            if (! Schema::hasColumn('products', 'sale_price')) {
                $table->decimal('sale_price', 15, 2)->nullable()->after('regular_price');
            }
            if (! Schema::hasColumn('products', 'manage_stock')) {
                $table->boolean('manage_stock')->default(true)->after('min_stock');
            }
            if (! Schema::hasColumn('products', 'stock_status')) {
                $table->string('stock_status', 30)->default('instock')->after('manage_stock');
            }
            if (! Schema::hasColumn('products', 'source')) {
                $table->string('source', 50)->default('manual')->after('stock_status')->index();
            }
            if (! Schema::hasColumn('products', 'external_url')) {
                $table->string('external_url')->nullable()->after('source');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            foreach ([
                'external_url',
                'source',
                'stock_status',
                'manage_stock',
                'sale_price',
                'regular_price',
                'attributes',
                'gallery',
                'image',
                'description',
                'short_description',
                'slug',
                'status',
                'product_type',
            ] as $column) {
                if (Schema::hasColumn('products', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};