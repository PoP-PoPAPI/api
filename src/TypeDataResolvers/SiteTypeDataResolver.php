<?php
namespace PoP\API\TypeDataResolvers;

use PoP\API\ObjectFacades\SiteObjectFacade;
use PoP\API\TypeResolvers\SiteTypeResolver;
use PoP\ComponentModel\TypeDataResolvers\AbstractTypeDataResolver;

class SiteTypeDataResolver extends AbstractTypeDataResolver
{
    public function getTypeResolverClass(): string
    {
        return SiteTypeResolver::class;
    }

    public function resolveObjectsFromIDs(array $ids): array
    {
        // Currently it deals only with the current site and nothing else
        $ret = [];
        $cmsengineapi = \PoP\Engine\FunctionAPIFactory::getInstance();
        if (in_array($cmsengineapi->getHost(), $ids)) {
            $ret[] = SiteObjectFacade::getInstance();
        }
        return $ret;
    }
}
