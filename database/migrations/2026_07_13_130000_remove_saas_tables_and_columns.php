<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('subscription_requests');

        Schema::table('shops', function (Blueprint $table) {
            if (Schema::hasColumn('shops', 'upcoming_package_id')) {
                $table->dropConstrainedForeignId('upcoming_package_id');
            }
            $columns = ['subscription_plan', 'subscription_start_date', 'subscription_end_date'];
            foreach ($columns as $column) {
                if (Schema::hasColumn('shops', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        Schema::dropIfExists('packages');
    }

    public function down(): void
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('price', 10, 2);
            $table->integer('duration_days');
            $table->integer('max_staff')->default(5);
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('subscription_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->foreignId('package_id')->constrained()->cascadeOnDelete();
            $table->string('payment_method')->nullable();
            $table->string('transaction_id')->nullable();
            $table->string('document_path')->nullable();
            $table->string('status')->default('pending');
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });

        Schema::table('shops', function (Blueprint $table) {
            $table->string('subscription_plan')->nullable();
            $table->timestamp('subscription_start_date')->nullable();
            $table->timestamp('subscription_end_date')->nullable();
            $table->foreignId('upcoming_package_id')->nullable()->constrained('packages')->nullOnDelete();
        });
    }
};
