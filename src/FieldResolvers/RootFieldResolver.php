<?php
namespace PoP\API\FieldResolvers;

use PoP\API\Facades\PersistedFragmentManagerFacade;
use PoP\Translation\Facades\TranslationAPIFacade;
use PoP\API\Schema\SchemaDefinition;
use PoP\ComponentModel\FieldResolvers\AbstractDBDataFieldResolver;
use PoP\ComponentModel\TypeResolvers\TypeResolverInterface;
use PoP\API\TypeResolvers\RootTypeResolver;

class RootFieldResolver extends AbstractDBDataFieldResolver
{
    public static function getClassesToAttachTo(): array
    {
        return array(RootTypeResolver::class);
    }

    public static function getFieldNamesToResolve(): array
    {
        return [
            '__schema',
            'site',
        ];
    }

    public function getSchemaFieldType(TypeResolverInterface $typeResolver, string $fieldName): ?string
    {
        $types = [
            '__schema' => SchemaDefinition::TYPE_OBJECT,
            'site' => SchemaDefinition::TYPE_ID,
        ];
        return $types[$fieldName] ?? parent::getSchemaFieldType($typeResolver, $fieldName);
    }

    public function getSchemaFieldDescription(TypeResolverInterface $typeResolver, string $fieldName): ?string
    {
        $translationAPI = TranslationAPIFacade::getInstance();
        $descriptions = [
            '__schema' => $translationAPI->__('The whole API schema, exposing what fields can be queried', ''),
            'site' => $translationAPI->__('This website', ''),
        ];
        return $descriptions[$fieldName] ?? parent::getSchemaFieldDescription($typeResolver, $fieldName);
    }

    public function getSchemaFieldArgs(TypeResolverInterface $typeResolver, string $fieldName): array
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

        return parent::getSchemaFieldArgs($typeResolver, $fieldName);
    }

    public function resolveValue(TypeResolverInterface $typeResolver, $resultItem, string $fieldName, array $fieldArgs = [], ?array $variables = null, ?array $expressions = null, array $options = [])
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
                $schemaDefinition = $typeResolver->getSchemaDefinition($fieldArgs, $options);

                // Add the Fragment Catalogue
                $fragmentCatalogueManager = PersistedFragmentManagerFacade::getInstance();
                $schemaDefinition[SchemaDefinition::ARGNAME_PERSISTED_FRAGMENTS] = $fragmentCatalogueManager->getPersistedFragmentsForSchema();
                return $schemaDefinition;
            case 'site':
                return $root->getSite()->getId();
        }

        return parent::resolveValue($typeResolver, $resultItem, $fieldName, $fieldArgs, $variables, $expressions, $options);
    }

    public function resolveFieldDefaultDataloaderClass(TypeResolverInterface $typeResolver, string $fieldName, array $fieldArgs = []): ?string
    {
        switch ($fieldName) {
            case 'site':
                return \PoP\API\Dataloader_Sites::class;
        }

        return parent::resolveFieldDefaultDataloaderClass($typeResolver, $fieldName, $fieldArgs);
    }
}
