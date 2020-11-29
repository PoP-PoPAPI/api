<?php

declare(strict_types=1);

namespace PoP\API\PersistedQueries;

interface SchemaPersistedQueryManagerInterface extends PersistedQueryManagerInterface
{
    public function getPersistedQueriesForSchema(): array;
}
