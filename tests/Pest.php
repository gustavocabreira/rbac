<?php

declare(strict_types=1);

use GustavoCabreira\Rbac\Tests\Support\TestDatabase;

uses(\GustavoCabreira\Rbac\Tests\TestCase::class)->in('Feature');

beforeAll(function (): void {
    TestDatabase::setup();
});
