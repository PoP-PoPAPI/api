<?php
namespace PoP\API\TypeResolvers;

use PoP\API\TypeDataLoaders\SiteTypeDataLoader;
use PoP\Translation\Facades\TranslationAPIFacade;
use PoP\ComponentModel\TypeResolvers\AbstractTypeResolver;

class SiteTypeResolver extends AbstractTypeResolver
{
    public const NAME = 'Site';

    public function getTypeName(): string
    {
        return self::NAME;
    }

    public function getSchemaTypeDescription(): ?string
    {
        $translationAPI = TranslationAPIFacade::getInstance();
        return $translationAPI->__('Obtain properties belonging to the site (name, domain, configuration options, etc)', 'api');
    }

    public function getId($resultItem)
    {
        $site = $resultItem;
        return $site->getId();
    }

    public function getTypeDataLoaderClass(): string
    {
        return SiteTypeDataLoader::class;
    }
}

