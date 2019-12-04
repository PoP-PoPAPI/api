<?php
namespace PoP\API\TypeResolvers;

use PoP\ComponentModel\TypeResolvers\AbstractTypeResolver;
use PoP\API\Dataloader_Sites;

class SiteTypeResolver extends AbstractTypeResolver
{
    public function getId($resultItem)
    {
        $site = $resultItem;
        return $site->getId();
    }

    public function getIdFieldDataloaderClass()
    {
        return Dataloader_Sites::class;
    }
}

