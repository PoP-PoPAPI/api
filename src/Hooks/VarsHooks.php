<?php
namespace PoP\API\Hooks;
use PoP\Translation\Facades\TranslationAPIFacade;
use PoP\Hooks\Facades\HooksAPIFacade;
use PoP\API\Facades\Schema\FieldQueryConvertorFacade;
use PoP\ComponentModel\Server\Utils;

class VarsHooks
{
    public function __construct()
    {
        // Add functions as hooks, so we allow PoP_Application to set the 'routing-state' first
        HooksAPIFacade::getInstance()->addAction(
            '\PoP\ComponentModel\Engine_Vars:addVars',
            array($this, 'addVars'),
            10,
            1
        );
        HooksAPIFacade::getInstance()->addFilter(
            \PoP\ComponentModel\ModelInstance\ModelInstance::HOOK_COMPONENTS_RESULT,
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
            } elseif (in_array($route, $dataquery_manager->getCacheableRoutes())) {
                if ($layouts = $_REQUEST[GD_URLPARAM_LAYOUTS]) {
                    $layouts = is_array($layouts) ? $layouts : array($layouts);
                    $vars['layouts'] = $layouts;
                }
            }
        }
    }

    private function addFieldsToVars(&$vars)
    {
        if (isset($_REQUEST[GD_URLPARAM_FIELDS])) {
            // The fields param can either be an array or a string. Convert them to array
            $vars['fields'] = $_REQUEST[GD_URLPARAM_FIELDS];
            if (is_string($vars['fields'])) {
                $vars['fields'] = FieldQueryConvertorFacade::getInstance()->convertAPIQuery($vars['fields']);
            }
        }
    }

    private function addFieldsToComponents(&$components)
    {
        $vars = Engine_Vars::getVars();
        if ($fields = $vars['fields']) {
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
            } elseif (in_array($route, $dataquery_manager->getCacheableRoutes())) {
                if ($layouts = $vars['layouts']) {
                    $components[] = TranslationAPIFacade::getInstance()->__('layouts:', 'pop-engine').implode('.', $layouts);
                }
            }
        }

        return $components;
    }
}
