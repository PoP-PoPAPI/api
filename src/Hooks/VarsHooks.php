<?php
namespace PoP\API\Hooks;
use PoP\API\Schema\QueryInputs;
use PoP\ComponentModel\Engine_Vars;
use PoP\ComponentModel\Server\Utils;
use PoP\Engine\Hooks\AbstractHookSet;
use PoP\ComponentModel\DataQueryManagerFactory;
use PoP\Translation\Facades\TranslationAPIFacade;
use PoP\ComponentModel\ModelInstance\ModelInstance;
use PoP\API\Facades\Schema\FieldQueryConvertorFacade;

class VarsHooks extends AbstractHookSet
{
    protected function init()
    {
        // Add functions as hooks, so we allow PoP_Application to set the 'routing-state' first
        $this->hooksAPI->addAction(
            '\PoP\ComponentModel\Engine_Vars:addVars',
            array($this, 'addVars'),
            10,
            1
        );
        $this->hooksAPI->addFilter(
            ModelInstance::HOOK_COMPONENTS_RESULT,
            array($this, 'getModelInstanceComponentsFromVars')
        );
    }

    public function addVars($vars_in_array)
    {
        // Allow WP API to set the "routing-state" first
        // Each page is an independent configuration
        $vars = &$vars_in_array[0];
        if (!Utils::disableAPI() && $vars['scheme'] == POP_SCHEME_API) {
            $this->addFieldsToVars($vars);
        } elseif ($vars['nature'] == POP_NATURE_STANDARD) {
            $dataquery_manager = DataQueryManagerFactory::getInstance();
            $route = $vars['route'];

            // Special pages: dataqueries' cacheablepages serve layouts, noncacheable pages serve fields.
            // So the settings for these pages depend on the URL params
            if (in_array($route, $dataquery_manager->getNonCacheableRoutes())) {
                $this->addFieldsToVars($vars);
            }
        }
    }

    private function addFieldsToVars(&$vars)
    {
        if (isset($_REQUEST[QueryInputs::QUERY])) {
            // The fields param can either be an array or a string. Convert them to array
            $vars['query'] = $_REQUEST[QueryInputs::QUERY];
            if (is_string($vars['query'])) {
                $vars['query'] = FieldQueryConvertorFacade::getInstance()->convertAPIQuery($vars['query']);
            }
        }
    }

    private function addFieldsToComponents(&$components)
    {
        $vars = Engine_Vars::getVars();
        if ($fields = $vars['query']) {
            // Serialize instead of implode, because $fields can contain $key => $value
            $components[] = TranslationAPIFacade::getInstance()->__('fields:', 'pop-engine').serialize($fields);
        }
    }

    public function getModelInstanceComponentsFromVars($components)
    {
        // Allow WP API to set the "routing-state" first
        // Each page is an independent configuration
        $vars = Engine_Vars::getVars();
        if (!Utils::disableAPI() && $vars['scheme'] == POP_SCHEME_API) {
            $this->addFieldsToComponents($components);
        } elseif ($vars['routing-state']['is-standard']) {
            $dataquery_manager = DataQueryManagerFactory::getInstance();
            $route = $vars['route'];

            // Special pages: dataqueries' cacheablepages serve layouts, noncacheable pages serve fields.
            // So the settings for these pages depend on the URL params
            if (in_array($route, $dataquery_manager->getNonCacheableRoutes())) {
                $this->addFieldsToComponents($components);
            }
        }

        return $components;
    }
}
