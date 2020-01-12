<?php
namespace PoP\API\ObjectModels;

use PoP\API\ObjectFacades\SiteObjectFacade;

class Root
{
    public const ID = 'root';
    // private $id;
    private $site;
    public function __construct(/*$site = null*/)
    {
        // $this->id = 'root';
        // if (!is_null($site)) {
        //     $this->site = $site;
        // } else {
        //     $this->site = Instances\SiteInstance::getUniqueObjectInstance();
        // }
        $this->site = SiteObjectFacade::getInstance();
    }
    // public function getID() {
    //     return $this->id;
    // }
    public function getID() {
        return self::ID;
    }
    public function getSite() {
        return $this->site;
    }
}
