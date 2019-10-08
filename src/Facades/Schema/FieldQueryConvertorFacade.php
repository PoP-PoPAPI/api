<?php
namespace PoP\API\Facades\Schema;

use PoP\API\Schema\FieldQueryConvertorInterface;
use PoP\Root\Container\ContainerBuilderFactory;

class FieldQueryConvertorFacade
{
    public static function getInstance(): FieldQueryConvertorInterface
    {
        return ContainerBuilderFactory::getInstance()->get('field_query_convertor');
    }
}
