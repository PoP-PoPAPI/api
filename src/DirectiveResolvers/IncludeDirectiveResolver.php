<?php
namespace PoP\API\DirectiveResolvers;
use PoP\ComponentModel\Schema\SchemaDefinition;
use PoP\Translation\Facades\TranslationAPIFacade;
use PoP\ComponentModel\FieldResolvers\FieldResolverInterface;
use PoP\ComponentModel\DirectiveResolvers\AbstractSchemaDirectiveResolver;

class IncludeDirectiveResolver extends AbstractSchemaDirectiveResolver
{
    use FilterIDsSatisfyingConditionDirectiveResolverTrait;

    const DIRECTIVE_NAME = 'include';
    public static function getDirectiveName(): string {
        return self::DIRECTIVE_NAME;
    }

    public function resolveDirective(FieldResolverInterface $fieldResolver, array &$resultIDItems, array &$idsDataFields, array &$dbItems, array &$dbErrors, array &$dbWarnings, array &$schemaErrors, array &$schemaWarnings, array &$schemaDeprecations)
    {
        // Check the condition field. If it is satisfied, then keep those fields, otherwise remove them
        $includeDataFieldsForIds = $this->getIdsSatisfyingCondition($fieldResolver, $resultIDItems, $this->directive, $idsDataFields, $dbErrors, $dbWarnings);
        $skipDataFieldsForIds = array_diff(array_keys($idsDataFields), $includeDataFieldsForIds);
        foreach ($skipDataFieldsForIds as $id) {
            $idsDataFields[$id]['direct'] = [];
            $idsDataFields[$id]['conditional'] = [];
        }
    }
    public function getSchemaDirectiveDescription(FieldResolverInterface $fieldResolver): ?string
    {
        $translationAPI = TranslationAPIFacade::getInstance();
        return $translationAPI->__('Include the field value in the output only if the argument \'if\' evals to `true`', 'api');
    }
    public function getSchemaDirectiveArgs(FieldResolverInterface $fieldResolver): array
    {
        $translationAPI = TranslationAPIFacade::getInstance();
        return [
            [
                SchemaDefinition::ARGNAME_NAME => 'if',
                SchemaDefinition::ARGNAME_TYPE => SchemaDefinition::TYPE_BOOL,
                SchemaDefinition::ARGNAME_DESCRIPTION => $translationAPI->__('Argument to evaluate to `true` to include the field value in the output', 'block-metadata'),
            ],
        ];
    }
}
