<?php
namespace PoP\API\Hooks;

use PoP\API\Environment;
use PoP\Engine\Hooks\AbstractCMSHookSet;
use PoP\ComponentModel\TypeResolvers\AbstractTypeResolver;
use PoP\ComponentModel\TypeResolvers\TypeResolverInterface;
use PoP\ComponentModel\FieldResolvers\FieldResolverInterface;

abstract class AbstractMaybeDisableDirectivesInPrivateSchemaHookSet extends AbstractCMSHookSet
{
    public function cmsInit(): void
    {
        /**
         * Check if doing privateSchemaMode, and ask if to disable the directives
         */
        if (Environment::usePrivateSchemaMode() && $this->disableDirectivesInPrivateSchemaMode()) {
            // If no directiveNames defined, apply to all of them
            if ($directiveNames = $this->getDirectiveNames()) {
                foreach ($directiveNames as $directiveName) {
                    $this->hooksAPI->addFilter(
                        AbstractTypeResolver::getHookNameToFilterDirective($directiveName),
                        array($this, 'maybeFilterDirectiveNames'),
                        10,
                        3
                    );
                }
            } else {
                $this->hooksAPI->addFilter(
                    AbstractTypeResolver::getHookNameToFilterDirective(),
                    array($this, 'maybeFilterDirectiveNames'),
                    10,
                    3
                );
            }
        }
    }

    /**
     * Return true if the directives must be disabled
     *
     * @return boolean
     */
    abstract protected function disableDirectivesInPrivateSchemaMode(): bool;

    public function maybeFilterDirectiveNames(bool $include, TypeResolverInterface $typeResolver,  string $directiveName): bool
    {
        // Because there may be several hooks chained, if any of them has already rejected the field, then already return that response
        if (!$include) {
            return false;
        }
        // Check if to remove the directive
        return !$this->removeDirectiveNames($typeResolver,  $directiveName);
    }
    /**
     * Affected directiveNames
     *
     * @param TypeResolverInterface $typeResolver
     * @param FieldResolverInterface $directiveResolver
     * @param string $directiveName
     * @return boolean
     */
    abstract protected function getDirectiveNames(): array;
    /**
     * Decide if to remove the directiveNames
     *
     * @param TypeResolverInterface $typeResolver
     * @param FieldResolverInterface $directiveResolver
     * @param string $directiveName
     * @return boolean
     */
    protected function removeDirectiveNames(TypeResolverInterface $typeResolver, string $directiveName): bool
    {
        return true;
    }
}
