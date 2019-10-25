<?php
namespace PoP\API;

use PoP\API\Config\ServiceConfiguration;
use PoP\Root\Component\AbstractComponent;
use PoP\Root\Component\YAMLServicesTrait;
use PoP\ComponentModel\Container\ContainerBuilderUtils;

/**
 * Initialize component
 */
class Component extends AbstractComponent
{
    use YAMLServicesTrait;
    // const VERSION = '0.1.0';
    public static $enabled;

    /**
     * Initialize services
     */
    public static function init()
    {
        parent::init();
        self::$enabled = !Environment::disableAPI();
        if (self::$enabled) {
            self::initYAMLServices(dirname(__DIR__));
            ServiceConfiguration::init();
        }
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
        if (self::$enabled) {
            ContainerBuilderUtils::instantiateNamespaceServices(__NAMESPACE__.'\\Hooks');
            ContainerBuilderUtils::attachDirectiveResolversFromNamespace(__NAMESPACE__.'\\DirectiveResolvers');
        }
    }
}
