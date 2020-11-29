<?php

declare(strict_types=1);

namespace PoP\API\PersistedQueries;

use PoP\API\Schema\SchemaDefinition;

abstract class AbstractSchemaPersistedQueryManager extends AbstractPersistedQueryManager implements SchemaPersistedQueryManagerInterface
{
    /**
     * @var array<string, array>
     */
    protected array $persistedQueriesForSchema = [];

    public function getPersistedQueriesForSchema(): array
    {
        return $this->persistedQueriesForSchema;
    }

    public function add(string $queryName, string $queryResolution, ?string $description = null): void
    {
        parent::add($queryName, $queryResolution, $description);

        $this->persistedQueriesForSchema[$queryName] = [
            SchemaDefinition::ARGNAME_NAME => $queryName,
        ];
        if ($description) {
            $this->persistedQueriesForSchema[$queryName][SchemaDefinition::ARGNAME_DESCRIPTION] = $description;
        }
        $this->persistedQueriesForSchema[$queryName][SchemaDefinition::ARGNAME_FRAGMENT_RESOLUTION] = $queryResolution;
    }
}
