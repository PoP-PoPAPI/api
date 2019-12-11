<?php
namespace PoP\API\TypeResolvers;

use PoP\ComponentModel\TypeResolvers\AbstractTypeResolver;
use PoP\API\TypeDataLoaders\RootTypeDataLoader;

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

    public function getTypeDataLoaderClass(): string
    {
        return RootTypeDataLoader::class;
    }
}

