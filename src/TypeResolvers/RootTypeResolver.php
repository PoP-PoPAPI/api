<?php
namespace PoP\API\TypeResolvers;

use PoP\API\TypeDataLoaders\RootTypeDataLoader;
use PoP\Translation\Facades\TranslationAPIFacade;
use PoP\ComponentModel\TypeResolvers\AbstractTypeResolver;

class RootTypeResolver extends AbstractTypeResolver
{
    public const NAME = 'Root';

    public function getTypeName(): string
    {
        return self::NAME;
    }

    public function getSchemaTypeDescription(): ?string
    {
        $translationAPI = TranslationAPIFacade::getInstance();
        return $translationAPI->__('Root type, starting from which the query is executed', 'api');
    }

    public function getId($resultItem)
    {
        $root = $resultItem;
        return $root->getId();
    }

    public function getTypeDataLoaderClass(): string
    {
        return RootTypeDataLoader::class;
    }
}

