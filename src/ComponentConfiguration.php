<?php

declare(strict_types=1);

namespace PoP\API;

use PoP\ComponentModel\AbstractComponentConfiguration;
use PoP\ComponentModel\ComponentConfiguration as ComponentModelComponentConfiguration;

class ComponentConfiguration extends AbstractComponentConfiguration
{
    private static $useSchemaDefinitionCache;

    public static function useSchemaDefinitionCache(): bool
    {
        // First check that the Component Model cache is enabled
        if (!ComponentModelComponentConfiguration::useComponentModelCache()) {
            return false;
        }

        // Define properties
        $envVariable = Environment::USE_SCHEMA_DEFINITION_CACHE;
        $selfProperty = &self::$useSchemaDefinitionCache;
        $callback = [Environment::class, 'useSchemaDefinitionCache'];

        // Initialize property from the environment/hook
        self::maybeInitEnvironmentVariable(
            $envVariable,
            $selfProperty,
            $callback
        );
        return $selfProperty;
    }
}
