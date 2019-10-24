<?php
namespace PoP\API\RouteModuleProcessors;

use PoP\ModuleRouting\AbstractEntryRouteModuleProcessor;
use PoP\ComponentModel\Server\Utils;

class EntryRouteModuleProcessor extends AbstractEntryRouteModuleProcessor
{
    public function getModulesVarsPropertiesByNature()
    {
        $ret = array();

        // API
        if (!Utils::disableAPI()) {

            // Single endpoint (homepage)
            $ret[RouteNatures::HOME][] = [
                'module' => [PoP_API_Module_Processor_FieldDataloads::class, PoP_API_Module_Processor_FieldDataloads::MODULE_DATALOAD_DATAQUERY_ROOT_FIELDS],
                'conditions' => [
                    'scheme' => POP_SCHEME_API,
                ],
            ];
        }

        return $ret;
    }
}
