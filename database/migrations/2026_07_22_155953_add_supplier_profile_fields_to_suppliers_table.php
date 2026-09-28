<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $columns = [
            'contact_person' => "varchar(255) null",
            'phone_dial_code' => "varchar(10) null default '+880'",
            'alt_phone' => "varchar(255) null",
            'alt_phone_dial_code' => "varchar(10) null",
            'website' => "varchar(255) null",
            'tax_number' => "varchar(255) null",
            'business_type' => "varchar(255) null",
            'address_line_1' => "varchar(255) null",
            'address_line_2' => "varchar(255) null",
            'city' => "varchar(255) null",
            'state' => "varchar(255) null",
            'postal_code' => "varchar(255) null",
            'country' => "varchar(255) null",
            'opening_balance' => "numeric(15, 2) not null default 0",
            'credit_limit' => "numeric(15, 2) null",
            'payment_terms' => "varchar(255) null",
            'currency' => "varchar(10) null default 'BDT'",
            'notes' => "text null",
        ];

        foreach ($columns as $name => $definition) {
            if (! Schema::hasColumn('suppliers', $name)) {
                DB::statement("alter table suppliers add column {$name} {$definition}");
            }
        }
    }

    public function down(): void
    {
        $cols = [
            'contact_person', 'phone_dial_code', 'alt_phone', 'alt_phone_dial_code',
            'website', 'tax_number', 'business_type', 'address_line_1', 'address_line_2',
            'city', 'state', 'postal_code', 'country', 'opening_balance', 'credit_limit',
            'payment_terms', 'currency', 'notes',
        ];

        foreach ($cols as $col) {
            if (Schema::hasColumn('suppliers', $col)) {
                DB::statement("alter table suppliers drop column {$col}");
            }
        }
    }
};
