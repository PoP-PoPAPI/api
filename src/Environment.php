<?php

declare(strict_types=1);

namespace PoP\API;

class Environment
{
    public const USE_SCHEMA_DEFINITION_CACHE = 'USE_SCHEMA_DEFINITION_CACHE';
    public const EXECUTE_QUERY_BATCH_IN_STRICT_ORDER = 'EXECUTE_QUERY_BATCH_IN_STRICT_ORDER';
    public const ENABLE_EMBEDDABLE_FIELDS = 'ENABLE_EMBEDDABLE_FIELDS';

    public static function disableAPI(): bool
    {
        return isset($_ENV['DISABLE_API']) ? strtolower($_ENV['DISABLE_API']) == "true" : false;
    }
}
