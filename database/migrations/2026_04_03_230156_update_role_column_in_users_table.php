<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("DO \$\$ BEGIN
            IF EXISTS (
                SELECT 1 FROM information_schema.columns 
                WHERE table_name='users' AND column_name='role'
            ) THEN
                ALTER TABLE users DROP COLUMN role;
            END IF;
        END \$\$");

        DB::statement("ALTER TABLE users ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT 'researcher'");
    }

    public function down(): void
    {
        DB::statement("DO \$\$ BEGIN
            IF EXISTS (
                SELECT 1 FROM information_schema.columns 
                WHERE table_name='users' AND column_name='role'
            ) THEN
                ALTER TABLE users DROP COLUMN role;
            END IF;
        END \$\$");

        DB::statement("ALTER TABLE users ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT 'user'");
    }
};