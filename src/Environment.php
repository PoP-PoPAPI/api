<?php
namespace PoP\API;

class Environment
{
    public static function disableAPI(): bool
    {
        return isset($_ENV['DISABLE_API']) ? strtolower($_ENV['DISABLE_API']) == "true" : false;
    }
    public static function usePrivateSchemaMode(): bool
    {
        return isset($_ENV['USE_PRIVATE_SCHEMA_MODE']) ? strtolower($_ENV['USE_PRIVATE_SCHEMA_MODE']) == "true" : false;
    }
}

