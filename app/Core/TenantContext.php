<?php

declare(strict_types=1);

namespace App\Core;

final class TenantContext
{
    public function __construct(
        private readonly int $churchId,
        private readonly string $role
    ) {
        if ($churchId <= 0) {
            throw new \InvalidArgumentException('A valid church_id is required.');
        }
    }

    public static function fromSession(): self
    {
        $auth = Session::get('auth', []);
        if (!is_array($auth)) {
            $auth = [];
        }
        return new self((int) ($auth['church_id'] ?? 0), (string) ($auth['church_role'] ?? ''));
    }

    public function churchId(): int
    {
        return $this->churchId;
    }

    public function role(): string
    {
        return $this->role;
    }
}
