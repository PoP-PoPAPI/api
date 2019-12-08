<?php
namespace PoP\API\TypeResolvers;

use PoP\ComponentModel\TypeResolvers\AbstractTypeResolver;
use PoP\API\Dataloader_Sites;

class SiteTypeResolver extends AbstractTypeResolver
{
    public const DATABASE_KEY = 'sites';

    public function getDatabaseKey(): string
    {
        return self::DATABASE_KEY;
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

