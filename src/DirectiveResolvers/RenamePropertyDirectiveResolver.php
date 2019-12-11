<?php
namespace PoP\API\DirectiveResolvers;

use PoP\ComponentModel\TypeDataLoaders\TypeDataLoaderInterface;
use PoP\Translation\Facades\TranslationAPIFacade;
use PoP\ComponentModel\TypeResolvers\TypeResolverInterface;
use PoP\ComponentModel\Facades\Schema\FieldQueryInterpreterFacade;

class RenamePropertyDirectiveResolver extends DuplicatePropertyDirectiveResolver
{
    const DIRECTIVE_NAME = 'renameProperty';
    public static function getDirectiveName(): string {
        return self::DIRECTIVE_NAME;
    }

    public function getSchemaDirectiveDescription(TypeResolverInterface $typeResolver): ?string
    {
        $translationAPI = TranslationAPIFacade::getInstance();
        return $translationAPI->__('Rename a property in the current object', 'component-model');
    }

    /**
     * Rename a property from the current object
     *
     * @param TypeResolverInterface $typeResolver
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
    public function resolveDirective(TypeDataLoaderInterface $typeDataResolver, TypeResolverInterface $typeResolver, array &$idsDataFields, array &$succeedingPipelineIDsDataFields, array &$resultIDItems, array &$convertibleDBKeyIDs, array &$dbItems, array &$previousDBItems, array &$variables, array &$messages, array &$dbErrors, array &$dbWarnings, array &$schemaErrors, array &$schemaWarnings, array &$schemaDeprecations)
    {
        // After duplicating the property, delete the original
        parent::resolveDirective($typeDataResolver, $typeResolver, $idsDataFields, $succeedingPipelineIDsDataFields, $resultIDItems, $convertibleDBKeyIDs, $dbItems, $previousDBItems, $variables, $messages, $dbErrors, $dbWarnings, $schemaErrors, $schemaWarnings, $schemaDeprecations);
        $fieldQueryInterpreter = FieldQueryInterpreterFacade::getInstance();
        foreach ($idsDataFields as $id => $dataFields) {
            foreach ($dataFields['direct'] as $field) {
                $fieldOutputKey = $fieldQueryInterpreter->getFieldOutputKey($field);
                unset($dbItems[(string)$id][$fieldOutputKey]);
            }
        }
    }
}
