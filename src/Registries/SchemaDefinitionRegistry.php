<?php

declare(strict_types=1);

namespace PoP\API\Registries;

use PoP\API\Cache\CacheTypes;
use PoP\API\ComponentConfiguration;
use PoP\Engine\ObjectFacades\RootObjectFacade;
use PoP\Engine\TypeResolvers\RootTypeResolver;
use PoP\API\Registries\SchemaDefinitionRegistryInterface;
use PoP\ComponentModel\Facades\Cache\PersistentCacheFacade;
use PoP\ComponentModel\Facades\Instances\InstanceManagerFacade;
use PoP\ComponentModel\Facades\Schema\FieldQueryInterpreterFacade;
use PoP\ComponentModel\State\ApplicationState;
use PoP\ComponentModel\Configuration\Request;

class SchemaDefinitionRegistry implements SchemaDefinitionRegistryInterface
{

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
        return json_encode($fieldArgs ?? []) . json_encode($options ?? []);
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
                // Use different caches for the normal and namespaced schemas,  or
                // it throws exception if switching without deleting the cache (eg: when passing ?use_namespace=1)
                $vars = ApplicationState::getVars();
                $cacheType = CacheTypes::SCHEMA_DEFINITION;
                $cacheKeyComponents = [
                    'namespaced' => $vars['namespace-types-and-interfaces'],
                    'version-constraint' => Request::getVersionConstraint() ?? '',
                    'field-version-constraints' => Request::getVersionConstraintsForFields() ?? [],
                    'directive-version-constraints' => Request::getVersionConstraintsForDirectives() ?? [],
                ];
                // For the persistentCache, use a hash to remove invalid characters (such as "()")
                $cacheKey = hash('md5', $key . '|' . json_encode($cacheKeyComponents));
            }
            if ($useCache) {
                if ($persistentCache->hasCache($cacheKey, $cacheType)) {
                    $schemaDefinition = $persistentCache->getCache($cacheKey, $cacheType);
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
                    $persistentCache->storeCache($cacheKey, $cacheType, $schemaDefinition);
                }
            }
            // Assign to in-memory cache
            $this->schemaInstances[$key] = $schemaDefinition;
        }
        return $this->schemaInstances[$key];
    }
}
