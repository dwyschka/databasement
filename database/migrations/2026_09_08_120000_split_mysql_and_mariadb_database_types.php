<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * MySQL and MariaDB are now separate database types, each backed by its
     * own client (see App\Services\Backup\Databases\MysqlDatabase and
     * MariadbDatabase). Every existing `mysql`-typed row was, until now,
     * always dumped/restored with the MariaDB client, so it is migrated to
     * the new `mariadb` type to preserve its current behaviour. `mysql`
     * becomes the new genuine-MySQL-client type; nothing is auto-assigned to
     * it, users switch a server to it explicitly.
     */
    public function up(): void
    {
        DB::table('database_servers')
            ->where('database_type', 'mysql')
            ->update(['database_type' => 'mariadb']);

        DB::table('snapshots')
            ->where('database_type', 'mysql')
            ->update(['database_type' => 'mariadb']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration reassigns existing rows to a new type - no reliable
        // way to reverse, since every migrated row now looks identical to one
        // that was genuinely created as `mariadb`.
    }
};
