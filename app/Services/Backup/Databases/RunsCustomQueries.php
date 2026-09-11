<?php

namespace App\Services\Backup\Databases;

use App\Contracts\BackupLogger;

/**
 * Implemented by database handlers that can run arbitrary SQL against the
 * database a restore just wrote to. Powers the optional custom queries a
 * restore runs, against that same database, once it completes successfully.
 *
 * Not part of {@see DatabaseInterface} itself: SQLite, Firebird, MongoDB and
 * Redis/Valkey have no equivalent (no SQL dialect, or no PDO driver wired up
 * in this codebase), so {@see \App\Services\Backup\RestoreTask} checks for
 * this interface rather than calling an unconditional method every handler
 * would otherwise have to implement.
 */
interface RunsCustomQueries
{
    /**
     * Run each query against $schemaName, in order, on a single connection.
     * Not wrapped in a transaction: some engines implicitly commit on DDL
     * anyway, so a later statement can run even after an earlier one already
     * changed the database.
     *
     * @param  array<int, string>  $queries
     */
    public function executeQueries(string $schemaName, array $queries, BackupLogger $logger): void;
}
