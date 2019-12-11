<?php
namespace PoP\API\TypeResolvers;

use PoP\ComponentModel\TypeResolvers\AbstractTypeResolver;
use PoP\API\TypeDataResolvers\SiteTypeDataResolver;

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

    public function getIdFieldTypeDataResolverClass(): string
    {
        return SiteTypeDataResolver::class;
    }
}

