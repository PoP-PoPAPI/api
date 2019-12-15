<?php
namespace PoP\API\TypeResolvers;

use PoP\API\TypeDataLoaders\RootTypeDataLoader;
use PoP\ComponentModel\Schema\SchemaDefinition;
use PoP\Translation\Facades\TranslationAPIFacade;
use PoP\ComponentModel\TypeResolvers\AbstractTypeResolver;
use PoP\ComponentModel\Facades\Instances\InstanceManagerFacade;

class RootTypeResolver extends AbstractTypeResolver
{
    public const NAME = 'Root';

    public function getTypeName(): string
    {
        return self::NAME;
    }

    public function getSchemaTypeDescription(): ?string
    {
        $translationAPI = TranslationAPIFacade::getInstance();
        return $translationAPI->__('Root type, starting from which the query is executed', 'api');
    }

    public function getId($resultItem)
    {
        $root = $resultItem;
        return $root->getId();
    }

    public function getTypeDataLoaderClass(): string
    {
        return RootTypeDataLoader::class;
    }

    protected function addSchemaDefinition(array $stackMessages, array &$generalMessages, array $options = [])
    {
        parent::addSchemaDefinition($stackMessages, $generalMessages, $options);

        $instanceManager = InstanceManagerFacade::getInstance();
        $typeName = $this->getTypeName();

        // Only in the root we output the operators and helpers
        $directiveNameClasses = $this->getDirectiveNameClasses();
        foreach ($directiveNameClasses as $directiveName => $directiveClasses) {
            foreach ($directiveClasses as $directiveClass) {
                $directiveResolverInstance = $instanceManager->getInstance($directiveClass);
                // A directive can decide to not be added to the schema, eg: when it is repeated/implemented several times
                if ($directiveResolverInstance->skipAddingToSchemaDefinition()) {
                    continue;
                }
                $isGlobal = $directiveResolverInstance->isGlobal($this);
                if ($isGlobal) {
                    $directiveSchemaDefinition = $directiveResolverInstance->getSchemaDefinitionForDirective($this);
                    $this->schemaDefinition[$typeName][SchemaDefinition::ARGNAME_GLOBAL_DIRECTIVES][] = $directiveSchemaDefinition;
                }
            }
        }

        $schemaFieldResolvers = $this->getAllFieldResolvers();
        foreach ($schemaFieldResolvers as $fieldName => $fieldResolvers) {
            // Get the documentation from the first element
            $fieldResolver = $fieldResolvers[0];
            $isOperatorOrHelper = $fieldResolver->isOperatorOrHelper($this, $fieldName);
            if ($isOperatorOrHelper) {
                $this->addFieldSchemaDefinition($fieldResolver, $fieldName, $stackMessages, $generalMessages, $options);
            }
        }
    }
}

