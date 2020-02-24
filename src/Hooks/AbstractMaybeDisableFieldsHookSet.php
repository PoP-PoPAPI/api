<?php
namespace PoP\API\Hooks;

use PoP\Engine\Hooks\AbstractCMSHookSet;
use PoP\ComponentModel\TypeResolvers\AbstractTypeResolver;
use PoP\ComponentModel\TypeResolvers\TypeResolverInterface;
use PoP\ComponentModel\FieldResolvers\FieldResolverInterface;

abstract class AbstractMaybeDisableFieldsHookSet extends AbstractCMSHookSet
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
    public function cmsInit(): void
    {
        if (!$this->enabled()) {
            return;
        }

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
