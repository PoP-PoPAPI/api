<?php
namespace PoP\API\TypeResolvers;

use PoP\ComponentModel\TypeResolvers\AbstractTypeResolver;
use PoP\API\Dataloader_Root;

class RootTypeResolver extends AbstractTypeResolver
{
    public const TYPE_COLLECTION_NAME = 'root';

    public function getTypeCollectionName(): string
    {
        return self::TYPE_COLLECTION_NAME;
    }

    public function getId($resultItem)
    {
        $root = $resultItem;
        return $root->getId();
    }

    public function getIdFieldTypeDataResolverClass(): string
    {
        return Dataloader_Root::class;
    }
}

