<?php

declare(strict_types=1);

namespace PoP\API\PersistedQueries;

interface PersistedQueryManagerInterface
{
    public function getPersistedQueries(): array;
    public function getPersistedQueriesForSchema(): array;
    public function getPersistedQuery(string $queryName): ?string;
    public function hasPersistedQuery(string $queryName): bool;
    public function add(string $queryName, string $queryResolution, ?string $description = null): void;
}
