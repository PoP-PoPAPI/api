<?php

declare(strict_types=1);

namespace PoP\API\Conditional\AccessControl\Hooks;

use PoP\API\Environment;
use PoP\AccessControl\Environment as AccessControlEnvironment;
use PoP\API\ComponentConfiguration;
use PoP\Engine\Hooks\AbstractHookSet;
use PoP\ComponentModel\AbstractComponentConfiguration;

class ComponentConfigurationHookSet extends AbstractHookSet
{
    protected function init()
    {
        /**
         * Do not enable caching when doing a private schema mode
         */
        if (AccessControlEnvironment::enableIndividualControlForPublicPrivateSchemaMode() ||
            AccessControlEnvironment::usePrivateSchemaMode()
        ) {
            $hookName = AbstractComponentConfiguration::getHookName(
                ComponentConfiguration::class,
                Environment::USE_SCHEMA_DEFINITION_CACHE
            );
            $this->hooksAPI->addFilter(
                $hookName,
                function () {
                    return false;
                }
            );
        }
    }
}
