<?php
namespace PoP\API\TypeResolvers;

use PoP\ComponentModel\TypeResolvers\AbstractTypeResolver;
use PoP\API\Dataloader_Root;

class RootTypeResolver extends AbstractTypeResolver
{
    public const DATABASE_KEY = 'root';

    public function getDatabaseKey()
    {
        return self::DATABASE_KEY;
    }

    public function getId($resultItem)
    {
        return 'root';
        // $root = $resultItem;
        // return $root->getId();
    }

    public function getIdFieldTypeDataResolverClass()
    {
        return Dataloader_Root::class;
    }
}

