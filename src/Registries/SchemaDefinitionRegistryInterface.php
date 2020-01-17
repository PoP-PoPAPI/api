<?php
namespace PoP\API\Registries;

interface SchemaDefinitionRegistryInterface {
    public function &getSchemaDefinition(?array $fieldArgs = [], ?array $options = []): array;
}
