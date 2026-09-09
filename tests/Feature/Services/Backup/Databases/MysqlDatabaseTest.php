<?php

use App\Services\Backup\Databases\MysqlDatabase;
use App\Services\Backup\DTO\DatabaseOperationResult;
use Illuminate\Support\Facades\Process;

beforeEach(function () {
    $this->db = new MysqlDatabase;
    $this->db->setConfig([
        'host' => 'db.local',
        'port' => 3306,
        'user' => 'root',
        'pass' => 'secret',
        'database' => 'myapp',
    ]);
});

test('dump builds correct command with ssl-mode=DISABLED by default', function () {
    $result = $this->db->dump('/tmp/dump.sql');

    expect($result)->toBeInstanceOf(DatabaseOperationResult::class)
        ->and($result->command)->toBe("mysqldump --single-transaction --routines --add-drop-table --hex-blob --quote-names --ssl-mode=DISABLED --host='db.local' --port='3306' --user='root' --password='secret' 'myapp' > '/tmp/dump.sql'");
});

test('dump uses ssl-mode=REQUIRED when ssl_enabled is true', function () {
    $db = new MysqlDatabase;
    $db->setConfig([
        'host' => 'db.local',
        'port' => 3306,
        'user' => 'root',
        'pass' => 'secret',
        'database' => 'myapp',
        'ssl_enabled' => true,
    ]);

    $result = $db->dump('/tmp/dump.sql');

    expect($result->command)
        ->toContain('--ssl-mode=REQUIRED')
        ->not->toContain('--ssl-mode=DISABLED');
});

test('dump always keeps --routines, unlike the MariaDB client', function () {
    // The MariaDB client's routines quirk (App\Services\Backup\Databases\MariadbDatabase)
    // does not apply here: mysqldump talking to a real MySQL server has no
    // version-gated SHOW PACKAGE STATUS problem.
    $result = $this->db->dump('/tmp/dump.sql');

    expect($result->command)->toContain('--routines')
        ->and($result->log)->toBeNull();
});

test('dump includes extra dump flags', function () {
    $db = new MysqlDatabase;
    $db->setConfig([
        'host' => 'db.local',
        'port' => 3306,
        'user' => 'root',
        'pass' => 'secret',
        'database' => 'myapp',
        'dump_flags' => '--no-tablespaces --column-statistics=0',
    ]);

    $result = $db->dump('/tmp/dump.sql');

    // Flags must appear before the database name (mysqldump treats post-db args as table names)
    expect($result->command)->toContain("'--no-tablespaces' '--column-statistics=0' 'myapp'")
        ->and($result->command)->toEndWith("> '/tmp/dump.sql'");
});

test('restore builds correct command with ssl-mode=DISABLED by default', function () {
    $result = $this->db->restore('/tmp/restore.sql');

    expect($result)->toBeInstanceOf(DatabaseOperationResult::class)
        ->and($result->command)->toBe("mysql --host='db.local' --port='3306' --user='root' --password='secret' --ssl-mode=DISABLED 'myapp' -e 'source /tmp/restore.sql'");
});

test('restore uses ssl-mode=REQUIRED when ssl_enabled is true', function () {
    $db = new MysqlDatabase;
    $db->setConfig([
        'host' => 'db.local',
        'port' => 3306,
        'user' => 'root',
        'pass' => 'secret',
        'database' => 'myapp',
        'ssl_enabled' => true,
    ]);

    $result = $db->restore('/tmp/restore.sql');

    expect($result->command)
        ->toContain('--ssl-mode=REQUIRED')
        ->not->toContain('--ssl-mode=DISABLED');
});

test('testConnection returns success when process succeeds', function () {
    Process::fake([
        '*' => Process::result(output: 'Uptime: 12345'),
    ]);

    $result = $this->db->testConnection();

    expect($result['success'])->toBeTrue()
        ->and($result['message'])->toBe('Connection successful')
        ->and($result['details'])->toHaveKey('ping_ms')
        ->and($result['details']['output'])->toBe('Uptime: 12345');
});

test('listDatabases returns databases excluding system databases', function () {
    $pdoStatement = Mockery::mock(\PDOStatement::class);
    $pdoStatement->shouldReceive('fetchAll')
        ->once()
        ->with(PDO::FETCH_COLUMN, 0)
        ->andReturn(['information_schema', 'performance_schema', 'mysql', 'sys', 'app_database', 'test_database']);

    $pdo = Mockery::mock(PDO::class);
    $pdo->shouldReceive('query')
        ->once()
        ->with('SHOW DATABASES')
        ->andReturn($pdoStatement);

    $db = Mockery::mock(MysqlDatabase::class)->makePartial()->shouldAllowMockingProtectedMethods();
    $db->shouldReceive('createPdo')->once()->andReturn($pdo);
    $db->setConfig(['host' => 'db.local', 'port' => 3306, 'user' => 'root', 'pass' => 'secret', 'database' => '']);

    $databases = $db->listDatabases();

    expect($databases)->toBe(['app_database', 'test_database']);
});

test('testConnection returns failure when process fails', function () {
    Process::fake([
        '*' => Process::result(exitCode: 1, errorOutput: 'Access denied for user'),
    ]);

    $result = $this->db->testConnection();

    expect($result['success'])->toBeFalse()
        ->and($result['message'])->toContain('Access denied');
});

test('dump refuses a flag that redirects where the client writes', function () {
    $db = new MysqlDatabase;
    $db->setConfig([
        'host' => 'db.local',
        'port' => 3306,
        'user' => 'root',
        'pass' => 'secret',
        'database' => 'myapp',
        'dump_flags' => '--result-file=/app/public/shell.php',
    ]);

    expect(fn () => $db->dump('/tmp/dump.sql'))
        ->toThrow(App\Exceptions\Backup\DatabaseDumpException::class, '--result-file=/app/public/shell.php');
});
