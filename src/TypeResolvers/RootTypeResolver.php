<?php
namespace PoP\API\TypeResolvers;

use PoP\ComponentModel\TypeResolvers\AbstractTypeResolver;
use PoP\API\TypeDataResolvers\RootTypeDataResolver;

class RootTypeResolver extends AbstractTypeResolver
{
    public const NAME = 'Root';

    public function getTypeName(): string
    {
        return self::NAME;
    }

    public function getId($resultItem)
    {
        $root = $resultItem;
        return $root->getId();
    }

    public function getIdFieldTypeDataResolverClass(): string
    {
        return RootTypeDataResolver::class;
    }
}

