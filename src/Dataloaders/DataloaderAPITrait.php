<?php
namespace PoP\API\Dataloaders;
use PoP\ComponentModel\Facades\Managers\ModuleProcessorManagerFacade;

trait DataloaderAPITrait
{
    public function maybeFilterDataloadQueryArgs(array &$query, array $options = [])
    {
        // // Accept field atts to filter the API fields
        // $vars = \PoP\ComponentModel\Engine_Vars::getVars();
        // if (!\PoP\API\Configuration\Environment::disableAPI() && $vars['scheme'] == POP_SCHEME_API) {
        if ($filterDataloadQueryArgsParams = $options['filter-dataload-query-args']) {
            $filterDataloadQueryArgsSource = $filterDataloadQueryArgsParams['source'];
            $filterDataloadingModule = $filterDataloadQueryArgsParams['module'];
            if ($filterDataloadQueryArgsSource && $filterDataloadingModule) {
                $moduleprocessor_manager = ModuleProcessorManagerFacade::getInstance();
                $moduleprocessor_manager->getProcessor($filterDataloadingModule)->filterHeadmoduleDataloadQueryArgs($filterDataloadingModule, $query, $filterDataloadQueryArgsSource);
            }
        }
        // }
    }
}
