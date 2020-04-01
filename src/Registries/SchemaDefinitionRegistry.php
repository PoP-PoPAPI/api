<?php
namespace PoP\API\Registries;

use PoP\API\Cache\CacheTypes;
use PoP\API\ComponentConfiguration;
use PoP\API\ObjectFacades\RootObjectFacade;
use PoP\API\TypeResolvers\RootTypeResolver;
use PoP\API\Registries\SchemaDefinitionRegistryInterface;
use PoP\ComponentModel\Facades\Cache\PersistentCacheFacade;
use PoP\ComponentModel\Facades\Instances\InstanceManagerFacade;
use PoP\ComponentModel\Facades\Schema\FieldQueryInterpreterFacade;

class SchemaDefinitionRegistry implements SchemaDefinitionRegistryInterface {

    protected $schemaInstances;

    /**
     * Create a key from the arrays, to cache the results
     *
     * @param array $fieldArgs
     * @param array|null $options
     * @return string
     */
    protected function getArgumentKey(?array $fieldArgs, ?array $options): string
    {
        // For the persistentCache (no need for in-memory cache), use a hash to remove invalid characters (such as "()")
        $key = json_encode($fieldArgs ?? []).json_encode($options ?? []);
        return hash('md5', $key);
    }

    /**
     * Produce the schema definition. It can store the value in the cache.
     * Use cache with care: if the schema is dynamic, it should not be cached.
     * Public schema: can cache, Private schema: cannot cache.
     *
     * @param array|null $fieldArgs
     * @param array|null $options
     * @return array
     */
    public function &getSchemaDefinition(?array $fieldArgs = [], ?array $options = []): array
    {
        // Create a key from the arrays, to cache the results
        $key = $this->getArgumentKey($fieldArgs, $options);
        if (is_null($this->schemaInstances[$key])) {
            // Attempt to retrieve from the cache, if enabled
            if ($useCache = ComponentConfiguration::useSchemaDefinitionCache()) {
                $persistentCache = PersistentCacheFacade::getInstance();
            }
            if ($useCache) {
                if ($persistentCache->hasCache($key, CacheTypes::SCHEMA_DEFINITION)) {
                    $schemaDefinition = $persistentCache->getCache($key, CacheTypes::SCHEMA_DEFINITION);
                }
            }
            // If either not using cache, or using but the value had not been cached, then calculate the value
            if (!$schemaDefinition) {
                $instanceManager = InstanceManagerFacade::getInstance();
                $rootTypeResolver = $instanceManager->getInstance(RootTypeResolver::class);
                $fieldQueryInterpreter = FieldQueryInterpreterFacade::getInstance();
                $root = RootObjectFacade::getInstance();
                $schemaDefinition = $rootTypeResolver->resolveValue(
                    $root,
                    $fieldQueryInterpreter->getField('fullSchema', $fieldArgs ?? []),
                    null,
                    null,
                    $options
                );

                // Store in the cache
                if ($useCache) {
                    $persistentCache->storeCache($key, CacheTypes::SCHEMA_DEFINITION, $schemaDefinition);
                }
            }
            // Assign to in-memory cache
            $this->schemaInstances[$key] = $schemaDefinition;
        }
        return $this->schemaInstances[$key];
    }

}
