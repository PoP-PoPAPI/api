<?php

declare(strict_types=1);

namespace PoP\API\Cache;

use PoP\ComponentModel\State\ApplicationState;

class CacheUtils
{
    public static function getSchemaCacheKeyComponents(): array
    {
        $vars = ApplicationState::getVars();
        return [
            'namespaced' => $vars['namespace-types-and-interfaces'],
            'version-constraint' => $vars['version-constraint'],
            'field-version-constraints' => $vars['field-version-constraints'],
            'directive-version-constraints' => $vars['directive-version-constraints'],
        ];
    }
}
