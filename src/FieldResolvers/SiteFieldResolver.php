<?php
namespace PoP\API\FieldResolvers;

use PoP\ComponentModel\FieldResolvers\AbstractFieldResolver;
use PoP\API\Dataloader_Sites;

class SiteFieldResolver extends AbstractFieldResolver
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

