<?php
namespace PoP\API;

class Environment
{
    public const USE_SCHEMA_DEFINITION_CACHE = 'USE_SCHEMA_DEFINITION_CACHE';

    public static function disableAPI(): bool
    {
        return isset($_ENV['DISABLE_API']) ? strtolower($_ENV['DISABLE_API']) == "true" : false;
    }

    public static function useSchemaDefinitionCache(): bool
    {
        return isset($_ENV[self::USE_SCHEMA_DEFINITION_CACHE]) ? strtolower($_ENV[self::USE_SCHEMA_DEFINITION_CACHE]) == "true" : false;
    }
}
