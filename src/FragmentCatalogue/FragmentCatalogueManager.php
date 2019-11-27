<?php
namespace PoP\API\FragmentCatalogue;

class FragmentCatalogueManager implements FragmentCatalogueManagerInterface
{
    protected $fragmentCatalogue = [];

    public function getFragmentCatalogue(): array
    {
        return $this->fragmentCatalogue;
    }

    public function add(string $fragmentName, string $fragmentResolution): void
    {
        $this->fragmentCatalogue[$fragmentName] = $fragmentResolution;
    }
}
