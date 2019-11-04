<?php
namespace PoP\API\FieldValueResolvers;

use PoP\ComponentModel\ErrorUtils;
use PoP\GuzzleHelpers\GuzzleHelpers;
use PoP\ComponentModel\Schema\SchemaDefinition;
use PoP\Translation\Facades\TranslationAPIFacade;
use PoP\ComponentModel\FieldResolvers\FieldResolverInterface;
use PoP\ComponentModel\FieldValueResolvers\AbstractOperatorFieldValueResolver;

class FieldValueResolver extends AbstractOperatorFieldValueResolver
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

    protected function getDataFromPathError(string $fieldName, array $data, string $path)
    {
        $translationAPI = TranslationAPIFacade::getInstance();
        return ErrorUtils::getError(
            $fieldName,
            self::ERRORCODE_PATHNOTREACHABLE,
            sprintf(
                $translationAPI->__('Path \'%s\' is not reachable for object: %s', 'pop-component-model'),
                $path,
                json_encode($data)
            )
        );
    }

    protected function getDataFromPath(string $fieldName, array $data, string $path)
    {
        $dataPointer = &$data;

        // Iterate the data array to the provided path.
        foreach (explode(POP_CONSTANT_APIJSONRESPONSE_PATHDELIMITERSYMBOL, $path) as $pathLevel) {
            if (!$dataPointer) {
                // If we reached the end of the array and can't keep going down any level more, then it's an error
                return $this->getDataFromPathError($fieldName, $data, $path);
            } elseif (isset($dataPointer[$pathLevel])) {
                // Retrieve the property under the pathLevel
                $dataPointer = &$dataPointer[$pathLevel];
            } elseif (is_array($dataPointer) && isset($dataPointer[0]) && is_array($dataPointer[0]) && isset($dataPointer[0][$pathLevel])) {
                // If it is an array, then retrieve that property from each element of the array
                $dataPointerArray = array_map(function($item) use($pathLevel) {
                    return $item[$pathLevel];
                }, $dataPointer);
                $dataPointer = &$dataPointerArray;
            } else {
                // We are accessing a level that doesn't exist
                // If we reached the end of the array and can't keep going down any level more, then it's an error
                return $this->getDataFromPathError($fieldName, $data, $path);
            }
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
