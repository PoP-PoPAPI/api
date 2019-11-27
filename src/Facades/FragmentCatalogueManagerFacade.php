<?php
namespace PoP\API\Facades;

use PoP\API\FragmentCatalogue\FragmentCatalogueManagerInterface;
use PoP\Root\Container\ContainerBuilderFactory;

class FragmentCatalogueManagerFacade
{
    public static function getInstance(): FragmentCatalogueManagerInterface
    {
        return ContainerBuilderFactory::getInstance()->get('fragment_catalogue_manager');
    }
}
