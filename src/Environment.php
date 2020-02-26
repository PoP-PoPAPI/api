<?php
namespace PoP\API;

class Environment
{
    public static function disableAPI(): bool
    {
        return isset($_ENV['DISABLE_API']) ? strtolower($_ENV['DISABLE_API']) == "true" : false;
    }
}

