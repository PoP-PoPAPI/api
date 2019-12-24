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
                    [
                        SchemaDefinition::ARGNAME_NAME => 'readable',
                        SchemaDefinition::ARGNAME_TYPE => SchemaDefinition::TYPE_BOOL,
                        SchemaDefinition::ARGNAME_DESCRIPTION => $translationAPI->__('Make the output readable for humans (it doesn\'t follow spec, then it is not understood by GraphiQL)', ''),
                        SchemaDefinition::ARGNAME_DEFAULT_VALUE => 'false',
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
                    'compressed' => isset($fieldArgs['compressed']) ? $fieldArgs['compressed'] : true,
                    'shape' => isset($fieldArgs['shape']) && in_array(strtolower($fieldArgs['shape']), $this->getSchemaFieldShapeValues()) ? strtolower($fieldArgs['shape']) : SchemaDefinition::ARGVALUE_SCHEMA_SHAPE_FLAT,
                    'typeAsSDL' => isset($fieldArgs['typeAsSDL']) ? $fieldArgs['typeAsSDL'] : true,
                    'readable' => isset($fieldArgs['readable']) ? $fieldArgs['readable'] : false,
                ];
                // If it is flat shape, all types will be added under $generalMessages
                $isFlatShape = $options['shape'] == SchemaDefinition::ARGVALUE_SCHEMA_SHAPE_FLAT;
                if ($isFlatShape) {
                    $generalMessages[SchemaDefinition::ARGNAME_TYPES] = [];
                }
                $typeSchemaDefinition = $typeResolver->getSchemaDefinition($stackMessages, $generalMessages, $options);
                $schemaDefinition[SchemaDefinition::ARGNAME_TYPES] =
                    $options['readable'] ?
                        $typeSchemaDefinition :
                        array_values($typeSchemaDefinition);

                // Add the queryType
                $schemaDefinition[SchemaDefinition::ARGNAME_QUERY_TYPE] = $rootTypeName;

                // Move from under Root type to the top: globalDirectives and globalFields (renamed as "functions")
                $schemaDefinition[SchemaDefinition::ARGNAME_GLOBAL_FIELDS] =
                    $options['readable'] ?
                        $typeSchemaDefinition[$rootTypeName][SchemaDefinition::ARGNAME_GLOBAL_FIELDS] :
                        array_values($typeSchemaDefinition[$rootTypeName][SchemaDefinition::ARGNAME_GLOBAL_FIELDS]);
                $schemaDefinition[SchemaDefinition::ARGNAME_GLOBAL_CONNECTIONS] =
                    $options['readable'] ?
                        $typeSchemaDefinition[$rootTypeName][SchemaDefinition::ARGNAME_GLOBAL_CONNECTIONS] :
                        array_values($typeSchemaDefinition[$rootTypeName][SchemaDefinition::ARGNAME_GLOBAL_CONNECTIONS]);
                $schemaDefinition[SchemaDefinition::ARGNAME_GLOBAL_DIRECTIVES] =
                    $options['readable'] ?
                        $typeSchemaDefinition[$rootTypeName][SchemaDefinition::ARGNAME_GLOBAL_DIRECTIVES] :
                        array_values($typeSchemaDefinition[$rootTypeName][SchemaDefinition::ARGNAME_GLOBAL_DIRECTIVES]);
                unset($schemaDefinition[SchemaDefinition::ARGNAME_TYPES][$rootTypeName][SchemaDefinition::ARGNAME_GLOBAL_FIELDS]);
                unset($schemaDefinition[SchemaDefinition::ARGNAME_TYPES][$rootTypeName][SchemaDefinition::ARGNAME_GLOBAL_CONNECTIONS]);
                unset($schemaDefinition[SchemaDefinition::ARGNAME_TYPES][$rootTypeName][SchemaDefinition::ARGNAME_GLOBAL_DIRECTIVES]);

                // Retrieve the list of all types from under $generalMessages
                if ($isFlatShape) {
                    $typeFlatList = $generalMessages[SchemaDefinition::ARGNAME_TYPES];

                    // Remove the globals from the Root
                    unset($typeFlatList[$rootTypeName][SchemaDefinition::ARGNAME_GLOBAL_FIELDS]);
                    unset($typeFlatList[$rootTypeName][SchemaDefinition::ARGNAME_GLOBAL_CONNECTIONS]);
                    unset($typeFlatList[$rootTypeName][SchemaDefinition::ARGNAME_GLOBAL_DIRECTIVES]);

                    // Because they were added in reverse way, reverse it once again, so that the first types (eg: Root) appear first
                    $schemaDefinition[SchemaDefinition::ARGNAME_TYPES] = array_reverse($typeFlatList);
                }

                // Add the Fragment Catalogue
                $fragmentCatalogueManager = PersistedFragmentManagerFacade::getInstance();
                $persistedFragments = $fragmentCatalogueManager->getPersistedFragmentsForSchema();
                $schemaDefinition[SchemaDefinition::ARGNAME_PERSISTED_FRAGMENTS] =
                    $options['readable'] ?
                        $persistedFragments :
                        array_values($persistedFragments);

                // Remove the arg name for as the key to all args in the JSON
                if (!$options['readable']) {
                    // TODO: Complete!
                }

                return $schemaDefinition;
            case 'site':
                return $root->getSite()->getID();
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
