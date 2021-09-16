<?php

declare(strict_types=1);

namespace PoP\API\TypeResolvers\EnumType;

use PoP\API\Schema\SchemaDefinition;
use PoP\ComponentModel\TypeResolvers\EnumType\AbstractEnumTypeResolver;

class SchemaFieldShapeEnumTypeResolver extends AbstractEnumTypeResolver
{
    public function getTypeName(): string
    {
        return 'SchemaOutputShape';
    }
    /**
     * @return string[]
     */
    public function getEnumValues(): array
    {
        return [
            SchemaDefinition::ARGVALUE_SCHEMA_SHAPE_FLAT,
            SchemaDefinition::ARGVALUE_SCHEMA_SHAPE_NESTED,
        ];
    }
}
