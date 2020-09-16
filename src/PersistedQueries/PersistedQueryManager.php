<?php

declare(strict_types=1);

namespace PoP\API\PersistedQueries;

use PoP\API\Schema\SchemaDefinition;

class PersistedQueryManager implements PersistedQueryManagerInterface
{
    /**
     * @var array<string, string>
     */
    protected array $persistedQueries = [];
    /**
     * @var array<string, array>
     */
    protected array $persistedQueriesForSchema = [];

    public function getPersistedQueries(): array
    {
        return $this->persistedQueries;
    }

    public function getPersistedQueriesForSchema(): array
    {
        return $this->persistedQueriesForSchema;
    }

    public function hasPersistedQuery(string $queryName): bool
    {
        return isset($this->persistedQueries[$queryName]);
    }

    public function getPersistedQuery(string $queryName): ?string
    {
        return $this->persistedQueries[$queryName];
    }

    public function add(string $queryName, string $queryResolution, ?string $description = null): void
    {
        $this->persistedQueries[$queryName] = $queryResolution;
        $this->persistedQueriesForSchema[$queryName] = [
            SchemaDefinition::ARGNAME_NAME => $queryName,
        ];
        if ($description) {
            $this->persistedQueriesForSchema[$queryName][SchemaDefinition::ARGNAME_DESCRIPTION] = $description;
        }
        $this->persistedQueriesForSchema[$queryName][SchemaDefinition::ARGNAME_FRAGMENT_RESOLUTION] = $queryResolution;
    }
}
