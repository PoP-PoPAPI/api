<?php
namespace PoP\API\FragmentCatalogue;

interface FragmentCatalogueManagerInterface
{
    public function getFragmentCatalogue(): array;
    public function getFragmentCatalogueForSchema(): array;
    public function add(string $fragmentName, string $fragmentResolution, ?string $description = null): void;
}
