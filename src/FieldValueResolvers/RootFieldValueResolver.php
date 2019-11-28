<?php
namespace PoP\API\FieldValueResolvers;

use PoP\API\Facades\PersistedFragmentManagerFacade;
use PoP\Translation\Facades\TranslationAPIFacade;
use PoP\API\Schema\SchemaDefinition;
use PoP\ComponentModel\FieldValueResolvers\AbstractDBDataFieldValueResolver;
use PoP\ComponentModel\FieldResolvers\FieldResolverInterface;
use PoP\API\FieldResolvers\RootFieldResolver;

class RootFieldValueResolver extends AbstractDBDataFieldValueResolver
{
    public static function getClassesToAttachTo(): array
    {
        return array(RootFieldResolver::class);
    }

    public static function getFieldNamesToResolve(): array
    {
        return [
            '__schema',
            'site',
        ];
    }

    public function getSchemaFieldType(FieldResolverInterface $fieldResolver, string $fieldName): ?string
    {
        $types = [
            '__schema' => SchemaDefinition::TYPE_OBJECT,
            'site' => SchemaDefinition::TYPE_ID,
        ];
        return $types[$fieldName] ?? parent::getSchemaFieldType($fieldResolver, $fieldName);
    }

    public function getSchemaFieldDescription(FieldResolverInterface $fieldResolver, string $fieldName): ?string
    {
        $translationAPI = TranslationAPIFacade::getInstance();
        $descriptions = [
            '__schema' => $translationAPI->__('The whole API schema, exposing what fields can be queried', ''),
            'site' => $translationAPI->__('This website', ''),
        ];
        return $descriptions[$fieldName] ?? parent::getSchemaFieldDescription($fieldResolver, $fieldName);
    }

    public function getSchemaFieldArgs(FieldResolverInterface $fieldResolver, string $fieldName): array
    {
        $translationAPI = TranslationAPIFacade::getInstance();
        switch ($fieldName) {
            case '__schema':
                return [
                    [
                        'name' => 'deep',
                        'type' => SchemaDefinition::TYPE_BOOL,
                        'description' => $translationAPI->__('Make a deep introspection of the fields, for all nested objects. Default is \'true\'', ''),
                    ],
                ];
        }

        return parent::getSchemaFieldArgs($fieldResolver, $fieldName);
    }

    public function resolveValue(FieldResolverInterface $fieldResolver, $resultItem, string $fieldName, array $fieldArgs = [], ?array $variables = null, ?array $expressions = null, array $options = [])
    {
        $root = $resultItem;
        switch ($fieldName) {
            case '__schema':
                $options = [
                    'processed' => [],
                    'is-root' => true,
                ];
                // Normalize properties in $fieldArgs with their defaults
                // By default make it deep. To avoid it, must pass argument (deep:false)
                $fieldArgs['deep'] = isset($fieldArgs['deep']) ? strtolower($fieldArgs['deep']) === "true" : true;
                $schemaDefinition = $fieldResolver->getSchemaDefinition($fieldArgs, $options);

                // Add the Fragment Catalogue
                $fragmentCatalogueManager = PersistedFragmentManagerFacade::getInstance();
                $schemaDefinition[SchemaDefinition::ARGNAME_PERSISTED_FRAGMENTS] = $fragmentCatalogueManager->getPersistedFragmentsForSchema();
                return $schemaDefinition;
            case 'site':
                return $root->getSite()->getId();
        }

        return parent::resolveValue($fieldResolver, $resultItem, $fieldName, $fieldArgs, $variables, $expressions, $options);
    }

    public function resolveFieldDefaultDataloaderClass(FieldResolverInterface $fieldResolver, string $fieldName, array $fieldArgs = []): ?string
    {
        switch ($fieldName) {
            case 'site':
                return \PoP\API\Dataloader_Sites::class;
        }

        return parent::resolveFieldDefaultDataloaderClass($fieldResolver, $fieldName, $fieldArgs);
    }
}
