<?php
namespace PoP\API\TypeDataResolvers;

use PoP\API\ObjectFacades\RootObjectFacade;
use PoP\API\TypeResolvers\RootTypeResolver;
use PoP\ComponentModel\TypeDataResolvers\AbstractTypeDataResolver;

class RootTypeDataResolver extends AbstractTypeDataResolver
{
    public function getTypeResolverClass(): string
    {
        return RootTypeResolver::class;
    }

    public function resolveObjectsFromIDs(array $ids): array
    {
        return [RootObjectFacade::getInstance()];
    }
}
