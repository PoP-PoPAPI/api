<?php
namespace PoP\API\Hooks;

use PoP\API\Environment;
use PoP\Engine\Hooks\AbstractCMSHookSet;
use PoP\ComponentModel\TypeResolvers\AbstractTypeResolver;
use PoP\ComponentModel\TypeResolvers\TypeResolverInterface;
use PoP\ComponentModel\FieldResolvers\FieldResolverInterface;

abstract class AbstractMaybeDisableFieldsInPrivateSchemaHookSet extends AbstractCMSHookSet
{
    public function cmsInit(): void
    {
        /**
         * Check if doing privateSchemaMode, and ask if to disable the fields
         */
        if (Environment::usePrivateSchemaMode() && $this->disableFieldsInPrivateSchemaMode()) {
            $this->hooksAPI->addFilter(
                AbstractTypeResolver::HOOK_ENABLED_FIELD_NAMES,
                array($this, 'maybeFilterFieldNames'),
                10,
                4
            );
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
     * Decide if to remove the fieldNames
     *
     * @param TypeResolverInterface $typeResolver
     * @param FieldResolverInterface $fieldResolver
     * @param string $fieldName
     * @return boolean
     */
    abstract protected function removeFieldNames(TypeResolverInterface $typeResolver, FieldResolverInterface $fieldResolver, string $fieldName): bool;
}
