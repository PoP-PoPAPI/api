<?php
namespace PoP\API\DirectiveResolvers;

use PoP\ComponentModel\DataloaderInterface;
use PoP\ComponentModel\FieldResolvers\FieldResolverInterface;
use PoP\CacheControl\DirectiveResolvers\AbstractCacheControlDirectiveResolver;

class NoCacheCacheControlDirectiveResolver extends AbstractCacheControlDirectiveResolver
{
    public static function getFieldNamesToApplyTo(): array
    {
        return [
            'getJSON',
        ];
    }

    public function getMaxAge(): int
    {
        // Do not cache
        return 0;
    }
}
