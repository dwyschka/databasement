<?php

use App\Exceptions\Backup\ConnectionException;
use App\Services\Backup\Databases\MssqlDatabase;
use App\Services\Backup\DTO\DatabaseOperationResult;
use App\Services\Backup\InMemoryBackupLogger;

beforeEach(function () {
    $this->db = new MssqlDatabase;
    $this->db->setConfig([
        'host' => 'mssql.example.com',
        'port' => 1433,
        'user' => 'sa',
        'pass' => 'Pa55w0rd!',
        'database' => 'app_db',
    ]);
});

test('dump produces sqlpackage extract command', function () {
    $result = $this->db->dump('/tmp/snapshot.dacpac');

    expect($result)->toBeInstanceOf(DatabaseOperationResult::class)
        ->and($result->command)->toBe(
            "sqlpackage /Action:Extract /TargetFile:'/tmp/snapshot.dacpac' "
            ."/SourceServerName:'mssql.example.com,1433' "
            ."/SourceDatabaseName:'app_db' "
            ."/SourceUser:'sa' "
            ."/SourcePassword:'Pa55w0rd!' "
            .'/SourceTrustServerCertificate:True '
            .'/SourceEncryptConnection:True '
            .'/p:ExtractAllTableData=True '
            .'/p:ExtractReferencedServerScopedElements=False '
            .'/p:IgnoreUserLoginMappings=True '
            .'/p:IgnorePermissions=True'
        );
});

test('dump appends user-provided dump flags', function () {
    $this->db->setConfig([
        'host' => 'mssql.example.com',
        'port' => 1433,
        'user' => 'sa',
        'pass' => 'Pa55w0rd!',
        'database' => 'app_db',
        'dump_flags' => '/Verbose:True',
    ]);

    $result = $this->db->dump('/tmp/snapshot.dacpac');

    expect($result->command)->toEndWith("/p:IgnorePermissions=True '/Verbose:True'");
});

test('restore produces sqlpackage publish command', function () {
    $result = $this->db->restore('/tmp/snapshot.dacpac');

    expect($result)->toBeInstanceOf(DatabaseOperationResult::class)
        ->and($result->command)->toBe(
            "sqlpackage /Action:Publish /SourceFile:'/tmp/snapshot.dacpac' "
            ."/TargetServerName:'mssql.example.com,1433' "
            ."/TargetDatabaseName:'app_db' "
            ."/TargetUser:'sa' "
            ."/TargetPassword:'Pa55w0rd!' "
            .'/TargetTrustServerCertificate:True '
            .'/TargetEncryptConnection:True'
        );
});

test('listDatabases filters out system databases', function () {
    $statement = Mockery::mock(\PDOStatement::class);
    $statement->shouldReceive('fetchAll')
        ->once()
        ->with(PDO::FETCH_COLUMN, 0)
        ->andReturn(['master', 'tempdb', 'model', 'msdb', 'app_db', 'reports']);

    $pdo = Mockery::mock(PDO::class);
    $pdo->shouldReceive('query')
        ->once()
        ->with('SELECT name FROM sys.databases ORDER BY name')
        ->andReturn($statement);

    $db = Mockery::mock(MssqlDatabase::class)->makePartial()->shouldAllowMockingProtectedMethods();
    $db->shouldReceive('createPdo')->once()->andReturn($pdo);
    $db->setConfig(['host' => 'h', 'port' => 1433, 'user' => 'u', 'pass' => 'p', 'database' => '']);

    expect($db->listDatabases())->toBe(['app_db', 'reports']);
});

test('executeQueries connects to the restored database and runs each statement', function () {
    $pdo = Mockery::mock(PDO::class);
    $pdo->shouldReceive('exec')->once()->with('UPDATE users SET password = NULL');
    $pdo->shouldReceive('exec')->once()->with('DELETE FROM sessions');

    $db = Mockery::mock(MssqlDatabase::class)->makePartial()->shouldAllowMockingProtectedMethods();
    $db->shouldReceive('createPdoForDatabase')->once()->with('restored_db')->andReturn($pdo);

    $db->executeQueries('restored_db', ['UPDATE users SET password = NULL', 'DELETE FROM sessions'], new InMemoryBackupLogger);
});

test('executeQueries wraps a failing statement in a ConnectionException', function () {
    $pdo = Mockery::mock(PDO::class);
    $pdo->shouldReceive('exec')->once()->andThrow(new PDOException('syntax error'));

    $db = Mockery::mock(MssqlDatabase::class)->makePartial()->shouldAllowMockingProtectedMethods();
    $db->shouldReceive('createPdoForDatabase')->once()->with('restored_db')->andReturn($pdo);

    expect(fn () => $db->executeQueries('restored_db', ['GARBAGE'], new InMemoryBackupLogger))
        ->toThrow(ConnectionException::class, 'Failed to run custom post-restore queries');
});

test('testConnection returns success with parsed version', function () {
    $version = 'Microsoft SQL Server 2022 (RTM) - 16.0.1000.6 (X64)';
    $statement = Mockery::mock(\PDOStatement::class);
    $statement->shouldReceive('fetchColumn')->andReturn($version);

    $pdo = Mockery::mock(PDO::class);
    $pdo->shouldReceive('query')->with('SELECT @@VERSION')->andReturn($statement);

    $db = Mockery::mock(MssqlDatabase::class)->makePartial()->shouldAllowMockingProtectedMethods();
    $db->shouldReceive('createPdo')->once()->andReturn($pdo);
    $db->setConfig(['host' => 'h', 'port' => 1433, 'user' => 'u', 'pass' => 'p', 'database' => 'app_db']);

    $result = $db->testConnection();

    expect($result['success'])->toBeTrue()
        ->and($result['message'])->toBe('Connection successful')
        ->and($result['details'])->toHaveKey('ping_ms')
        ->and($result['details']['output'])->toContain('Microsoft SQL Server 2022');
});

test('testConnection returns failure when PDO throws', function () {
    $db = Mockery::mock(MssqlDatabase::class)->makePartial()->shouldAllowMockingProtectedMethods();
    $db->shouldReceive('createPdo')->once()->andThrow(new PDOException('Login failed for user'));
    $db->setConfig(['host' => 'h', 'port' => 1433, 'user' => 'u', 'pass' => 'p', 'database' => 'app_db']);

    $result = $db->testConnection();

    expect($result['success'])->toBeFalse()
        ->and($result['message'])->toContain('Login failed');
});
