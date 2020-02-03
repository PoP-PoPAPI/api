<?php
namespace PoP\API\Configuration;

class Request
{
    const URLPARAM_USE_NAMESPACE = 'use_namespace';
    public static function namespaceTypesAndInterfaces()
    {
        return isset($_REQUEST[self::URLPARAM_USE_NAMESPACE]) && $_REQUEST[self::URLPARAM_USE_NAMESPACE];
    }
}

