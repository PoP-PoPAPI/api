<?php
namespace PoP\API\Registries;

use PoP\API\ObjectFacades\RootObjectFacade;
use PoP\API\TypeResolvers\RootTypeResolver;
use PoP\API\Registries\SchemaDefinitionRegistryInterface;
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
        return json_encode($fieldArgs ?? []).json_encode($options ?? []);
    }

    public function &getSchemaDefinition(?array $fieldArgs = [], ?array $options = []): array
    {
        // Create a key from the arrays, to cache the results
        $key = $this->getArgumentKey($fieldArgs, $options);
        if (is_null($this->schemaInstances[$key])) {
            $instanceManager = InstanceManagerFacade::getInstance();
            $rootTypeResolver = $instanceManager->getInstance(RootTypeResolver::class);
            $fieldQueryInterpreter = FieldQueryInterpreterFacade::getInstance();
            $root = RootObjectFacade::getInstance();
            $this->schemaInstances[$key] = $rootTypeResolver->resolveValue(
                $root,
                $fieldQueryInterpreter->getField('fullSchema', $fieldArgs ?? []),
                null,
                null,
                $options
            );
        }
        return $this->schemaInstances[$key];
    }

}
