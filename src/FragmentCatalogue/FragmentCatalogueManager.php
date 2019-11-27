<?php
namespace PoP\API\FragmentCatalogue;

use PoP\API\Schema\SchemaDefinition;

class FragmentCatalogueManager implements FragmentCatalogueManagerInterface
{
    protected $fragmentCatalogue = [];
    protected $fragmentSchema = [];

    public function getFragmentCatalogue(): array
    {
        return $this->fragmentCatalogue;
    }

    public function getFragmentCatalogueForSchema(): array
    {
        return array_values($this->fragmentSchema);
    }

    public function add(string $fragmentName, string $fragmentResolution, ?string $description = null): void
    {
        $this->fragmentCatalogue[$fragmentName] = $fragmentResolution;
        $this->fragmentSchema[$fragmentName] = [
            SchemaDefinition::ARGNAME_NAME => $fragmentName,
        ];
        if ($description) {
            $this->fragmentSchema[$fragmentName][SchemaDefinition::ARGNAME_DESCRIPTION] = $description;
        }
        $this->fragmentSchema[$fragmentName][SchemaDefinition::ARGNAME_FRAGMENT_RESOLUTION] = $fragmentResolution;
    }
}
