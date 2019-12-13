<?php
namespace PoP\API\FieldResolvers;

use PoP\API\Facades\PersistedFragmentManagerFacade;
use PoP\Translation\Facades\TranslationAPIFacade;
use PoP\API\Schema\SchemaDefinition;
use PoP\ComponentModel\FieldResolvers\AbstractDBDataFieldResolver;
use PoP\ComponentModel\TypeResolvers\TypeResolverInterface;
use PoP\API\TypeResolvers\RootTypeResolver;
use PoP\API\TypeResolvers\SiteTypeResolver;

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

    protected function getSchemaFieldShapeValues() {
        return [
            SchemaDefinition::ARGVALUE_SCHEMA_SHAPE_FLAT,
            SchemaDefinition::ARGVALUE_SCHEMA_SHAPE_NESTED,
        ];
    }

    public function getSchemaFieldArgs(TypeResolverInterface $typeResolver, string $fieldName): array
    {
        $translationAPI = TranslationAPIFacade::getInstance();
        switch ($fieldName) {
            case '__schema':
                return [
                    [
                        SchemaDefinition::ARGNAME_NAME => 'deep',
                        SchemaDefinition::ARGNAME_TYPE => SchemaDefinition::TYPE_BOOL,
                        SchemaDefinition::ARGNAME_DESCRIPTION => $translationAPI->__('Make a deep introspection of the fields, for all nested objects', ''),
                        SchemaDefinition::ARGNAME_DEFAULT_VALUE => 'true',
                    ],
                    [
                        SchemaDefinition::ARGNAME_NAME => 'shape',
                        SchemaDefinition::ARGNAME_TYPE => SchemaDefinition::TYPE_ENUM,
                        SchemaDefinition::ARGNAME_DESCRIPTION => sprintf(
                            $translationAPI->__('How to shape the schema output: \'%s\', in which case all types are listed together, or \'%s\', in which the types are listed following where they appear in the graph', ''),
                            SchemaDefinition::ARGVALUE_SCHEMA_SHAPE_FLAT,
                            SchemaDefinition::ARGVALUE_SCHEMA_SHAPE_NESTED
                        ),
                        SchemaDefinition::ARGNAME_ENUMVALUES => $this->getSchemaFieldShapeValues(),
                        SchemaDefinition::ARGNAME_DEFAULT_VALUE => SchemaDefinition::ARGVALUE_SCHEMA_SHAPE_FLAT,

                    ],
                    [
                        SchemaDefinition::ARGNAME_NAME => 'compressed',
                        SchemaDefinition::ARGNAME_TYPE => SchemaDefinition::TYPE_BOOL,
                        SchemaDefinition::ARGNAME_DESCRIPTION => $translationAPI->__('Output each resolver\'s schema data only once to compress the output. Valid only when field \'deep\' is `true`', ''),
                        SchemaDefinition::ARGNAME_DEFAULT_VALUE => 'false',
                    ],
                    [
                        SchemaDefinition::ARGNAME_NAME => 'typeAsSDL',
                        SchemaDefinition::ARGNAME_TYPE => SchemaDefinition::TYPE_BOOL,
                        SchemaDefinition::ARGNAME_DESCRIPTION => $translationAPI->__('Output the type using the GraphQL SDL notation (eg: \'[Post]\' instead of \'array:id\')', ''),
                        SchemaDefinition::ARGNAME_DEFAULT_VALUE => 'true',
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
                $stackMessages = [
                    'processed' => [],
                    'is-root' => true,
                ];
                $generalMessages = [
                    'processed' => [],
                ];
                $rootTypeName = $typeResolver->getTypeName();
                // Normalize properties in $fieldArgs with their defaults
                // By default make it deep. To avoid it, must pass argument (deep:false)
                // By default, use the "flat" shape
                $options = [
                    'deep' => isset($fieldArgs['deep']) ? $fieldArgs['deep'] : true,
                    'compressed' => isset($fieldArgs['compressed']) ? $fieldArgs['compressed'] : false,
                    'shape' => isset($fieldArgs['shape']) && in_array(strtolower($fieldArgs['shape']), $this->getSchemaFieldShapeValues()) ? strtolower($fieldArgs['shape']) : SchemaDefinition::ARGVALUE_SCHEMA_SHAPE_FLAT,
                    'typeAsSDL' => isset($fieldArgs['typeAsSDL']) ? $fieldArgs['typeAsSDL'] : true,
                ];
                $schemaDefinition[SchemaDefinition::ARGNAME_TYPES] = $typeResolver->getSchemaDefinition($stackMessages, $generalMessages, $options);

                // Move from under Root type to the top: globalDirectives and operatorsAndHelpers
                $schemaDefinition[SchemaDefinition::ARGNAME_FUNCTIONS] = $schemaDefinition[SchemaDefinition::ARGNAME_TYPES][$rootTypeName][SchemaDefinition::ARGNAME_FUNCTIONS];
                unset($schemaDefinition[SchemaDefinition::ARGNAME_TYPES][$rootTypeName][SchemaDefinition::ARGNAME_FUNCTIONS]);
                $schemaDefinition[SchemaDefinition::ARGNAME_HELPERS] = $schemaDefinition[SchemaDefinition::ARGNAME_TYPES][$rootTypeName][SchemaDefinition::ARGNAME_HELPERS];
                unset($schemaDefinition[SchemaDefinition::ARGNAME_TYPES][$rootTypeName][SchemaDefinition::ARGNAME_HELPERS]);
                $schemaDefinition[SchemaDefinition::ARGNAME_DIRECTIVES] = $schemaDefinition[SchemaDefinition::ARGNAME_TYPES][$rootTypeName][SchemaDefinition::ARGNAME_GLOBAL_DIRECTIVES];
                unset($schemaDefinition[SchemaDefinition::ARGNAME_TYPES][$rootTypeName][SchemaDefinition::ARGNAME_GLOBAL_DIRECTIVES]);

                // Add the Fragment Catalogue
                $fragmentCatalogueManager = PersistedFragmentManagerFacade::getInstance();
                $schemaDefinition[SchemaDefinition::ARGNAME_PERSISTED_FRAGMENTS] = $fragmentCatalogueManager->getPersistedFragmentsForSchema();
                return $schemaDefinition;
            case 'site':
                return $root->getSite()->getId();
        }

        return parent::resolveValue($typeResolver, $resultItem, $fieldName, $fieldArgs, $variables, $expressions, $options);
    }

    public function resolveFieldTypeResolverClass(TypeResolverInterface $typeResolver, string $fieldName, array $fieldArgs = []): ?string
    {
        switch ($fieldName) {
            case 'site':
                return SiteTypeResolver::class;
        }

        return parent::resolveFieldTypeResolverClass($typeResolver, $fieldName, $fieldArgs);
    }
}
