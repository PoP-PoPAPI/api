<?php
namespace PoP\API\Dataloaders;
use PoP\ComponentModel\Facades\ModuleProcessors\ModuleProcessorManagerFacade;

trait DataloaderAPITrait
{
    public function maybeFilterDataloadQueryArgs(array &$query, array $options = [])
    {
        if ($filterDataloadQueryArgsParams = $options['filter-dataload-query-args']) {
            $filterDataloadQueryArgsSource = $filterDataloadQueryArgsParams['source'];
            $filterDataloadingModule = $filterDataloadQueryArgsParams['module'];
            if ($filterDataloadQueryArgsSource && $filterDataloadingModule) {
                $moduleprocessor_manager = ModuleProcessorManagerFacade::getInstance();
                $moduleprocessor_manager->getProcessor($filterDataloadingModule)->filterHeadmoduleDataloadQueryArgs($filterDataloadingModule, $query, $filterDataloadQueryArgsSource);
            }
        }
    }
}
