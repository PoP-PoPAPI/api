<?php
namespace PoP\API\Container;

use PoP\API\FragmentCatalogue\FragmentUtils;

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
        // Enable using expressions, by going around an incompatibility with Symfony's DependencyInjection component
        $fragmentResolution = FragmentUtils::addSpacingToExpressions($fragmentResolution);
        // Use this version to show in the schema
        $schemaFragmentResolution = $fragmentResolution;
        // Format the fragment: Remove the tabs and new lines
        $fragmentResolution = FragmentUtils::removeWhitespaces($fragmentResolution);
        // Inject the values into the service
        \PoP\Root\Container\ContainerBuilderUtils::injectValuesIntoService(
            'fragment_catalogue_manager',
            'add',
            $fragmentName,
            $fragmentResolution,
            $description,
            $schemaFragmentResolution
        );
    }
}
