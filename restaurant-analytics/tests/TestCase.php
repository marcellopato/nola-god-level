<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;

abstract class TestCase extends BaseTestCase
{
    /**
     * Ensure the SQL schema is loaded once for the test run.
     * Many domain migrations are provided as a SQL dump in
     * database/database-schema.sql, so we load it here so
     * tests that expect tables like `sales` exist will pass.
     */
    protected static bool $schemaLoaded = false;

    /**
     * Create the application instance for tests.
     */
    public function createApplication()
    {
        $app = require __DIR__ . '/../bootstrap/app.php';
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        if (!static::$schemaLoaded) {
            $this->loadSchemaIfNeeded();
            static::$schemaLoaded = true;
        }
    }

    protected function loadSchemaIfNeeded(): void
    {
        $sqlPath = base_path('database/database-schema.sql');
        if (file_exists($sqlPath)) {
            $sql = file_get_contents($sqlPath);
            
            // Add IF NOT EXISTS to avoid conflicts
            $sql = str_replace('CREATE TABLE ', 'CREATE TABLE IF NOT EXISTS ', $sql);
            $sql = str_replace('CREATE INDEX ', 'CREATE INDEX IF NOT EXISTS ', $sql);
            
            // Handle the INSERT that might conflict
            $sql = str_replace(
                "INSERT INTO brands (id, name) VALUES (1, 'Challenge Restaurant Group');",
                "INSERT INTO brands (id, name) VALUES (1, 'Challenge Restaurant Group') ON CONFLICT (id) DO NOTHING;",
                $sql
            );
            
            // Run raw SQL to create domain tables/indexes only once.
            DB::unprepared($sql);
        }
    }
}
