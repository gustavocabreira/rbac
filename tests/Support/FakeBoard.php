<?php

declare(strict_types=1);

namespace GustavoCabreira\Rbac\Tests\Support;

use GustavoCabreira\Rbac\Concerns\IsPermissionable;
use GustavoCabreira\Rbac\Contracts\Permissionable;

class FakeBoard implements Permissionable
{
    use IsPermissionable;

    /** @var array<int,self> */
    private static array $fixtures = [];

    public int $id;

    public function __construct(int $id = 0)
    {
        $this->id = $id;
    }

    public static function make(int $id): self
    {
        $instance = new self($id);
        self::$fixtures[$id] = $instance;

        return $instance;
    }

    public static function clearFixtures(): void
    {
        self::$fixtures = [];
    }

    protected static function rbacQueryByIds(array $ids): iterable
    {
        return array_values(array_filter(
            self::$fixtures,
            fn (self $b) => in_array($b->id, $ids, true)
        ));
    }
}
