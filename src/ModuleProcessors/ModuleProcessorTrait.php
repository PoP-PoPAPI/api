<?php
namespace PoP\API\ModuleProcessors;
use PoP\ComponentModel\Schema\QuerySyntax;
use PoP\ComponentModel\Facades\Managers\ModuleProcessorManagerFacade;

trait ModuleProcessorTrait
{
    public function getDataloadMultidomainQuerySources(array $module, array &$props): array
    {
        if ($sources = $this->getDataloadMultidomainSources($module, $props)) {
            // If this website and the external one have the same software installed, then the external site can already retrieve the needed data
            // Otherwise, this website needs to explicitly request what data is needed to the external one
            if (\PoP\ComponentModel\Server\Utils::externalSitesRunSameSoftware()) {
                return parent::getDataloadMultidomainQuerySources($module, $props);
            }
            $cmsenginehelpers = \PoP\Engine\HelperAPIFactory::getInstance();
            $moduleprocessor_manager = ModuleProcessorManagerFacade::getInstance();
            $flattened_datafields = $moduleprocessor_manager->getProcessor($module)->getDatasetmoduletreeSectionFlattenedDataFields($module, $props);
            $apifields = [];
            $heap = [
                '' => [&$flattened_datafields],
            ];
            while (!empty($heap)) {

                // Obtain and remove first element from the heap
                reset($heap);
                $key = key($heap);
                $key_dataitems = $heap[$key];
                unset($heap[$key]);

                foreach ($key_dataitems as &$key_data) {

                    // If there are data fields, add them separated by "|"
                    // If not, and we're inside a subcomponent, there is no need to add the subcomponent's key alone, since the engine already includes this field as a data-field (so it was added in the previous iteration)
                    if ($key_datafields = $key_data['data-fields']) {
                        // Make sure the fields are not repeated, and no empty values
                        $apifields[] = $key.implode(QuerySyntax::SYMBOL_FIELDPROPERTIES_SEPARATOR, array_values(array_unique(array_filter($key_datafields))));
                    }

                    // If there are subcomponents, add them into the heap
                    if ($key_data['subcomponents']) {
                        foreach ($key_data['subcomponents'] as $subcomponent_key => &$subcomponent_dataloader_data) {
                            foreach ($subcomponent_dataloader_data as $subcomponent_dataloader => &$subcomponent_data) {
                                // Add the previous key, generating a path
                                $heap[$key.$subcomponent_key.QuerySyntax::SYMBOL_RELATIONALFIELDS_NEXTLEVEL][] = &$subcomponent_data;
                            }
                        }
                    }
                }
            }

            if ($apifields) {
                return array_map(
                    function ($source) use ($cmsenginehelpers, $apifields) {
                        return
                            $cmsenginehelpers->addQueryArgs(
                                [
                                    GD_URLPARAM_FIELDS => implode(
                                        QuerySyntax::SYMBOL_QUERYFIELDS_SEPARATOR,
                                        $apifields
                                    ),
                                ],
                                \PoP\Engine\APIUtils::getEndpoint(
                                    $source,
                                    [
                                        GD_URLPARAM_DATAOUTPUTITEMS_MODULEDATA,
                                        GD_URLPARAM_DATAOUTPUTITEMS_DATABASES,
                                        GD_URLPARAM_DATAOUTPUTITEMS_META,
                                    ]
                                )
                            );
                    },
                    $sources
                );
            }
            return $sources;
        }

        return array();
    }
}
