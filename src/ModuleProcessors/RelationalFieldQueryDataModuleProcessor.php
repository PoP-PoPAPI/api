<?php
namespace PoP\API\ModuleProcessors;
use PoP\ComponentModel\ModuleProcessors\AbstractRelationalFieldQueryDataModuleProcessor;

class RelationalFieldQueryDataModuleProcessor extends AbstractRelationalFieldQueryDataModuleProcessor
{
    public const MODULE_LAYOUT_RELATIONALFIELDS = 'layout-relationalfields';

    public function getModulesToProcess(): array
    {
        return array(
            [self::class, self::MODULE_LAYOUT_RELATIONALFIELDS],
        );
    }
}



