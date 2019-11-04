<?php
namespace PoP\API\FieldValueResolvers;

use PoP\Translation\Facades\TranslationAPIFacade;
use PoP\ComponentModel\Schema\SchemaDefinition;
use PoP\ComponentModel\FieldValueResolvers\AbstractDBDataFieldValueResolver;
use PoP\ComponentModel\FieldResolvers\FieldResolverInterface;
use PoP\API\FieldResolvers\SiteFieldResolver;

class SiteFieldValueResolver extends AbstractDBDataFieldValueResolver
{
    public static function getClassesToAttachTo(): array
    {
        return array(SiteFieldResolver::class);
    }

    public static function getFieldNamesToResolve(): array
    {
        return [
            'domain',
            'host',
        ];
    }

    public function getSchemaFieldType(FieldResolverInterface $fieldResolver, string $fieldName): ?string
    {
        $types = [
            'domain' => SchemaDefinition::TYPE_STRING,
            'host' => SchemaDefinition::TYPE_STRING,
        ];
        return $types[$fieldName] ?? parent::getSchemaFieldType($fieldResolver, $fieldName);
    }

    public function getSchemaFieldDescription(FieldResolverInterface $fieldResolver, string $fieldName): ?string
    {
        $translationAPI = TranslationAPIFacade::getInstance();
        $descriptions = [
            'domain' => $translationAPI->__('The site\'s domain', ''),
            'host' => $translationAPI->__('The site\'s host', ''),
        ];
        return $descriptions[$fieldName] ?? parent::getSchemaFieldDescription($fieldResolver, $fieldName);
    }

    public function resolveValue(FieldResolverInterface $fieldResolver, $resultItem, string $fieldName, array $fieldArgs = [])
    {
        $site = $resultItem;
        switch ($fieldName) {
            case 'domain':
                return $site->getDomain();
            case 'host':
                return $site->getHost();
        }

        return parent::resolveValue($fieldResolver, $resultItem, $fieldName, $fieldArgs);
    }
}
