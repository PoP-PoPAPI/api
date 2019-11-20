<?php
namespace PoP\API\DirectiveResolvers;

use PoP\ComponentModel\DataloaderInterface;
use PoP\ComponentModel\Schema\SchemaDefinition;
use PoP\ComponentModel\Schema\TypeCastingHelpers;
use PoP\Translation\Facades\TranslationAPIFacade;
use PoP\ComponentModel\FieldResolvers\PipelinePositions;
use PoP\ComponentModel\FieldResolvers\FieldResolverInterface;
use PoP\ComponentModel\DirectiveResolvers\AbstractGlobalDirectiveResolver;

class SetPropertiesAsExpressionsDirectiveResolver extends AbstractGlobalDirectiveResolver
{
    const DIRECTIVE_NAME = 'setPropertiesAsExpressions';
    public static function getDirectiveName(): string {
        return self::DIRECTIVE_NAME;
    }

    /**
     * This directive must go after ResolveValueAndMerge
     *
     * @return void
     */
    public function getPipelinePosition(): string
    {
        return PipelinePositions::BACK;
    }

    /**
     * Can set several properties
     *
     * @return boolean
     */
    public function canExecuteMultipleTimesInField(): bool
    {
        return true;
    }

    public function getSchemaDirectiveDescription(FieldResolverInterface $fieldResolver): ?string
    {
        $translationAPI = TranslationAPIFacade::getInstance();
        return $translationAPI->__('Extract a property from the current object, and set it as a expression, so it can be accessed by fieldValueResolvers', 'component-model');
    }

    public function getSchemaDirectiveDeprecationDescription(FieldResolverInterface $fieldResolver): ?string
    {
        $translationAPI = TranslationAPIFacade::getInstance();
        return $translationAPI->__('Use directive `getSelfProp` together with field `extract` instead', 'component-model');
    }

    public function getSchemaDirectiveArgs(FieldResolverInterface $fieldResolver): array
    {
        $translationAPI = TranslationAPIFacade::getInstance();
        return [
            [
                SchemaDefinition::ARGNAME_NAME => 'properties',
                SchemaDefinition::ARGNAME_TYPE => TypeCastingHelpers::combineTypes(SchemaDefinition::TYPE_ARRAY, SchemaDefinition::TYPE_STRING),
                SchemaDefinition::ARGNAME_DESCRIPTION => $translationAPI->__('The property in the current object from which to copy the data into the expressions', 'component-model'),
                SchemaDefinition::ARGNAME_MANDATORY => true,
            ],
            [
                SchemaDefinition::ARGNAME_NAME => 'expressions',
                SchemaDefinition::ARGNAME_TYPE => TypeCastingHelpers::combineTypes(SchemaDefinition::TYPE_ARRAY, SchemaDefinition::TYPE_STRING),
                SchemaDefinition::ARGNAME_DESCRIPTION => $translationAPI->__('Name of the expressions. If not provided, the same name as the property is used', 'component-model'),
            ],
        ];
    }

    /**
     * Validate that the number of elements in the fields `properties` and `expressions` match one another
     *
     * @param FieldResolverInterface $fieldResolver
     * @param array $directiveArgs
     * @param array $schemaErrors
     * @param array $schemaWarnings
     * @param array $schemaDeprecations
     * @return array
     */
    public function validateDirectiveArgumentsForSchema(FieldResolverInterface $fieldResolver, array $directiveArgs, array &$schemaErrors, array &$schemaWarnings, array &$schemaDeprecations): array
    {
        $directiveArgs = parent::validateDirectiveArgumentsForSchema($fieldResolver, $directiveArgs, $schemaErrors, $schemaWarnings, $schemaDeprecations);
        $translationAPI = TranslationAPIFacade::getInstance();

        if (isset($directiveArgs['expressions'])) {
            $expressionsName = $directiveArgs['expressions'];
            $properties = $directiveArgs['properties'];
            $expressionsNameCount = count($expressionsName);
            $propertiesCount = count($properties);

            // Validate that both arrays have the same number of elements
            if ($expressionsNameCount > $propertiesCount) {
                $schemaWarnings[$this->directive][] = sprintf(
                    $translationAPI->__('Argument \'expressions\' has more elements than argument \'properties\', so the following expressions have been ignored: \'%s\'', 'component-model'),
                    implode($translationAPI->__('\', \''), array_slice($expressionsName, $propertiesCount))
                );
            } elseif ($expressionsNameCount < $propertiesCount) {
                $schemaWarnings[$this->directive][] = sprintf(
                    $translationAPI->__('Argument \'properties\' has more elements than argument \'expressions\', so the following properties will be assigned to the destination object under their same name: \'%s\'', 'component-model'),
                    implode($translationAPI->__('\', \''), array_slice($properties, $expressionsNameCount))
                );
            }
        }

        return $directiveArgs;
    }

    /**
     * Copy the data under the relational object into the current object
     *
     * @param FieldResolverInterface $fieldResolver
     * @param array $resultIDItems
     * @param array $idsDataFields
     * @param array $dbItems
     * @param array $dbErrors
     * @param array $dbWarnings
     * @param array $schemaErrors
     * @param array $schemaWarnings
     * @param array $schemaDeprecations
     * @return void
     */
    public function resolveDirective(DataloaderInterface $dataloader, FieldResolverInterface $fieldResolver, array &$idsDataFields, array &$succeedingPipelineIDsDataFields, array &$resultIDItems, array &$dbItems, array &$previousDBItems, array &$variables, array &$messages, array &$dbErrors, array &$dbWarnings, array &$schemaErrors, array &$schemaWarnings, array &$schemaDeprecations)
    {
        $translationAPI = TranslationAPIFacade::getInstance();
        // Send a message to the resolveAndMerge directive, indicating which properties to retrieve
        $properties = $this->directiveArgsForSchema['properties'];
        $expressionNames = $this->directiveArgsForSchema['expressions'] ?? $properties;
        $dbKey = $dataloader->getDatabaseKey();
        foreach (array_keys($idsDataFields) as $id) {
            for ($i=0; $i<count($properties); $i++) {
                // Validate that the property exists in the source object, either on this iteration or any previous one
                $property = $properties[$i];
                $isValueInDBItems = array_key_exists($property, $dbItems[(string)$id] ?? []);
                if (!$isValueInDBItems && !array_key_exists($property, $previousDBItems[$dbKey][(string)$id] ?? [])) {
                    $dbErrors[(string)$id][$this->directive][] = sprintf(
                        $translationAPI->__('Property \'%s\' hadn\'t been set for object with ID \'%s\', so no expression has been defined', 'component-model'),
                        $property,
                        $id
                    );
                    continue;
                }
                // Check if the value already exists
                $expressionName = $expressionNames[$i];
                $existingValue = $this->getExpressionForResultItem($id, $expressionName, $messages);
                if (!is_null($existingValue)) {
                    $dbWarnings[(string)$id][$this->directive][] = sprintf(
                        $translationAPI->__('The existing value for expression \'%s\' for object with ID \'%s\' has been overriden: \'%s\'', 'component-model'),
                        $expressionName,
                        $id
                    );
                }
                $value = $isValueInDBItems ? $dbItems[(string)$id][$property] : $previousDBItems[$dbKey][(string)$id][$property];
                $this->addExpressionForResultItem($id, $expressionName, $value, $messages);
            }
        }
    }
}
