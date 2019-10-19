<?php
namespace PoP\API\Hooks;
use PoP\Hooks\Hooks\AbstractHook;
use PoP\Hooks\Contracts\HooksAPIInterface;

class RoutingHooks extends AbstractHook
{
    public function __construct(HooksAPIInterface $hooksAPI)
    {
        parent::__construct($hooksAPI);
        $this->hooksAPI->addFilter(
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
