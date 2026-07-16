<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

/**
 * Base class for tests that touch the database. Each test gets a fresh
 * throwaway SQLite file built from database/schema.sql, so tests never
 * read or write database/mtpar.sqlite.
 */
abstract class DatabaseTestCase extends TestCase
{
    private string $dbPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dbPath = tempnam(sys_get_temp_dir(), 'mtpar_test_') . '.sqlite';
        \Database::usePath($this->dbPath);
        \Database::getConnection()->exec(file_get_contents(MTPAR_TEST_SCHEMA));
    }

    protected function tearDown(): void
    {
        \Database::usePath(':memory:');
        @unlink($this->dbPath);

        parent::tearDown();
    }
}
