<?php

declare(strict_types=1);

namespace PoP\API\Cache;

use PoP\ComponentModel\State\ApplicationState;
use PoP\ComponentModel\Configuration\Request;

class CacheUtils
{
    public static function getSchemaCacheKeyComponents(): array
    {
        $vars = ApplicationState::getVars();
        return [
            'namespaced' => $vars['namespace-types-and-interfaces'],
            'version-constraint' => Request::getVersionConstraint() ?? '',
            'field-version-constraints' => Request::getVersionConstraintsForFields() ?? [],
            'directive-version-constraints' => Request::getVersionConstraintsForDirectives() ?? [],
        ];
    }
}
