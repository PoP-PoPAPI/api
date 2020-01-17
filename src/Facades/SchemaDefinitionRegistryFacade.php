<?php
namespace PoP\API\Facades;

use PoP\API\Registries\SchemaDefinitionRegistryInterface;
use PoP\Root\Container\ContainerBuilderFactory;

class SchemaDefinitionRegistryFacade
{
    public static function getInstance(): SchemaDefinitionRegistryInterface
    {
        return ContainerBuilderFactory::getInstance()->get('schema_definition_registry');
    }
}
