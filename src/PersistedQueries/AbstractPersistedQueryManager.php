<?php

declare(strict_types=1);

namespace PoP\API\PersistedQueries;

use PoP\API\Schema\QuerySymbols;

abstract class AbstractPersistedQueryManager implements PersistedQueryManagerInterface
{
    /**
     * @var array<string, string>
     */
    protected array $persistedQueries = [];

    public function getPersistedQueries(): array
    {
        return $this->persistedQueries;
    }

    public function hasPersistedQuery(string $queryName): bool
    {
        return isset($this->persistedQueries[$queryName]);
    }

    public function getPersistedQuery(string $queryName): ?string
    {
        return $this->persistedQueries[$queryName];
    }

    /**
     * If the query starts with "!" then it is the query name to a persisted query
     */
    public function isPersistedQuery(string $query): bool
    {
        return substr($query, 0, strlen(QuerySymbols::PERSISTED_QUERY)) == QuerySymbols::PERSISTED_QUERY;
    }

    /**
     * Remove "!" to get the persisted query name
     */
    public function getPersistedQueryName(string $query): string
    {
        return substr($query, strlen(QuerySymbols::PERSISTED_QUERY));
    }

    public function add(string $queryName, string $queryResolution, ?string $description = null): void
    {
        $this->persistedQueries[$queryName] = $queryResolution;
    }
}
