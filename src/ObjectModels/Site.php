<?php
namespace PoP\API\ObjectModels;

class Site
{
    private $id;
    private $domain;
    private $host;
    // private $name;
    // private $description;
    public function __construct(string $domain = ''/*, $name, $description = ''*/)
    {
        if (!$domain) {
            $cmsengineapi = \PoP\Engine\FunctionAPIFactory::getInstance();
            $domain = $cmsengineapi->getHomeURL();
        }
        $this->domain = $domain;
        $this->host = removeScheme($domain);
        $this->id = $this->host;
        // $this->name = $name;
        // $this->description = $description;
    }
    public function getId() {
        return $this->id;
    }
    public function getDomain() {
        return $this->domain;
    }
    public function getHost() {
        return $this->host;
    }
    // public function getName() {
    //     return $this->name;
    // }
    // public function getDescription() {
    //     return $this->description;
    // }
}
