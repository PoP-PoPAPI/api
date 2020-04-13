<?php

declare(strict_types=1);

namespace PoP\API\Schema;

use PoP\API\Facades\FieldQueryConvertorFacade;

class FieldQueryConvertorUtils
{
    public static function getQueryAsArray($query)
    {
        if (is_string($query)) {
            $fieldQueryConvertor = FieldQueryConvertorFacade::getInstance();
            $query = $fieldQueryConvertor->convertAPIQuery($query);
        }
        return $query;
    }
}
