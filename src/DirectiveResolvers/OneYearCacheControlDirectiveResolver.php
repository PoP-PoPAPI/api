<?php
namespace PoP\API\DirectiveResolvers;

use PoP\CacheControl\DirectiveResolvers\AbstractCacheControlDirectiveResolver;

class OneYearCacheControlDirectiveResolver extends AbstractCacheControlDirectiveResolver
{
    public static function getFieldNamesToApplyTo(): array
    {
        return [
            // operators and helpers...
            'extract',
        ];
    }

    public function getMaxAge(): ?int
    {
        // One year = 315360000 seconds
        return 315360000;
    }
}
