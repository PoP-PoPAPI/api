<?php

declare(strict_types=1);

namespace PoP\API\Hooks;

use Exception;
use PoP\Engine\Hooks\AbstractHookSet;
use PoP\ComponentModel\Misc\RequestUtils;

class RoutingHooks extends AbstractHookSet
{
    protected function init()
    {
        $this->hooksAPI->addFilter(
            '\PoP\Routing:uri-route',
            array($this, 'getURIRoute')
        );
    }

    public function getURIRoute(string $route): string
    {
        $cmsengineapi = \PoP\Engine\FunctionAPIFactory::getInstance();
        $homeURL = $cmsengineapi->getHomeURL();
        $currentURL = RequestUtils::getCurrentUrl();
        // If the homeURL is not contained in the current URL
        // then there's a misconfiguration in the server
        if (substr($currentURL, 0, strlen($homeURL)) != $homeURL) {
            throw new Exception(sprintf(
                'The webserver is not configured properly, since the current URL \'%s\' does not contain the home URL \'%s\' (possibly the server name has not been set-up correctly)',
                $currentURL,
                $homeURL
            ));
        }
        return substr($currentURL, strlen($homeURL));
    }
}
