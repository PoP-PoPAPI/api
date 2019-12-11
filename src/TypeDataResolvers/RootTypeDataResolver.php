<?php
namespace PoP\API\TypeDataResolvers;

use PoP\API\ObjectFacades\RootObjectFacade;
use PoP\ComponentModel\TypeDataResolvers\AbstractTypeDataResolver;

class RootTypeDataResolver extends AbstractTypeDataResolver
{
    public function resolveObjectsFromIDs(array $ids): array
    {
        return [RootObjectFacade::getInstance()];
    }
}
