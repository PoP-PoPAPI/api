<?php
namespace PoP\API\Schema;
use PoP\FieldQuery\Query\QuerySyntax;
use PoP\FieldQuery\Query\QueryHelpers;
use PoP\Translation\Contracts\TranslationAPIInterface;
use PoP\ComponentModel\Schema\ErrorMessageStoreInterface;
use PoP\QueryParsing\Parsers\QueryParserInterface;
use function strlen;
use function substr;
use function count;

class FieldQueryConvertor implements FieldQueryConvertorInterface
{
    // Cache the output from functions
    private $expandedRelationalPropertiesCache = [];

    // Cache vars to take from the request
    private $fragmentsFromRequestCache;

    // Services
    protected $translationAPI;
    protected $errorMessageStore;
    protected $queryParser;

    public function __construct(
        TranslationAPIInterface $translationAPI,
        ErrorMessageStoreInterface $errorMessageStore,
        QueryParserInterface $queryParser
    ) {
        $this->translationAPI = $translationAPI;
        $this->errorMessageStore = $errorMessageStore;
        $this->queryParser = $queryParser;
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
        foreach ($this->queryParser->splitElements($dotNotation, QuerySyntax::SYMBOL_QUERYFIELDS_SEPARATOR, [QuerySyntax::SYMBOL_FIELDARGS_OPENING, QuerySyntax::SYMBOL_BOOKMARK_OPENING, QuerySyntax::SYMBOL_FIELDDIRECTIVE_OPENING], [QuerySyntax::SYMBOL_FIELDARGS_CLOSING, QuerySyntax::SYMBOL_BOOKMARK_CLOSING, QuerySyntax::SYMBOL_FIELDDIRECTIVE_CLOSING], QuerySyntax::SYMBOL_FIELDARGS_ARGVALUESTRING_OPENING, QuerySyntax::SYMBOL_FIELDARGS_ARGVALUESTRING_CLOSING) as $commafields) {
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
            foreach ($this->queryParser->splitElements($dotNotation, QuerySyntax::SYMBOL_QUERYFIELDS_SEPARATOR, [QuerySyntax::SYMBOL_FIELDARGS_OPENING, QuerySyntax::SYMBOL_BOOKMARK_OPENING, QuerySyntax::SYMBOL_FIELDDIRECTIVE_OPENING], [QuerySyntax::SYMBOL_FIELDARGS_CLOSING, QuerySyntax::SYMBOL_BOOKMARK_CLOSING, QuerySyntax::SYMBOL_FIELDDIRECTIVE_CLOSING], QuerySyntax::SYMBOL_FIELDARGS_ARGVALUESTRING_OPENING, QuerySyntax::SYMBOL_FIELDARGS_ARGVALUESTRING_CLOSING) as $commafields) {
                // The fields are split by "."
                // Watch out: we need to ignore all instances of "(" and ")" which may happen inside the fieldArg values!
                // Eg: /api/?query=posts(searchfor:this => ( and this => ) are part of the search too).id|title
                $dotfields = $this->queryParser->splitElements($commafields, QuerySyntax::SYMBOL_RELATIONALFIELDS_NEXTLEVEL, [QuerySyntax::SYMBOL_FIELDARGS_OPENING, QuerySyntax::SYMBOL_FIELDDIRECTIVE_OPENING], [QuerySyntax::SYMBOL_FIELDARGS_CLOSING, QuerySyntax::SYMBOL_FIELDDIRECTIVE_CLOSING], QuerySyntax::SYMBOL_FIELDARGS_ARGVALUESTRING_OPENING, QuerySyntax::SYMBOL_FIELDARGS_ARGVALUESTRING_CLOSING);

                // If there is a path to the node...
                if (count($dotfields) >= 2) {
                    // If surrounded by "[]", the first element references a bookmark from a previous iteration. If so, retrieve it
                    $firstPathLevel = $dotfields[0];
                    // Remove the fieldDirective, if it has one
                    if ($fieldDirectiveSplit = $this->queryParser->splitElements($firstPathLevel, QuerySyntax::SYMBOL_FIELDDIRECTIVE_OPENING, QuerySyntax::SYMBOL_FIELDARGS_OPENING, QuerySyntax::SYMBOL_FIELDARGS_CLOSING, QuerySyntax::SYMBOL_FIELDARGS_ARGVALUESTRING_OPENING, QuerySyntax::SYMBOL_FIELDARGS_ARGVALUESTRING_CLOSING)) {
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
                            $aliasSymbolPos,
                            $bookmarkOpeningSymbolPos,
                            $bookmarkClosingSymbolPos,
                            $skipOutputIfNullSymbolPos,
                            $fieldDirectivesOpeningSymbolPos,
                            $fieldDirectivesClosingSymbolPos,
                        ) = $symbolPositions;

                        // If it has both "[" and "]"...
                        if ($bookmarkClosingSymbolPos !== false && $bookmarkOpeningSymbolPos !== false) {
                            // Extract the bookmark
                            $bookmarkStartPos = $bookmarkOpeningSymbolPos+strlen(QuerySyntax::SYMBOL_BOOKMARK_OPENING);
                            $bookmark = substr($dotfields[$pathLevel], $bookmarkStartPos, $bookmarkClosingSymbolPos-$bookmarkStartPos);

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
                                    $skipOutputIfNullSymbolPos !== false ?
                                        QuerySyntax::SYMBOL_SKIPOUTPUTIFNULL :
                                        ''
                                ).
                                (
                                    $fieldDirectivesOpeningSymbolPos !== false ?
                                        substr($dotfields[$pathLevel], $fieldDirectivesOpeningSymbolPos) :
                                        ''
                                );

                                // Recalculate the path (all the levels until the pathLevel), and store it to be used on a later iteration
                            $bookmarkPath = $dotfields;
                            array_splice($bookmarkPath, $pathLevel+1);
                            $bookmarkPaths[$bookmark] = $bookmarkPath;
                            // This works now:
                            // ?query=posts(limit:3,search:template)[@posts].id|title,[posts].url
                            // Also support appending "@" before the bookmark for the aliases
                            // ?query=posts(limit:3,search:template)[@posts].id|title,[@posts].url
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
                foreach ($this->queryParser->splitElements($pipefields, QuerySyntax::SYMBOL_FIELDPROPERTIES_SEPARATOR, [QuerySyntax::SYMBOL_FIELDARGS_OPENING, QuerySyntax::SYMBOL_FIELDDIRECTIVE_OPENING], [QuerySyntax::SYMBOL_FIELDARGS_CLOSING, QuerySyntax::SYMBOL_FIELDDIRECTIVE_CLOSING], QuerySyntax::SYMBOL_FIELDARGS_ARGVALUESTRING_OPENING, QuerySyntax::SYMBOL_FIELDARGS_ARGVALUESTRING_CLOSING) as $pipefield) {
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

    protected function expandRelationalProperties(string $dotNotation): string
    {
        if (!isset($this->expandedRelationalPropertiesCache[$dotNotation])) {
            $this->expandedRelationalPropertiesCache[$dotNotation] = $this->doExpandRelationalProperties($dotNotation);
        }
        return $this->expandedRelationalPropertiesCache[$dotNotation];
    }

    protected function doExpandRelationalProperties(string $dotNotation): string
    {
        // Support a query combining relational and properties:
        // ?field=posts.id|title|author.id|name|posts.id|title|author.name
        // Transform it into:
        // ?field=posts.id|title,posts.author.id|name,posts.author.posts.id|title,posts.author.posts.author.name
        // Strategy: continuously search for "." appearing after "|", recreate their full path, and add them as new query sections (separated by ",")
        $expandedDotNotations = [];
        foreach ($this->queryParser->splitElements($dotNotation, QuerySyntax::SYMBOL_QUERYFIELDS_SEPARATOR, [QuerySyntax::SYMBOL_FIELDARGS_OPENING, QuerySyntax::SYMBOL_BOOKMARK_OPENING, QuerySyntax::SYMBOL_FIELDDIRECTIVE_OPENING], [QuerySyntax::SYMBOL_FIELDARGS_CLOSING, QuerySyntax::SYMBOL_BOOKMARK_CLOSING, QuerySyntax::SYMBOL_FIELDDIRECTIVE_CLOSING], QuerySyntax::SYMBOL_FIELDARGS_ARGVALUESTRING_OPENING, QuerySyntax::SYMBOL_FIELDARGS_ARGVALUESTRING_CLOSING) as $commafields) {
            $dotPos = strpos($commafields, QuerySyntax::SYMBOL_RELATIONALFIELDS_NEXTLEVEL);
            if ($dotPos !== false) {
                while ($dotPos !== false) {
                    // Position of the first "|". Everything before there is path + first property
                    // We must make sure the "|" is not inside "()", otherwise this would fail:
                    // /api/graphql/?query=posts(order:title|asc).id|title
                    $pipeElements = $this->queryParser->splitElements($commafields, QuerySyntax::SYMBOL_FIELDPROPERTIES_SEPARATOR, [QuerySyntax::SYMBOL_FIELDARGS_OPENING, QuerySyntax::SYMBOL_FIELDDIRECTIVE_OPENING], [QuerySyntax::SYMBOL_FIELDARGS_CLOSING, QuerySyntax::SYMBOL_FIELDDIRECTIVE_CLOSING], QuerySyntax::SYMBOL_FIELDARGS_ARGVALUESTRING_OPENING, QuerySyntax::SYMBOL_FIELDARGS_ARGVALUESTRING_CLOSING);
                    if (count($pipeElements) >= 2) {
                        $pipePos = strlen($pipeElements[0]);
                        // Make sure the dot is not inside "()". Otherwise this will not work:
                        // /api/graphql/?query=posts(order:title|asc).id|date(format:Y.m.d)
                        $pipeRest = substr($commafields, 0, $pipePos);
                        $dotElements = $this->queryParser->splitElements($pipeRest, QuerySyntax::SYMBOL_RELATIONALFIELDS_NEXTLEVEL, [QuerySyntax::SYMBOL_FIELDARGS_OPENING, QuerySyntax::SYMBOL_FIELDDIRECTIVE_OPENING], [QuerySyntax::SYMBOL_FIELDARGS_CLOSING, QuerySyntax::SYMBOL_FIELDDIRECTIVE_CLOSING], QuerySyntax::SYMBOL_FIELDARGS_ARGVALUESTRING_OPENING, QuerySyntax::SYMBOL_FIELDARGS_ARGVALUESTRING_CLOSING);
                        // Watch out case in which there is no previous sectionPath. Eg: query=id|comments.id
                        if ($lastDotPos = strlen($pipeRest) - strlen($dotElements[count($dotElements)-1])) {
                            // The path to the properties
                            $sectionPath = substr($commafields, 0, $lastDotPos);
                            // Combination of properties and, possibly, further relational ElemCount
                            $sectionRest = substr($commafields, $lastDotPos);
                        } else {
                            $sectionPath = '';
                            $sectionRest = $commafields;
                        }
                        // If there is another "." after a "|", then it keeps going down the relational path to load other elements
                        $sectionRestPipePos = strpos($sectionRest, QuerySyntax::SYMBOL_FIELDPROPERTIES_SEPARATOR);
                        $sectionRestDotPos = strpos($sectionRest, QuerySyntax::SYMBOL_RELATIONALFIELDS_NEXTLEVEL);
                        if ($sectionRestPipePos !== false && $sectionRestDotPos !== false && $sectionRestDotPos > $sectionRestPipePos) {
                            // Extract the last property, from which further relational ElemCount are loaded, and create a new query section for it
                            // This is the subtring from the last ocurrence of "|" before the "." up to the "."
                            $lastPipePos = strrpos(
                                substr(
                                    $sectionRest,
                                    0,
                                    $sectionRestDotPos
                                ),
                                QuerySyntax::SYMBOL_FIELDPROPERTIES_SEPARATOR
                            );
                            // Extract the new "rest" of the query section
                            $querySectionRest = substr(
                                $sectionRest,
                                $lastPipePos+strlen(QuerySyntax::SYMBOL_FIELDPROPERTIES_SEPARATOR)
                            );
                            // Remove the relational property from the now only properties part
                            $sectionRest = substr(
                                $sectionRest,
                                0,
                                $lastPipePos
                            );
                            // Add these as 2 independent ElemCount to the query
                            $expandedDotNotations[] = $sectionPath.$sectionRest;
                            $commafields = $sectionPath.$querySectionRest;
                            // Keep iterating
                            $dotPos = strpos($commafields, QuerySyntax::SYMBOL_RELATIONALFIELDS_NEXTLEVEL);
                        } else {
                            // The element has no further relationships
                            $expandedDotNotations[] = $commafields;
                            // Break out from the cycle
                            break;
                        }
                    } else {
                        // The element has no further relationships
                        $expandedDotNotations[] = $commafields;
                        // Break out from the cycle
                        break;
                    }
                }
            } else {
                // The element has no relationships
                $expandedDotNotations[] = $commafields;
            }
        }

        // Recombine all the elements
        return implode(QuerySyntax::SYMBOL_QUERYFIELDS_SEPARATOR, $expandedDotNotations);
    }

    protected function getFragment($fragmentName, array $fragments): ?string
    {
        // A fragment can itself contain fragments!
        if ($fragment = $fragments[$fragmentName]) {
            return $this->replaceFragments($fragment, $fragments);
        }
        return null;
    }

    protected function resolveFragmentOrAddError(string $fragment, array $fragments): ?string
    {
        // Replace with the actual fragment
        $fragmentName = substr($fragment, strlen(QuerySyntax::SYMBOL_FRAGMENT_PREFIX));
        // If it has a bookmark or alias, it's an error
        $aliasSymbolPos = QueryHelpers::findFieldAliasSymbolPosition($fragmentName);
        if ($aliasSymbolPos !== false) {
            $this->errorMessageStore->addQueryError(sprintf(
                $this->translationAPI->__('Fragment \'%s\' cannot contain aliases, so it has been ignored', 'pop-component-model'),
                $fragmentName
            ));
            return null;
        }
        // If it has a fragment, extract it and then add it again on each component from the fragment
        $fragmentDirectives = '';
        list(
            $fieldDirectivesOpeningSymbolPos,
            $fieldDirectivesClosingSymbolPos
        ) = QueryHelpers::listFieldDirectivesSymbolPositions($fragmentName);
        if ($fieldDirectivesOpeningSymbolPos !== false || $fieldDirectivesClosingSymbolPos !== false) {
            // First check both "<" and ">" are present, or it's an error
            if ($fieldDirectivesOpeningSymbolPos === false || $fieldDirectivesClosingSymbolPos === false) {
                $this->errorMessageStore->addQueryError(sprintf(
                    $this->translationAPI->__('Fragment \'%s\' must contain both \'%s\' and \'%s\' to define directives, so it has been ignored', 'pop-component-model'),
                    $fragmentName,
                    QuerySyntax::SYMBOL_FIELDDIRECTIVE_OPENING,
                    QuerySyntax::SYMBOL_FIELDDIRECTIVE_CLOSING
                ));
                return null;
            }
            $fragmentDirectives = substr($fragmentName, $fieldDirectivesOpeningSymbolPos, $fieldDirectivesClosingSymbolPos);
            $fragmentName = substr($fragmentName, 0, $fieldDirectivesOpeningSymbolPos);
        }
        $fragment = $this->getFragment($fragmentName, $fragments);
        if (!$fragment) {
            $this->errorMessageStore->addQueryError(sprintf(
                $this->translationAPI->__('Fragment \'%s\' is undefined, so it has been ignored', 'pop-component-model'),
                $fragmentName
            ));
            return null;
        }
        // If the fragment has directives, attach them again to each component from the fragment
        // But only if the component doesn't already have a directive! Otherwise, the directive at the definition level takes priority
        if ($fragmentDirectives) {
            $fragmentPipeFields = $this->queryParser->splitElements($fragment, QuerySyntax::SYMBOL_FIELDPROPERTIES_SEPARATOR, [QuerySyntax::SYMBOL_FIELDARGS_OPENING, QuerySyntax::SYMBOL_FIELDDIRECTIVE_OPENING], [QuerySyntax::SYMBOL_FIELDARGS_CLOSING, QuerySyntax::SYMBOL_FIELDDIRECTIVE_CLOSING], QuerySyntax::SYMBOL_FIELDARGS_ARGVALUESTRING_OPENING, QuerySyntax::SYMBOL_FIELDARGS_ARGVALUESTRING_CLOSING);
            $fragment = implode(QuerySyntax::SYMBOL_FIELDPROPERTIES_SEPARATOR, array_filter(array_map(function($fragmentField) use($fragmentDirectives) {
                list(
                    $fragmentFieldDirectivesOpeningSymbolPos,
                    $fragmentFieldDirectivesClosingSymbolPos
                ) = QueryHelpers::listFieldDirectivesSymbolPositions($fragmentField);
                if ($fragmentFieldDirectivesOpeningSymbolPos !== false || $fragmentFieldDirectivesClosingSymbolPos !== false) {
                    // First check both "<" and ">" are present, or it's an error
                    if ($fragmentFieldDirectivesOpeningSymbolPos === false || $fragmentFieldDirectivesClosingSymbolPos === false) {
                        $this->errorMessageStore->addQueryError(sprintf(
                            $this->translationAPI->__('Fragment field \'%s\' must contain both \'%s\' and \'%s\' to define directives, so it has been ignored', 'pop-component-model'),
                            $fragmentField,
                            QuerySyntax::SYMBOL_FIELDDIRECTIVE_OPENING,
                            QuerySyntax::SYMBOL_FIELDDIRECTIVE_CLOSING
                        ));
                        return null;
                    }
                    // The fragmentField has directives, so prioritize these: do not attach the fragments directives
                    return $fragmentField;
                }
                // Attach the fragment resolution's directives to the field
                return $fragmentField.$fragmentDirectives;
            }, $fragmentPipeFields)));
        }

        return $fragment;
    }

    protected function replaceFragments(string $commafields, array $fragments): ?string
    {
        // The fields are split by "."
        // Watch out: we need to ignore all instances of "(" and ")" which may happen inside the fieldArg values!
        // Eg: /api/?query=posts(searchfor:this => ( and this => ) are part of the search too).id|title
        $dotfields = $this->queryParser->splitElements($commafields, QuerySyntax::SYMBOL_RELATIONALFIELDS_NEXTLEVEL, [QuerySyntax::SYMBOL_FIELDARGS_OPENING, QuerySyntax::SYMBOL_FIELDDIRECTIVE_OPENING], [QuerySyntax::SYMBOL_FIELDARGS_CLOSING, QuerySyntax::SYMBOL_FIELDDIRECTIVE_CLOSING], QuerySyntax::SYMBOL_FIELDARGS_ARGVALUESTRING_OPENING, QuerySyntax::SYMBOL_FIELDARGS_ARGVALUESTRING_CLOSING);

        // Replace all fragment placeholders with the actual fragments
        // Do this at the beginning, because the fragment may contain new leaves, which need be at the last level of the $dotfields array. So this array must be recalculated after replacing the fragments in
        // Iterate from right to left, because after replacing the fragment in, the length of $dotfields may increase
        // Right now only for the properties. For the path will be done immediately after
        $lastLevel = count($dotfields)-1;
        // Replace fragments for the properties, adding them to temporary variable $lastLevelProperties
        $pipefields = $this->queryParser->splitElements($dotfields[$lastLevel], QuerySyntax::SYMBOL_FIELDPROPERTIES_SEPARATOR, [QuerySyntax::SYMBOL_FIELDARGS_OPENING, QuerySyntax::SYMBOL_FIELDDIRECTIVE_OPENING], [QuerySyntax::SYMBOL_FIELDARGS_CLOSING, QuerySyntax::SYMBOL_FIELDDIRECTIVE_CLOSING], QuerySyntax::SYMBOL_FIELDARGS_ARGVALUESTRING_OPENING, QuerySyntax::SYMBOL_FIELDARGS_ARGVALUESTRING_CLOSING);
        $lastPropertyNumber = count($pipefields)-1;
        $lastLevelProperties = [];
        for ($propertyNumber=0; $propertyNumber<=$lastPropertyNumber; $propertyNumber++) {
            // If it starts with "--", then it's a fragment
            $pipeField = $pipefields[$propertyNumber];
            if (substr($pipeField, 0, strlen(QuerySyntax::SYMBOL_FRAGMENT_PREFIX)) == QuerySyntax::SYMBOL_FRAGMENT_PREFIX) {
                // Replace with the actual fragment
                $resolvedFragment = $this->resolveFragmentOrAddError($pipeField, $fragments);
                if (is_null($resolvedFragment)) {
                    continue;
                }
                $lastLevelProperties[] = $resolvedFragment;
            } else {
                $lastLevelProperties[] = $pipeField;
            }
        }
        // Assign variable $lastLevelProperties (which contains the replaced fragments) back to the last level of $dotfields
        $dotfields[$lastLevel] = implode(QuerySyntax::SYMBOL_FIELDPROPERTIES_SEPARATOR, $lastLevelProperties);

        // Now replace fragments for properties
        for ($pathLevel=$lastLevel-1; $pathLevel>=0; $pathLevel--) {
            // If it starts with "--", then it's a fragment
            $pipeField = $dotfields[$pathLevel];
            if (substr($pipeField, 0, strlen(QuerySyntax::SYMBOL_FRAGMENT_PREFIX)) == QuerySyntax::SYMBOL_FRAGMENT_PREFIX) {
                // Replace with the actual fragment
                $resolvedFragment = $this->resolveFragmentOrAddError($pipeField, $fragments);
                if (is_null($resolvedFragment)) {
                    $this->errorMessageStore->addQueryError(sprintf(
                        $this->translationAPI->__('Because fragment \'%s\' has errors, query section \'%s\' has been ignored', 'pop-component-model'),
                        $pipeField,
                        $commafields
                    ));
                    // Remove whole query section
                    return null;
                }
                $fragmentDotfields = $this->queryParser->splitElements($resolvedFragment, QuerySyntax::SYMBOL_RELATIONALFIELDS_NEXTLEVEL, [QuerySyntax::SYMBOL_FIELDARGS_OPENING, QuerySyntax::SYMBOL_FIELDDIRECTIVE_OPENING], [QuerySyntax::SYMBOL_FIELDARGS_CLOSING, QuerySyntax::SYMBOL_FIELDDIRECTIVE_CLOSING], QuerySyntax::SYMBOL_FIELDARGS_ARGVALUESTRING_OPENING, QuerySyntax::SYMBOL_FIELDARGS_ARGVALUESTRING_CLOSING);
                array_splice($dotfields, $pathLevel, 1, $fragmentDotfields);
            }
        }

        // If we reach here, there were no errors with any path level, so add element again on array
        return implode(QuerySyntax::SYMBOL_RELATIONALFIELDS_NEXTLEVEL, $dotfields);
    }

    protected function validateProperty($property, $querySection = null)
    {
        $errorMessageEnd = $querySection ?
            sprintf(
                $this->translationAPI->__('Query section \'%s\' has been ignored', 'pop-component-model'),
                $querySection
            ) :
            $this->translationAPI->__('The property has been ignored', 'pop-component-model');

        // --------------------------------------------------------
        // Validate correctness of query constituents: fieldArgs, bookmark, skipOutputIfNull, directive
        // --------------------------------------------------------
        // Field Args
        list(
            $fieldArgsOpeningSymbolPos,
            $fieldArgsClosingSymbolPos
        ) = QueryHelpers::listFieldArgsSymbolPositions($property);

        // If it has "(" from the very beginning, then there's no fieldName, it's an error
        if ($fieldArgsOpeningSymbolPos === 0) {
            return sprintf(
                $this->translationAPI->__('Property \'%s\' is missing the field name. %s', 'pop-component-model'),
                $property,
                $errorMessageEnd
            );
        }

        // If it has only "(" or ")" but not the other one, it's an error
        if (($fieldArgsClosingSymbolPos === false && $fieldArgsOpeningSymbolPos !== false) || ($fieldArgsClosingSymbolPos !== false && $fieldArgsOpeningSymbolPos === false)) {
            return sprintf(
                $this->translationAPI->__('Arguments \'%s\' must start with symbol \'%s\' and end with symbol \'%s\'. %s', 'pop-component-model'),
                $property,
                QuerySyntax::SYMBOL_FIELDARGS_OPENING,
                QuerySyntax::SYMBOL_FIELDARGS_CLOSING,
                $errorMessageEnd
            );
        }

        // Bookmarks
        list(
            $bookmarkOpeningSymbolPos,
            $bookmarkClosingSymbolPos
        ) = QueryHelpers::listFieldBookmarkSymbolPositions($property);

        // If it has "[" from the very beginning, then there's no fieldName, it's an error
        if ($bookmarkOpeningSymbolPos === 0) {
            return sprintf(
                $this->translationAPI->__('Property \'%s\' is missing the field name. %s', 'pop-component-model'),
                $property,
                $errorMessageEnd
            );
        }

        // If it has only "[" or "]" but not the other one, it's an error
        if (($bookmarkClosingSymbolPos === false && $bookmarkOpeningSymbolPos !== false) || ($bookmarkClosingSymbolPos !== false && $bookmarkOpeningSymbolPos === false)) {
            return sprintf(
                $this->translationAPI->__('Bookmark \'%s\' must start with symbol \'%s\' and end with symbol \'%s\'. %s', 'pop-component-model'),
                $property,
                QuerySyntax::SYMBOL_BOOKMARK_OPENING,
                QuerySyntax::SYMBOL_BOOKMARK_CLOSING,
                $errorMessageEnd
            );
        }

        // Field Directives
        list(
            $fieldDirectivesOpeningSymbolPos,
            $fieldDirectivesClosingSymbolPos
        ) = QueryHelpers::listFieldDirectivesSymbolPositions($property);

        // If it has "<" from the very beginning, then there's no fieldName, it's an error
        if ($fieldDirectivesOpeningSymbolPos === 0) {
            return sprintf(
                $this->translationAPI->__('Property \'%s\' is missing the field name. %s', 'pop-component-model'),
                $property,
                $errorMessageEnd
            );
        }

        // If it has only "[" or "]" but not the other one, it's an error
        if (($fieldDirectivesClosingSymbolPos === false && $fieldDirectivesOpeningSymbolPos !== false) || ($fieldDirectivesClosingSymbolPos !== false && $fieldDirectivesOpeningSymbolPos === false)) {
            return sprintf(
                $this->translationAPI->__('Directive \'%s\' must start with symbol \'%s\' and end with symbol \'%s\'. %s', 'pop-component-model'),
                $property,
                QuerySyntax::SYMBOL_FIELDDIRECTIVE_OPENING,
                QuerySyntax::SYMBOL_FIELDDIRECTIVE_CLOSING,
                $errorMessageEnd
            );
        }

        // --------------------------------------------------------
        // Validate correctness of order of elements: ...(...)[...]<...>
        // (0. field name, 1. field args, 2. bookmarks, 3. skip output if null?, 4. field directives)
        // --------------------------------------------------------
        if ($fieldArgsOpeningSymbolPos !== false) {
            if ($fieldArgsOpeningSymbolPos == 0) {
                return sprintf(
                    $this->translationAPI->__('Name is missing in property \'%s\'. %s', 'pop-component-model'),
                    $property,
                    $errorMessageEnd
                );
            }
        }

        // After the ")", it must be either the end, "@", "[", "?" or "<"
        $aliasSymbolPos = QueryHelpers::findFieldAliasSymbolPosition($property);
        $skipOutputIfNullSymbolPos = QueryHelpers::findSkipOutputIfNullSymbolPosition($property);
        if ($fieldArgsClosingSymbolPos !== false) {
            $nextCharPos = $fieldArgsClosingSymbolPos+strlen(QuerySyntax::SYMBOL_FIELDARGS_CLOSING);
            if (!(
                // It's in the last position
                ($fieldArgsClosingSymbolPos == strlen($property)-strlen(QuerySyntax::SYMBOL_FIELDARGS_CLOSING)) ||
                // Next comes "["
                ($bookmarkOpeningSymbolPos !== false && $bookmarkOpeningSymbolPos == $nextCharPos) ||
                // Next comes "@"
                ($aliasSymbolPos !== false && $aliasSymbolPos == $nextCharPos) ||
                // Next comes "?"
                ($skipOutputIfNullSymbolPos !== false && $skipOutputIfNullSymbolPos == $nextCharPos) ||
                // Next comes "<"
                ($fieldDirectivesOpeningSymbolPos !== false && $fieldDirectivesOpeningSymbolPos == $nextCharPos)
            )) {
                return sprintf(
                    $this->translationAPI->__('After \'%s\', property \'%s\' must either end or be followed by \'%s\', \'%s\', \'%s\' or \'%s\'. %s', 'pop-component-model'),
                    QuerySyntax::SYMBOL_FIELDARGS_CLOSING,
                    $property,
                    QuerySyntax::SYMBOL_BOOKMARK_OPENING,
                    QuerySyntax::SYMBOL_FIELDALIAS_PREFIX,
                    QuerySyntax::SYMBOL_SKIPOUTPUTIFNULL,
                    QuerySyntax::SYMBOL_FIELDDIRECTIVE_OPENING,
                    $errorMessageEnd
                );
            }
        }

        // After the "]", it must be either the end, "?" or "<"
        if ($bookmarkClosingSymbolPos !== false) {
            $nextCharPos = $bookmarkClosingSymbolPos+strlen(QuerySyntax::SYMBOL_FIELDARGS_CLOSING);
            if (!(
                // It's in the last position
                ($bookmarkClosingSymbolPos == strlen($property)-strlen(QuerySyntax::SYMBOL_BOOKMARK_CLOSING)) ||
                // Next comes "?"
                ($skipOutputIfNullSymbolPos !== false && $skipOutputIfNullSymbolPos == $nextCharPos) ||
                // Next comes "<"
                ($fieldDirectivesOpeningSymbolPos !== false && $fieldDirectivesOpeningSymbolPos == $nextCharPos)
            )) {
                return sprintf(
                    $this->translationAPI->__('After \'%s\', property \'%s\' must either end or be followed by \'%s\' or \'%s\'. %s', 'pop-component-model'),
                    QuerySyntax::SYMBOL_BOOKMARK_CLOSING,
                    $property,
                    QuerySyntax::SYMBOL_SKIPOUTPUTIFNULL,
                    QuerySyntax::SYMBOL_FIELDDIRECTIVE_OPENING,
                    $errorMessageEnd
                );
            }
        }

        // After the "?", it must be either the end or "<"
        if ($skipOutputIfNullSymbolPos !== false) {
            $nextCharPos = $skipOutputIfNullSymbolPos+strlen(QuerySyntax::SYMBOL_SKIPOUTPUTIFNULL);
            if (!(
                // It's in the last position
                ($skipOutputIfNullSymbolPos == strlen($property)-strlen(QuerySyntax::SYMBOL_SKIPOUTPUTIFNULL)) ||
                // Next comes "<"
                ($fieldDirectivesOpeningSymbolPos !== false && $fieldDirectivesOpeningSymbolPos == $nextCharPos)
            )) {
                return sprintf(
                    $this->translationAPI->__('After \'%s\', property \'%s\' must either end or be followed by \'%s\'. %s', 'pop-component-model'),
                    QuerySyntax::SYMBOL_SKIPOUTPUTIFNULL,
                    $property,
                    QuerySyntax::SYMBOL_FIELDDIRECTIVE_OPENING,
                    $errorMessageEnd
                );
            }
        }

        // After the ">", it must be the end
        if ($fieldDirectivesClosingSymbolPos !== false) {
            if (!(
                // It's in the last position
                ($fieldDirectivesClosingSymbolPos == strlen($property)-strlen(QuerySyntax::SYMBOL_FIELDDIRECTIVE_CLOSING))
            )) {
                return sprintf(
                    $this->translationAPI->__('After \'%s\', property \'%s\' must end (there cannot be any extra character). %s', 'pop-component-model'),
                    QuerySyntax::SYMBOL_FIELDDIRECTIVE_CLOSING,
                    $property,
                    $errorMessageEnd
                );
            }
        }

        return [
            $fieldArgsOpeningSymbolPos,
            $fieldArgsClosingSymbolPos,
            $aliasSymbolPos,
            $bookmarkOpeningSymbolPos,
            $bookmarkClosingSymbolPos,
            $skipOutputIfNullSymbolPos,
            $fieldDirectivesOpeningSymbolPos,
            $fieldDirectivesClosingSymbolPos,
        ];
    }
}
