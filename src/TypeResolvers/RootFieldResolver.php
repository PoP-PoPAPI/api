<?php
namespace PoP\API\TypeResolvers;

use PoP\ComponentModel\TypeResolvers\AbstractTypeResolver;
use PoP\API\Dataloader_Root;

class RootTypeResolver extends AbstractTypeResolver
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

