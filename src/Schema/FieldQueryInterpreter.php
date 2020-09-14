<?php

declare(strict_types=1);

namespace PoP\API\Schema;

use PoP\FieldQuery\QueryUtils;
use PoP\FieldQuery\QuerySyntax;
use PoP\ComponentModel\TypeResolvers\TypeResolverInterface;

class FieldQueryInterpreter extends \PoP\ComponentModel\Schema\FieldQueryInterpreter implements FieldQueryInterpreterInterface
{
    // Cache the output from functions
    private $extractedFieldArgumentValuesCache = [];

    /**
     * Extract field arg values without using the schema.
     * It is needed to replace embeddable fields ({{title}}) in the arguments
     *
     * @param TypeResolverInterface $typeResolver
     * @param string $field
     * @return array
     */
    public function extractFieldArgumentValues(string $field): array
    {
        if (!isset($this->extractedFieldArgumentValuesCache[$field])) {
            $this->extractedFieldArgumentValuesCache[$field] = $this->doExtractFieldArgumentValues($field);
        }
        return $this->extractedFieldArgumentValuesCache[$field];
    }

    protected function doExtractFieldArgumentValues(string $field): array
    {
        $fieldArgValues = [];
        // Extract the args from the string into an array
        if ($fieldArgsStr = $this->getFieldArgs($field)) {
            // Remove the opening and closing brackets
            $fieldArgsStr = substr($fieldArgsStr, strlen(QuerySyntax::SYMBOL_FIELDARGS_OPENING), strlen($fieldArgsStr) - strlen(QuerySyntax::SYMBOL_FIELDARGS_OPENING) - strlen(QuerySyntax::SYMBOL_FIELDARGS_CLOSING));
            // Remove the white spaces before and after
            if ($fieldArgsStr = trim($fieldArgsStr)) {
                // Iterate all the elements, and extract them into the array
                if ($fieldArgElems = $this->queryParser->splitElements($fieldArgsStr, QuerySyntax::SYMBOL_FIELDARGS_ARGSEPARATOR, [QuerySyntax::SYMBOL_FIELDARGS_OPENING, QuerySyntax::SYMBOL_FIELDARGS_ARGVALUEARRAY_OPENING], [QuerySyntax::SYMBOL_FIELDARGS_CLOSING, QuerySyntax::SYMBOL_FIELDARGS_ARGVALUEARRAY_CLOSING], QuerySyntax::SYMBOL_FIELDARGS_ARGVALUESTRING_OPENING, QuerySyntax::SYMBOL_FIELDARGS_ARGVALUESTRING_CLOSING)) {
                    for ($i = 0; $i < count($fieldArgElems); $i++) {
                        $fieldArg = $fieldArgElems[$i];
                        // If there is no separator, then the element is the value
                        $separatorPos = QueryUtils::findFirstSymbolPosition($fieldArg, QuerySyntax::SYMBOL_FIELDARGS_ARGKEYVALUESEPARATOR, [QuerySyntax::SYMBOL_FIELDARGS_OPENING, QuerySyntax::SYMBOL_FIELDARGS_ARGVALUEARRAY_OPENING], [QuerySyntax::SYMBOL_FIELDARGS_CLOSING, QuerySyntax::SYMBOL_FIELDARGS_ARGVALUEARRAY_CLOSING], QuerySyntax::SYMBOL_FIELDARGS_ARGVALUESTRING_OPENING, QuerySyntax::SYMBOL_FIELDARGS_ARGVALUESTRING_CLOSING);
                        if ($separatorPos === false) {
                            $fieldArgValue = $fieldArg;
                        } else {
                            $fieldArgValue = trim(substr($fieldArg, $separatorPos + strlen(QuerySyntax::SYMBOL_FIELDARGS_ARGKEYVALUESEPARATOR)));
                        }
                        $fieldArgValues[] = $fieldArgValue;
                    }
                }
            }
        }

        return $fieldArgValues;
    }
}
