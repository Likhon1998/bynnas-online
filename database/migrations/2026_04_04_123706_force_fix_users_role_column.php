<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role VARCHAR(255) DEFAULT 'Shop Owner'");
            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_role_check');
            DB::statement("ALTER TABLE users ALTER COLUMN role TYPE VARCHAR(255) USING role::text");
            DB::statement("ALTER TABLE users ALTER COLUMN role SET DEFAULT 'Shop Owner'");
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role VARCHAR(255) DEFAULT 'admin'");
            return;
        }

        if ($driver === 'pgsql') {
            DB::statement("ALTER TABLE users ALTER COLUMN role SET DEFAULT 'admin'");
        }
    }
};