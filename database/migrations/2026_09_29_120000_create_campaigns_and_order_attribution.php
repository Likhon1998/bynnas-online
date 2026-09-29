<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shop_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('code', 16)->unique();
            $table->string('source', 30);
            $table->string('medium', 30);
            $table->string('utm_campaign', 120);
            $table->string('landing_type', 20)->default('home');
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();
            $table->string('landing_url', 500)->nullable();
            $table->string('status', 20)->default('active');
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->decimal('spend', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['shop_id', 'utm_campaign']);
            $table->index(['shop_id', 'status']);
        });

        Schema::create('campaign_visits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->string('visitor_hash', 64);
            $table->string('utm_content', 120)->nullable();
            $table->string('landing_path', 500)->nullable();
            $table->string('referrer_host', 190)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['campaign_id', 'created_at']);
            $table->index(['campaign_id', 'visitor_hash']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('campaign_id')->nullable()->after('customer_id')->constrained()->nullOnDelete();
            $table->string('utm_source', 60)->nullable()->after('campaign_id');
            $table->string('utm_medium', 60)->nullable()->after('utm_source');
            $table->string('utm_campaign', 120)->nullable()->after('utm_medium');
            $table->string('utm_content', 120)->nullable()->after('utm_campaign');
            $table->string('utm_term', 120)->nullable()->after('utm_content');
            $table->string('landing_page', 500)->nullable()->after('utm_term');
            $table->string('referrer_host', 190)->nullable()->after('landing_page');
            $table->index(['shop_id', 'utm_source']);
        });

        if (Schema::hasTable('permissions') && Schema::hasTable('roles')) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
            $permission = Permission::firstOrCreate(['name' => 'manage campaigns', 'guard_name' => 'web']);
            foreach (['Admin', 'Shop Owner', 'Manager'] as $roleName) {
                Role::where('name', $roleName)->where('guard_name', 'web')->first()?->givePermissionTo($permission);
            }
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['shop_id', 'utm_source']);
            $table->dropConstrainedForeignId('campaign_id');
            $table->dropColumn(['utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'landing_page', 'referrer_host']);
        });
        Schema::dropIfExists('campaign_visits');
        Schema::dropIfExists('campaigns');

        if (Schema::hasTable('permissions')) {
            Permission::where('name', 'manage campaigns')->where('guard_name', 'web')->delete();
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }
};
