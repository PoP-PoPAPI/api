<?php

declare(strict_types=1);

namespace PoP\API\Configuration;

class Request
{
    public const URLPARAM_USE_NAMESPACE = 'use_namespace';

    public static function namespaceTypesAndInterfaces(): bool
    {
        return isset($_REQUEST[self::URLPARAM_USE_NAMESPACE]) && $_REQUEST[self::URLPARAM_USE_NAMESPACE];
    }
}
