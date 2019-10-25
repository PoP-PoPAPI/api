<?php
namespace PoP\API\RouteModuleProcessors;

use PoP\Routing\RouteNatures;
use PoP\ModuleRouting\AbstractEntryRouteModuleProcessor;

class EntryRouteModuleProcessor extends AbstractEntryRouteModuleProcessor
{
    public function getModulesVarsPropertiesByNature()
    {
        $ret = array();

        $ret[RouteNatures::HOME][] = [
            'module' => [\PoP_API_Module_Processor_FieldDataloads::class, \PoP_API_Module_Processor_FieldDataloads::MODULE_DATALOAD_DATAQUERY_ROOT_FIELDS],
            'conditions' => [
                'scheme' => POP_SCHEME_API,
            ],
        ];

        return $ret;
    }
}
