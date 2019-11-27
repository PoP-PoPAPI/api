<?php
namespace PoP\API\PersistedFragments;

use PoP\API\Schema\SchemaDefinition;

class PersistedFragmentManager implements PersistedFragmentManagerInterface
{
    protected $persistedFragments = [];
    protected $persistedFragmentsForSchema = [];

    public function getPersistedFragments(): array
    {
        return $this->persistedFragments;
    }

    public function getPersistedFragmentsForSchema(): array
    {
        return array_values($this->persistedFragmentsForSchema);
    }

    public function add(string $fragmentName, string $fragmentResolution, ?string $description = null): void
    {
        $this->persistedFragments[$fragmentName] = $fragmentResolution;
        $this->persistedFragmentsForSchema[$fragmentName] = [
            SchemaDefinition::ARGNAME_NAME => $fragmentName,
        ];
        if ($description) {
            $this->persistedFragmentsForSchema[$fragmentName][SchemaDefinition::ARGNAME_DESCRIPTION] = $description;
        }
        $this->persistedFragmentsForSchema[$fragmentName][SchemaDefinition::ARGNAME_FRAGMENT_RESOLUTION] = $fragmentResolution;
    }
}
