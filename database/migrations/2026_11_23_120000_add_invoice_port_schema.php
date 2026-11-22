<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Additive schema for upcoming invoice ports. Safe to run more than once.
     * Companion SQL: docs/sql/2026-11-23-invoice-port-schema.sql
     */
    public function up(): void
    {
        if (Schema::hasTable('staff_users') && ! Schema::hasColumn('staff_users', 'locale')) {
            Schema::table('staff_users', function (Blueprint $table): void {
                $table->string('locale', 8)->default('bn')->after('is_active');
            });
        }

        if (Schema::hasTable('invoices') && ! Schema::hasColumn('invoices', 'due_date')) {
            Schema::table('invoices', function (Blueprint $table): void {
                $table->date('due_date')->nullable()->after('issued_at');
            });
        }

        if (! Schema::hasTable('product_units')) {
            Schema::create('product_units', function (Blueprint $table): void {
                $table->id();
                $table->string('name_en');
                $table->string('name_ar');
                $table->string('name_bn', 160);
                $table->string('code', 16)->unique();
                $table->string('ubl_unit_code', 16);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('products') && ! Schema::hasColumn('products', 'product_unit_id')) {
            Schema::table('products', function (Blueprint $table): void {
                $table->foreignId('product_unit_id')
                    ->nullable()
                    ->after('product_category_id')
                    ->constrained('product_units')
                    ->nullOnDelete();
            });
        }

        $now = now();
        foreach ($this->defaultUnits() as $row) {
            $exists = DB::table('product_units')->where('code', $row['code'])->exists();
            if ($exists) {
                continue;
            }

            DB::table('product_units')->insert([
                'name_en' => $row['name_en'],
                'name_ar' => $row['name_ar'],
                'name_bn' => $row['name_bn'],
                'code' => $row['code'],
                'ubl_unit_code' => $row['ubl_unit_code'],
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('products') && Schema::hasColumn('products', 'product_unit_id')) {
            Schema::table('products', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('product_unit_id');
            });
        }

        Schema::dropIfExists('product_units');

        if (Schema::hasTable('invoices') && Schema::hasColumn('invoices', 'due_date')) {
            Schema::table('invoices', function (Blueprint $table): void {
                $table->dropColumn('due_date');
            });
        }

        if (Schema::hasTable('staff_users') && Schema::hasColumn('staff_users', 'locale')) {
            Schema::table('staff_users', function (Blueprint $table): void {
                $table->dropColumn('locale');
            });
        }
    }

    /**
     * @return list<array{code: string, name_en: string, name_ar: string, name_bn: string, ubl_unit_code: string}>
     */
    private function defaultUnits(): array
    {
        return [
            ['code' => 'PCS', 'name_en' => 'Piece', 'name_ar' => 'قطعة', 'name_bn' => 'পিস', 'ubl_unit_code' => 'PCE'],
            ['code' => 'KG', 'name_en' => 'Kilogram', 'name_ar' => 'كيلوغرام', 'name_bn' => 'কিলোগ্রাম', 'ubl_unit_code' => 'KGM'],
            ['code' => 'L', 'name_en' => 'Liter', 'name_ar' => 'لتر', 'name_bn' => 'লিটার', 'ubl_unit_code' => 'LTR'],
        ];
    }
};
