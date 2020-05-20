<?php

declare(strict_types=1);

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
    public static $COMPONENT_DIR;
    use YAMLServicesTrait, CanDisableComponentTrait;
    // const VERSION = '0.1.0';

    public static function getDependedComponentClasses(): array
    {
        return [
            \PoP\Engine\Component::class,
        ];
    }

    /**
     * All conditional component classes that this component depends upon, to initialize them
     *
     * @return array
     */
    public static function getDependedConditionalComponentClasses(): array
    {
        return [
            \PoP\AccessControl\Component::class,
        ];
    }

    public static function getDependedMigrationPlugins(): array
    {
        return [
            'migrate-api',
        ];
    }

    /**
     * Initialize services
     */
    protected static function doInitialize()
    {
        if (self::isEnabled()) {
            parent::doInitialize();
            self::$COMPONENT_DIR = dirname(__DIR__);
            self::initYAMLServices(self::$COMPONENT_DIR);
            ServiceConfiguration::init();

            if (class_exists('\PoP\AccessControl\Component')) {
                \PoP\API\Conditional\AccessControl\ConditionalComponent::init();
            }
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
    public static function beforeBoot()
    {
        parent::beforeBoot();

        // Initialize classes
        ContainerBuilderUtils::instantiateNamespaceServices(__NAMESPACE__ . '\\Hooks');
        ContainerBuilderUtils::attachFieldResolversFromNamespace(__NAMESPACE__ . '\\FieldResolvers');
        ContainerBuilderUtils::attachAndRegisterDirectiveResolversFromNamespace(__NAMESPACE__ . '\\DirectiveResolvers', false);

        // Boot conditional on API package being installed
        if (class_exists('\PoP\AccessControl\Component')) {
            \PoP\API\Conditional\AccessControl\ConditionalComponent::beforeBoot();
        }
    }
}
