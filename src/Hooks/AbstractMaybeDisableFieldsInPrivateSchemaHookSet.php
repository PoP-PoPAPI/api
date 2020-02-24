<?php
namespace PoP\API\Hooks;

use PoP\API\Environment;
use PoP\Engine\Hooks\AbstractCMSHookSet;
use PoP\ComponentModel\TypeResolvers\AbstractTypeResolver;
use PoP\ComponentModel\TypeResolvers\TypeResolverInterface;
use PoP\ComponentModel\FieldResolvers\FieldResolverInterface;

abstract class AbstractMaybeDisableFieldsInPrivateSchemaHookSet extends AbstractCMSHookSet
{
    /**
     * Indicate if this hook is enabled
     *
     * @return boolean
     */
    protected function enabled(): bool
    {
        return true;
    }
    protected function onlyForPrivateSchema(): bool
    {
        return true;
    }
    public function cmsInit(): void
    {
        if ($this->onlyForPrivateSchema() && !Environment::usePrivateSchemaMode()) {
            return;
        }
        /**
         * Check if doing privateSchemaMode, and ask if to disable the fields
         */
        if ($this->enabled() && $this->disableFieldsInPrivateSchemaMode()) {
            // If no field defined => it applies to any field
            if ($fieldNames = $this->getFieldNames()) {
                foreach ($fieldNames as $fieldName) {
                    $this->hooksAPI->addFilter(
                        AbstractTypeResolver::getHookNameToFilterField($fieldName),
                        array($this, 'maybeFilterFieldNames'),
                        10,
                        4
                    );
                }
            } else {
                $this->hooksAPI->addFilter(
                    AbstractTypeResolver::getHookNameToFilterField(),
                    array($this, 'maybeFilterFieldNames'),
                    10,
                    4
                );
            }
        }
    }

    /**
     * Return true if the fields must be disabled
     *
     * @return boolean
     */
    abstract protected function disableFieldsInPrivateSchemaMode(): bool;

    public function maybeFilterFieldNames(bool $include, TypeResolverInterface $typeResolver, FieldResolverInterface $fieldResolver, string $fieldName): bool
    {
        // Because there may be several hooks chained, if any of them has already rejected the field, then already return that response
        if (!$include) {
            return false;
        }
        // Check if to remove the field
        return !$this->removeFieldNames($typeResolver, $fieldResolver, $fieldName);
    }
    /**
     * Field names to remove
     *
     * @return array
     */
    abstract protected function getFieldNames(): array;
    /**
     * Decide if to remove the fieldNames
     *
     * @param TypeResolverInterface $typeResolver
     * @param FieldResolverInterface $fieldResolver
     * @param string $fieldName
     * @return boolean
     */
    protected function removeFieldNames(TypeResolverInterface $typeResolver, FieldResolverInterface $fieldResolver, string $fieldName): bool
    {
        return true;
    }
}
