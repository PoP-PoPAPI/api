<?php
namespace PoP\API\PersistedFragments;

interface PersistedFragmentManagerInterface
{
    public function getPersistedFragments(): array;
    public function getPersistedFragmentsForSchema(): array;
    public function add(string $fragmentName, string $fragmentResolution, ?string $description = null): void;
}