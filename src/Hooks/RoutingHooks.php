<?php
namespace PoP\API\Hooks;
use PoP\Hooks\Facades\HooksAPIFacade;

class RoutingHooks
{
    public function __construct()
    {
        HooksAPIFacade::getInstance()->addFilter(
            '\PoP\Routing:uri-route',
            array($this, 'getURIRoute')
        );
    }

    public function getURIRoute($route)
    {
        $cmsengineapi = \PoP\Engine\FunctionAPIFactory::getInstance();
        $homeurl = $cmsengineapi->getHomeURL();
        return substr(\PoP\ComponentModel\Utils::getCurrentUrl(), strlen($homeurl));
    }
}
