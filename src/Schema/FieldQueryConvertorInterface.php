<?php
namespace PoP\API\Schema;

interface FieldQueryConvertorInterface
{
    public function convertAPIQuery(string $dotNotation, ?array $fragments = null): array;
}
