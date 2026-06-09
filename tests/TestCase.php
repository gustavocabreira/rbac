<?php

declare(strict_types=1);

namespace GustavoCabreira\Rbac\Tests;

use GustavoCabreira\Rbac\Rbac;
use GustavoCabreira\Rbac\Seeding\DefinitionSeeder;
use GustavoCabreira\Rbac\Tests\Support\TestDatabase;
use PHPUnit\Framework\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    protected int $companyId = 1;

    protected function setUp(): void
    {
        parent::setUp();

        TestDatabase::beginTransaction();
        Rbac::configure(TestDatabase::pdo());
        Rbac::forCompany($this->companyId);

        $seeder = new DefinitionSeeder(TestDatabase::connection());
        $seeder->run();

        // Seed the 'fakeboard' module used by FakeBoard in integration tests.
        TestDatabase::connection()->statement(
            'INSERT INTO tb_Modules (modModuleSlug, modModuleName)
             VALUES (?, ?)
             ON DUPLICATE KEY UPDATE modModuleName = VALUES(modModuleName)',
            ['fakeboard', 'Fake Board']
        );
    }

    protected function tearDown(): void
    {
        try {
            Rbac::resolver()->flush();
        } catch (\RuntimeException $e) {
            // Rbac was reset inside the test body — nothing to flush
        }

        Rbac::reset();
        TestDatabase::rollback();

        parent::tearDown();
    }
}
