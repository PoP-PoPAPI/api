<?php
namespace PoP\API;

use PoP\API\Config\ServiceConfiguration;
use PoP\Root\Component\AbstractComponent;
use PoP\Root\Component\CanDisableComponentTrait;
use PoP\Root\Component\YAMLServicesTrait;
use PoP\ComponentModel\Container\ContainerBuilderUtils;

/**
 * Initialize component
 */
class Component extends AbstractComponent
{
    use YAMLServicesTrait, CanDisableComponentTrait;
    // const VERSION = '0.1.0';

    /**
     * Initialize services
     */
    public static function init()
    {
        if (self::isEnabled()) {
            parent::init();
            self::initYAMLServices(dirname(__DIR__));
            ServiceConfiguration::init();
        }
    }

    protected static function resolveEnabled()
    {
        return !Environment::disableAPI();
    }

    /**
     * Boot component
     *
     * @return void
     */
    public static function boot()
    {
        parent::boot();

        // Initialize classes
        ContainerBuilderUtils::instantiateNamespaceServices(__NAMESPACE__.'\\Hooks');
        ContainerBuilderUtils::attachFieldValueResolversFromNamespace(__NAMESPACE__.'\\FieldValueResolvers');
        ContainerBuilderUtils::attachDirectiveResolversFromNamespace(__NAMESPACE__.'\\DirectiveResolvers', false);
    }
}
