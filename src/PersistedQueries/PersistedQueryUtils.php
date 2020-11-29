<?php

declare(strict_types=1);

namespace PoP\API\PersistedQueries;

use PoP\API\Schema\QuerySymbols;
use PoP\API\Facades\PersistedQueryManagerFacade;

class PersistedQueryUtils
{
    /**
     * Trim, and remove tabs and new lines
     *
     * @param string $fragmentResolution
     * @return string
     */
    public static function removeWhitespaces(string $fragmentResolution): string
    {
        return preg_replace('/[ ]{2,}|[\t]|[\n]/', '', trim($fragmentResolution));
    }

    /**
     * Symfony's DependencyInjection component uses format "%parameter%", and PoP API uses format "%expression%",
     * so when passing an expression like "%self%" it throws an exception, expecting this to be a parameter (which doesn't exist!)
     * To fix it, we add a space in all expressions like this: "% expression %", which works for the PoP API since the expression name is trimmed
     *
     * @param string $fragmentResolution
     * @return string
     */
    public static function addSpacingToExpressions(string $fragmentResolution): string
    {
        return preg_replace('/%([\s\S]+?)%/', '% $1 %', $fragmentResolution);
    }

    /**
     * If the query starts with "!" then it is the query name to a persisted query
     */
    public static function isPersistedQuery(string $query): bool
    {
        return substr($query, 0, strlen(QuerySymbols::PERSISTED_QUERY)) == QuerySymbols::PERSISTED_QUERY;
    }

    /**
     * Remove "!" to get the persisted query name
     */
    public static function getPersistedQueryName(string $query): string
    {
        return substr($query, strlen(QuerySymbols::PERSISTED_QUERY));
    }

    /**
     * Retrieve the query name from the persisted query param, which starts with "!"
     *
     * @param string $query
     * @return string
     */
    public static function maybeGetPersistedQuery(string $query): string
    {
        if (self::isPersistedQuery($query)) {
            // Get the query name, and extract the query from the PersistedQueryManager
            $queryName = self::getPersistedQueryName($query);
            $queryCatalogueManager = PersistedQueryManagerFacade::getInstance();
            if ($queryCatalogueManager->hasPersistedQuery($queryName)) {
                return $queryCatalogueManager->getPersistedQuery($queryName);
            }
        }
        return $query;
    }
}
