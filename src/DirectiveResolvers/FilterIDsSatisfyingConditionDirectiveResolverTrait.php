<?php
namespace PoP\API\DirectiveResolvers;
use PoP\ComponentModel\FieldResolvers\FieldResolverInterface;

trait FilterIDsSatisfyingConditionDirectiveResolverTrait
{
    protected function getIdsSatisfyingCondition(FieldResolverInterface $fieldResolver, array &$resultIDItems, array &$idsDataFields, array &$dbErrors, array &$dbWarnings, array &$variables, array &$messages)
    {
        // Check the condition field. If it is satisfied, then skip those fields
        $idsSatisfyingCondition = [];
        foreach (array_keys($idsDataFields) as $id) {
            // Validate directive args for the resultItem
            $resultItem = $resultIDItems[$id];
            list(
                $resultItemValidDirective,
                $resultItemDirectiveName,
                $resultItemDirectiveArgs
            ) = $this->dissectAndValidateDirectiveForResultItem($fieldResolver, $resultItem, $dbErrors, $dbWarnings, $variables);
            // Check that the directive is valid. If it is not, $dbErrors will have the error already added
            if (is_null($resultItemValidDirective)) {
                continue;
            }
            // $resultItemDirectiveArgs has all the right directiveArgs values. Now we can evaluate on it
            if ($resultItemDirectiveArgs['if']) {
                $idsSatisfyingCondition[] = $id;
            }
        }
        return $idsSatisfyingCondition;
    }
}
