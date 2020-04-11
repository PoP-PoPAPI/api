<?php
namespace PoP\API\RouteModuleProcessors;

use PoP\Routing\RouteNatures;
use PoP\ModuleRouting\AbstractEntryRouteModuleProcessor;
use PoP\API\ModuleProcessors\RootRelationalFieldDataloadModuleProcessor;

class EntryRouteModuleProcessor extends AbstractEntryRouteModuleProcessor
{
    public function getModulesVarsPropertiesByNature()
    {
        $ret = array();

        $ret[RouteNatures::HOME][] = [
            'module' => [RootRelationalFieldDataloadModuleProcessor::class, RootRelationalFieldDataloadModuleProcessor::MODULE_DATALOAD_RELATIONALFIELDS_ROOT],
            'conditions' => [
                'scheme' => POP_SCHEME_API,
            ],
        ];

        return $ret;
    }
}
