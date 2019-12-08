<?php
namespace PoP\API\ObjectFacades;

use PoP\API\ObjectModels\Site;
use PoP\Root\Container\ContainerBuilderFactory;

class SiteObjectFacade
{
    public static function getInstance(): Site
    {
        $containerBuilderFactory = ContainerBuilderFactory::getInstance();
        return $containerBuilderFactory->get('site_object');
    }
}
