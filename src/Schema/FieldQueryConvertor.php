<?php
namespace PoP\API\Schema;
use PoP\ComponentModel\GeneralUtils;
use PoP\ComponentModel\Schema\QuerySyntax;
use PoP\ComponentModel\Schema\ErrorMessageStoreInterface;

class FieldQueryConvertor implements FieldQueryConvertorInterface
{
    // Cache vars to take from the request
    private $fragmentsFromRequestCache;

    // Services
    private $errorMessageStore;

    public function __construct(
        ErrorMessageStoreInterface $errorMessageStore
    ) {
        $this->errorMessageStore = $errorMessageStore;
    }

    public function convertAPIQuery(string $dotNotation, ?array $fragments = null): array
    {
        $fragments = $fragments ?? $this->getFragmentsFromRequest();

        // If it is a string, split the ElemCount with ',', the inner ElemCount with '.', and the inner fields with '|'
        $fields = [];

        // Support a query combining relational and properties:
        // ?field=posts.id|title|author.id|name|posts.id|title|author.name
        // Transform it into:
        // ?field=posts.id|title,posts.author.id|name,posts.author.posts.id|title,posts.author.posts.author.name
        $dotNotation = $this->expandRelationalProperties($dotNotation);

        // Replace all fragment placeholders with the actual fragments
        $replacedDotNotation = [];
        foreach (GeneralUtils::splitElements($dotNotation, QuerySyntax::SYMBOL_QUERYFIELDS_SEPARATOR, [QuerySyntax::SYMBOL_FIELDARGS_OPENING, QuerySyntax::SYMBOL_FIELDDIRECTIVE_OPENING], [QuerySyntax::SYMBOL_FIELDARGS_CLOSING, QuerySyntax::SYMBOL_FIELDDIRECTIVE_CLOSING]) as $commafields) {
            if ($replacedCommaFields = $this->replaceFragments($commafields, $fragments)) {
                $replacedDotNotation[] = $replacedCommaFields;
            }
        }
        if ($dotNotation = implode(QuerySyntax::SYMBOL_QUERYFIELDS_SEPARATOR, $replacedDotNotation)) {

            // After replacing the fragments, expand relational properties once again, since any such string could have been provided through a fragment
            // Eg: a fragment can contain strings such as "id|author.id"
            $dotNotation = $this->expandRelationalProperties($dotNotation);

            // Initialize the pointer
            $pointer = &$fields;

            // Allow for bookmarks, similar to GraphQL: https://graphql.org/learn/queries/#bookmarks
            // The bookmark "prev" (under constant TOKEN_BOOKMARK) is a reserved one: it always refers to the previous query node
            $bookmarkPaths = [];

            // Split the ElemCount by ",". Use `splitElements` instead of `explode` so that the "," can also be inside the fieldArgs
            foreach (GeneralUtils::splitElements($dotNotation, QuerySyntax::SYMBOL_QUERYFIELDS_SEPARATOR, [QuerySyntax::SYMBOL_FIELDARGS_OPENING, QuerySyntax::SYMBOL_FIELDDIRECTIVE_OPENING], [QuerySyntax::SYMBOL_FIELDARGS_CLOSING, QuerySyntax::SYMBOL_FIELDDIRECTIVE_CLOSING]) as $commafields) {

                // The fields are split by "."
                // Watch out: we need to ignore all instances of "(" and ")" which may happen inside the fieldArg values!
                // Eg: /api/?fields=posts(searchfor:this => ( and this => ) are part of the search too).id|title
                $dotfields = GeneralUtils::splitElements($commafields, QuerySyntax::SYMBOL_RELATIONALFIELDS_NEXTLEVEL, [QuerySyntax::SYMBOL_FIELDARGS_OPENING, QuerySyntax::SYMBOL_FIELDDIRECTIVE_OPENING], [QuerySyntax::SYMBOL_FIELDARGS_CLOSING, QuerySyntax::SYMBOL_FIELDDIRECTIVE_CLOSING], true);

                // If there is a path to the node...
                if (count($dotfields) >= 2) {
                    // If surrounded by "[]", the first element references a bookmark from a previous iteration. If so, retrieve it
                    $firstPathLevel = $dotfields[0];
                    // Remove the fieldDirective, if it has one
                    if ($fieldDirectiveSplit = GeneralUtils::splitElements($firstPathLevel, QuerySyntax::SYMBOL_FIELDDIRECTIVE_OPENING, QuerySyntax::SYMBOL_FIELDARGS_OPENING, QuerySyntax::SYMBOL_FIELDARGS_CLOSING)) {
                        $firstPathLevel = $fieldDirectiveSplit[0];
                    }
                    if (
                        (substr($firstPathLevel, 0, strlen(QuerySyntax::SYMBOL_BOOKMARK_OPENING)) == QuerySyntax::SYMBOL_BOOKMARK_OPENING) &&
                        (substr($firstPathLevel, -1*strlen(QuerySyntax::SYMBOL_BOOKMARK_CLOSING)) == QuerySyntax::SYMBOL_BOOKMARK_CLOSING)
                    ) {
                        $bookmark = substr($firstPathLevel, strlen(QuerySyntax::SYMBOL_BOOKMARK_OPENING), strlen($firstPathLevel)-1-strlen(QuerySyntax::SYMBOL_BOOKMARK_CLOSING));
                        // If this bookmark was not set...
                        if (!isset($bookmarkPaths[$bookmark])) {
                            // Show an error and discard this element
                            $errorMessage = sprintf(
                                $this->translationAPI->__('Query path alias \'%s\' is undefined. Query section \'%s\' has been ignored', 'pop-component-model'),
                                $bookmark,
                                $commafields
                            );
                            $this->errorMessageStore->addQueryError($errorMessage);
                            unset($bookmarkPaths[QueryTokens::TOKEN_BOOKMARK_PREV]);
                            continue;
                        }
                        // Replace the first element with the bookmark path
                        array_shift($dotfields);
                        $dotfields = array_merge(
                            $bookmarkPaths[$bookmark],
                            $dotfields
                        );
                    }

                    // At every subpath, it can define a bookmark to that fragment by adding "[bookmarkName]" at its end
                    for ($pathLevel=0; $pathLevel<count($dotfields)-1; $pathLevel++) {

                        $errorMessageOrSymbolPositions = $this->validateProperty(
                            $dotfields[$pathLevel],
                            $commafields
                        );
                        // If the validation is a string, then it's an error
                        if (is_string($errorMessageOrSymbolPositions)) {
                            $error = (string)$errorMessageOrSymbolPositions;
                            $this->errorMessageStore->addQueryError($error);
                            unset($bookmarkPaths[QueryTokens::TOKEN_BOOKMARK_PREV]);
                            // Exit 2 levels, so it doesn't process the whole query section, not just the property
                            continue 2;
                        }
                        // Otherwise, it is an array with all the symbol positions
                        $symbolPositions = (array)$errorMessageOrSymbolPositions;
                        list(
                            $fieldArgsOpeningSymbolPos,
                            $fieldArgsClosingSymbolPos,
                            $bookmarkOpeningSymbolPos,
                            $bookmarkClosingSymbolPos,
                            $fieldDirectivesOpeningSymbolPos,
                            $fieldDirectivesClosingSymbolPos,
                        ) = $symbolPositions;

                        // If it has both "[" and "]"...
                        if ($bookmarkClosingSymbolPos !== false && $bookmarkOpeningSymbolPos !== false) {
                            // Extract the bookmark
                            $startAliasPos = $bookmarkOpeningSymbolPos+strlen(QuerySyntax::SYMBOL_BOOKMARK_OPENING);
                            $bookmark = substr($dotfields[$pathLevel], $startAliasPos, $bookmarkClosingSymbolPos-$startAliasPos);

                            // If the bookmark starts with "@", it's also a property alias.
                            $alias = '';
                            if (substr($bookmark, 0, strlen(QuerySyntax::SYMBOL_FIELDALIAS_PREFIX)) == QuerySyntax::SYMBOL_FIELDALIAS_PREFIX) {
                                // Add the alias again to the pathLevel item, in the right format:
                                // Instead of fieldName[@alias] it is fieldName@alias
                                $alias = $bookmark;
                                $bookmark = substr($bookmark, strlen(QuerySyntax::SYMBOL_FIELDALIAS_PREFIX));
                            }

                            // Remove the bookmark from the path. Add the alias again, and keep the fieldDirective "<...>
                            $dotfields[$pathLevel] =
                                substr($dotfields[$pathLevel], 0, $bookmarkOpeningSymbolPos).
                                $alias.
                                (
                                    ($fieldDirectivesOpeningSymbolPos !== false) ?
                                        substr($dotfields[$pathLevel], $fieldDirectivesOpeningSymbolPos) :
                                        ''
                                );

                            // Recalculate the path (all the levels until the pathLevel), and store it to be used on a later iteration
                            $bookmarkPath = $dotfields;
                            array_splice($bookmarkPath, $pathLevel+1);
                            $bookmarkPaths[$bookmark] = $bookmarkPath;
                            // This works now:
                            // ?fields=posts(limit:3;search:template)[@posts].id|title,[posts].url
                            // Also support appending "@" before the bookmark for the aliases
                            // ?fields=posts(limit:3;search:template)[@posts].id|title,[@posts].url
                            if ($alias) {
                                $bookmarkPaths[$alias] = $bookmarkPath;
                            }
                        }
                    }

                    // Calculate the new "prev" bookmark path
                    $bookmarkPrevPath = $dotfields;
                    array_pop($bookmarkPrevPath);
                    $bookmarkPaths[QueryTokens::TOKEN_BOOKMARK_PREV] = $bookmarkPrevPath;
                }

                // For each item, advance to the last level by following the "."
                for ($i = 0; $i < count($dotfields)-1; $i++) {
                    $pointer[$dotfields[$i]] = $pointer[$dotfields[$i]] ?? array();
                    $pointer = &$pointer[$dotfields[$i]];
                }

                // The last level can contain several fields, separated by "|"
                $pipefields = $dotfields[count($dotfields)-1];
                // Use `splitElements` instead of `explode` so that the "|" can also be inside the fieldArgs (eg: order:title|asc)
                foreach (GeneralUtils::splitElements($pipefields, QuerySyntax::SYMBOL_FIELDPROPERTIES_SEPARATOR, [QuerySyntax::SYMBOL_FIELDARGS_OPENING, QuerySyntax::SYMBOL_FIELDDIRECTIVE_OPENING], [QuerySyntax::SYMBOL_FIELDARGS_CLOSING, QuerySyntax::SYMBOL_FIELDDIRECTIVE_CLOSING]) as $pipefield) {
                    $errorMessageOrSymbolPositions = $this->validateProperty(
                        $pipefield
                    );
                    // If the validation is a string, then it's an error
                    if (is_string($errorMessageOrSymbolPositions)) {
                        $error = (string)$errorMessageOrSymbolPositions;
                        $this->errorMessageStore->addQueryError($error);
                        // Exit 1 levels, so it ignores only this property but keeps processing the others
                        continue;
                    }
                    $pointer[] = $pipefield;
                }
                $pointer = &$fields;
            }
        }

        return $fields;
    }

    protected function getFragmentsFromRequest(): array
    {
        if (is_null($this->fragmentsFromRequestCache)) {
            $this->fragmentsFromRequestCache = $this->doGetFragmentsFromRequest();
        }
        return $this->fragmentsFromRequestCache;
    }

    protected function doGetFragmentsFromRequest(): array
    {
        // Each fragment is provided through $_REQUEST[fragments][fragmentName] or directly $_REQUEST[fragmentName]
        return array_merge(
            $_REQUEST,
            $_REQUEST['fragments'] ?? []
        );
    }
}
