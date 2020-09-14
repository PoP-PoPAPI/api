<?php

declare(strict_types=1);

namespace PoP\API;

use PoP\ComponentModel\ComponentConfiguration\EnvironmentValueHelpers;
use PoP\ComponentModel\ComponentConfiguration\ComponentConfigurationTrait;
use PoP\ComponentModel\ComponentConfiguration as ComponentModelComponentConfiguration;

class ComponentConfiguration
{
    use ComponentConfigurationTrait;

    private static $useSchemaDefinitionCache;
    private static $executeQueryBatchInStrictOrder;
    private static $enableEmbeddableFields;

    public static function useSchemaDefinitionCache(): bool
    {
        // First check that the Component Model cache is enabled
        if (!ComponentModelComponentConfiguration::useComponentModelCache()) {
            return false;
        }

        // Define properties
        $envVariable = Environment::USE_SCHEMA_DEFINITION_CACHE;
        $selfProperty = &self::$useSchemaDefinitionCache;
        $defaultValue = false;
        $callback = [EnvironmentValueHelpers::class, 'toBool'];

        // Initialize property from the environment/hook
        self::maybeInitializeConfigurationValue(
            $envVariable,
            $selfProperty,
            $defaultValue,
            $callback
        );
        return $selfProperty;
    }

    public static function executeQueryBatchInStrictOrder(): bool
    {
        // Define properties
        $envVariable = Environment::EXECUTE_QUERY_BATCH_IN_STRICT_ORDER;
        $selfProperty = &self::$executeQueryBatchInStrictOrder;
        $defaultValue = true;
        $callback = [EnvironmentValueHelpers::class, 'toBool'];

        // Initialize property from the environment/hook
        self::maybeInitializeConfigurationValue(
            $envVariable,
            $selfProperty,
            $defaultValue,
            $callback
        );
        return $selfProperty;
    }

    public static function enableEmbeddableFields(): bool
    {
        // Define properties
        $envVariable = Environment::ENABLE_EMBEDDABLE_FIELDS;
        $selfProperty = &self::$enableEmbeddableFields;
        $defaultValue = false;
        $callback = [EnvironmentValueHelpers::class, 'toBool'];

        // Initialize property from the environment/hook
        self::maybeInitializeConfigurationValue(
            $envVariable,
            $selfProperty,
            $defaultValue,
            $callback
        );
        return $selfProperty;
    }
}
