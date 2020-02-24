<?php
namespace PoP\API\Hooks;

use PoP\API\Environment;

abstract class AbstractMaybeDisableFieldsInPrivateSchemaHookSet extends AbstractMaybeDisableFieldsHookSet
{
    /**
     * Indicate if this hook is enabled
     *
     * @return boolean
     */
    protected function enabled(): bool
    {
        return Environment::usePrivateSchemaMode();
    }
}
