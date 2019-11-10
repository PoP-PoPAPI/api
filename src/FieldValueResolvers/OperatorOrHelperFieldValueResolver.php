<?php
namespace PoP\API\FieldValueResolvers;

use PoP\ComponentModel\ErrorUtils;
use PoP\GuzzleHelpers\GuzzleHelpers;
use PoP\ComponentModel\Schema\SchemaDefinition;
use PoP\Translation\Facades\TranslationAPIFacade;
use PoP\ComponentModel\FieldResolvers\FieldResolverInterface;
use PoP\ComponentModel\FieldValueResolvers\AbstractOperatorOrHelperFieldValueResolver;
use PoP\API\Misc\OperatorHelpers;

class OperatorOrHelperFieldValueResolver extends AbstractOperatorOrHelperFieldValueResolver
{
    public const ERRORCODE_PATHNOTREACHABLE = 'path-not-reachable';

    public static function getFieldNamesToResolve(): array
    {
        return [
            'getJSON',
            'extract',
        ];
    }

    public function getSchemaFieldType(FieldResolverInterface $fieldResolver, string $fieldName): ?string
    {
        $types = [
            'getJSON' => SchemaDefinition::TYPE_OBJECT,
            'extract' => SchemaDefinition::TYPE_MIXED,
        ];
        return $types[$fieldName] ?? parent::getSchemaFieldType($fieldResolver, $fieldName);
    }

    public function getSchemaFieldDescription(FieldResolverInterface $fieldResolver, string $fieldName): ?string
    {
        $translationAPI = TranslationAPIFacade::getInstance();
        $descriptions = [
            'getJSON' => $translationAPI->__('Retrieve data from URL and decode it as a JSON object', 'pop-component-model'),
            'extract' => $translationAPI->__('Given an object, it retrieves the data under a certain path', 'pop-component-model'),
        ];
        return $descriptions[$fieldName] ?? parent::getSchemaFieldDescription($fieldResolver, $fieldName);
    }

    public function getSchemaFieldArgs(FieldResolverInterface $fieldResolver, string $fieldName): array
    {
        $translationAPI = TranslationAPIFacade::getInstance();
        switch ($fieldName) {
            case 'getJSON':
                return [
                    [
                        SchemaDefinition::ARGNAME_NAME => 'url',
                        SchemaDefinition::ARGNAME_TYPE => SchemaDefinition::TYPE_STRING,
                        SchemaDefinition::ARGNAME_DESCRIPTION => $translationAPI->__('The URL to request', 'pop-component-model'),
                        SchemaDefinition::ARGNAME_MANDATORY => true,
                    ],
                ];
            case 'extract':
                return [
                    [
                        SchemaDefinition::ARGNAME_NAME => 'object',
                        SchemaDefinition::ARGNAME_TYPE => SchemaDefinition::TYPE_OBJECT,
                        SchemaDefinition::ARGNAME_DESCRIPTION => $translationAPI->__('The object to retrieve the data from', 'pop-component-model'),
                        SchemaDefinition::ARGNAME_MANDATORY => true,
                    ],
                    [
                        SchemaDefinition::ARGNAME_NAME => 'path',
                        SchemaDefinition::ARGNAME_TYPE => SchemaDefinition::TYPE_STRING,
                        SchemaDefinition::ARGNAME_DESCRIPTION => $translationAPI->__('The path to retrieve data from the object. Paths are separated with \'.\' for each sublevel', 'pop-component-model'),
                        SchemaDefinition::ARGNAME_MANDATORY => true,
                    ],
                ];
        }

        return parent::getSchemaFieldArgs($fieldResolver, $fieldName);
    }

    protected function getDataFromPathError(string $fieldName, string $errorMessage)
    {
        return ErrorUtils::getError(
            $fieldName,
            self::ERRORCODE_PATHNOTREACHABLE,
            $errorMessage
        );
    }

    protected function getDataFromPath(string $fieldName, array $data, string $path)
    {
        try {
            $dataPointer = OperatorHelpers::getArrayItemUnderPath($data, $path);
        } catch (Exception $e) {
            return $this->getDataFromPathError($fieldName, $e->getMessage());
        }
        return $dataPointer;
    }

    public function resolveValue(FieldResolverInterface $fieldResolver, $resultItem, string $fieldName, array $fieldArgs = [])
    {
        switch ($fieldName) {
            case 'getJSON':
                return GuzzleHelpers::requestJSON($fieldArgs['url'], [], 'GET');
            case 'extract':
                return $this->getDataFromPath($fieldName, $fieldArgs['object'], $fieldArgs['path']);
        }
        return parent::resolveValue($fieldResolver, $resultItem, $fieldName, $fieldArgs);
    }
}
