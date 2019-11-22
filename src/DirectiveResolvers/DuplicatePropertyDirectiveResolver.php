<?php
namespace PoP\API\DirectiveResolvers;

use PoP\ComponentModel\DataloaderInterface;
use PoP\ComponentModel\Schema\SchemaDefinition;
use PoP\Translation\Facades\TranslationAPIFacade;
use PoP\ComponentModel\FieldResolvers\FieldResolverInterface;
use PoP\ComponentModel\Facades\Schema\FieldQueryInterpreterFacade;
use PoP\ComponentModel\DirectiveResolvers\AbstractGlobalDirectiveResolver;

class DuplicatePropertyDirectiveResolver extends AbstractGlobalDirectiveResolver
{
    const DIRECTIVE_NAME = 'duplicateProperty';
    public static function getDirectiveName(): string {
        return self::DIRECTIVE_NAME;
    }

    public function getSchemaDirectiveDescription(FieldResolverInterface $fieldResolver): ?string
    {
        $translationAPI = TranslationAPIFacade::getInstance();
        return $translationAPI->__('Duplicate a property in the current object', 'component-model');
    }

    public function getSchemaDirectiveArgs(FieldResolverInterface $fieldResolver): array
    {
        $translationAPI = TranslationAPIFacade::getInstance();
        return [
            [
                SchemaDefinition::ARGNAME_NAME => 'to',
                SchemaDefinition::ARGNAME_TYPE => SchemaDefinition::TYPE_STRING,
                SchemaDefinition::ARGNAME_DESCRIPTION => $translationAPI->__('The new property name', 'component-model'),
                SchemaDefinition::ARGNAME_MANDATORY => true,
            ],
        ];
    }

    /**
     * Duplicate a property from the current object
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
        $fieldQueryInterpreter = FieldQueryInterpreterFacade::getInstance();
        $copyTo = $this->directiveArgsForSchema['to'];
        foreach ($idsDataFields as $id => $dataFields) {
            foreach ($dataFields['direct'] as $field) {
                $fieldOutputKey = $fieldQueryInterpreter->getFieldOutputKey($field);
                if (!array_key_exists($fieldOutputKey, $dbItems[(string)$id])) {
                    $dbWarnings[(string)$id][$this->directive][] = sprintf(
                        $translationAPI->__('Property \'%s\' doesn\'t exist in object with ID \'%s\', so it can\'t be copied to \'%s\''),
                        $fieldOutputKey,
                        $id,
                        $copyTo
                    );
                    continue;
                }
                $dbItems[(string)$id][$copyTo] = $dbItems[(string)$id][$fieldOutputKey];
            }
        }
    }
}
