<?php
namespace PoP\API\DirectiveResolvers;
use PoP\ComponentModel\DirectiveResolvers\AbstractSchemaDirectiveResolver;
use PoP\ComponentModel\FieldResolvers\FieldResolverInterface;

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
}
