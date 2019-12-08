<?php
namespace PoP\API\TypeResolvers;

use PoP\ComponentModel\TypeResolvers\AbstractTypeResolver;
use PoP\API\Dataloader_Sites;

class SiteTypeResolver extends AbstractTypeResolver
{
    public const TYPE_COLLECTION_NAME = 'sites';

    public function getTypeCollectionName(): string
    {
        return self::TYPE_COLLECTION_NAME;
    }

    public function getId($resultItem)
    {
        $site = $resultItem;
        return $site->getId();
    }

    public function getIdFieldTypeDataResolverClass(): string
    {
        return Dataloader_Sites::class;
    }
}
