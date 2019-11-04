<?php
namespace PoP\API\FieldResolvers;

use PoP\ComponentModel\FieldResolvers\AbstractFieldResolver;
use PoP\API\Dataloader_Root;

class RootFieldResolver extends AbstractFieldResolver
{
    public function getId($resultItem)
    {
        return 'root';
        // $root = $resultItem;
        // return $root->getId();
    }

    public function getIdFieldDataloaderClass()
    {
        return Dataloader_Root::class;
    }
}

