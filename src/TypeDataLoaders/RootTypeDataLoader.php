<?php
namespace PoP\API\TypeDataLoaders;

use PoP\API\ObjectFacades\RootObjectFacade;
use PoP\ComponentModel\TypeDataLoaders\AbstractTypeDataLoader;

class RootTypeDataLoader extends AbstractTypeDataLoader
{
    public function resolveObjectsFromIDs(array $ids): array
    {
        return [RootObjectFacade::getInstance()];
    }
}
