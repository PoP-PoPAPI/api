<?php
namespace PoP\API\Container;

use PoP\API\PersistedFragments\PersistedFragmentUtils;

class ContainerBuilderUtils {

    /**
     * Add a predefined fragment to the catalogue
     *
     * @param string $injectableServiceId
     * @param string $value
     * @param string $methodCall
     * @return void
     */
    public static function addFragmentToCatalogueService(
        string $fragmentName,
        string $fragmentResolution,
        ?string $description = null
    ): void
    {
        // Format the fragment: Remove the tabs and new lines
        $fragmentResolution = PersistedFragmentUtils::removeWhitespaces($fragmentResolution);
        // Enable using expressions, by going around an incompatibility with Symfony's DependencyInjection component
        $fragmentResolution = PersistedFragmentUtils::addSpacingToExpressions($fragmentResolution);
        // Inject the values into the service
        \PoP\Root\Container\ContainerBuilderUtils::injectValuesIntoService(
            'persisted_fragment_manager',
            'add',
            $fragmentName,
            $fragmentResolution,
            $description
        );
    }
}
