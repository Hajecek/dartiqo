<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

/** Odmítnutý online zápas. status je HTTP kód, error je strojový důvod. */
final class OnlineMatchException extends RuntimeException
{
    /**
     * @param array<string, mixed>|null $match
     */
    public function __construct(
        string $message,
        public readonly int $status = 409,
        public readonly ?string $error = null,
        public readonly ?array $match = null,
    ) {
        parent::__construct($message);
    }
}
