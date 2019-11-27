<?php
namespace PoP\API\FragmentCatalogue;

interface FragmentCatalogueManagerInterface
{
    public function getFragmentCatalogue(): array;
    public function add(string $fragmentName, string $fragmentResolution): void;
}
