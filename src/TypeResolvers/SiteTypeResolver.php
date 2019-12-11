<?php
namespace PoP\API\TypeResolvers;

use PoP\ComponentModel\TypeResolvers\AbstractTypeResolver;
use PoP\API\TypeDataLoaders\SiteTypeDataLoader;

class SiteTypeResolver extends AbstractTypeResolver
{
    public const NAME = 'Site';

    public function getTypeName(): string
    {
        return self::NAME;
    }

    public function getId($resultItem)
    {
        $site = $resultItem;
        return $site->getId();
    }

    public function getTypeDataLoaderClass(): string
    {
        return SiteTypeDataLoader::class;
    }
}

