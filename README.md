# API

<!--
[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-travis]][link-travis]
[![Coverage Status][ico-scrutinizer]][link-scrutinizer]
[![Quality Score][ico-code-quality]][link-code-quality]
[![Total Downloads][ico-downloads]][link-downloads]
-->

API for PoP for fetching and posting data. By default, it retrieves the data using PoP's native format. Through extension packages, the API can also add compatibility for GraphQL (through [GraphQL API](https://github.com/getpop/api-graphql)) and REST (through [REST API](https://github.com/getpop/api-rest)).

## Install

Via Composer

``` bash
$ composer require getpop/api dev-master
```

**Note:** Your `composer.json` file must have the configuration below to accept minimum stability `"dev"` (there are no releases for PoP yet, and the code is installed directly from the `master` branch):

```javascript
{
    ...
    "minimum-stability": "dev",
    "prefer-stable": true,
    ...
}
```

### Enable pretty permalinks

Add the following code in the `.htaccess` file to enable API endpoint `/api/`:

```apache
<IfModule mod_rewrite.c>
RewriteEngine On
RewriteBase /

# Rewrite from /some-url/api/ to /some-url/?scheme=api
RewriteCond %{SCRIPT_FILENAME} !-d
RewriteCond %{SCRIPT_FILENAME} !-f
RewriteRule ^(.*)/api/?$ /$1/?scheme=api [L,P,QSA]

# Rewrite from api/ to /?scheme=api
RewriteCond %{SCRIPT_FILENAME} !-d
RewriteCond %{SCRIPT_FILENAME} !-f
RewriteRule ^api/?$ /?scheme=api [L,P,QSA]
</IfModule>
```

## Usage

1. Add the API endpoint to any URL:

    - GraphQL: `.../api/graphql/`
    - REST: `.../api/rest/`
    - PoP native: `.../api/`

2. Add your query under URL parameter `query`

In the homepage, the initial selected resource on which the query is applied is `"root"`: 

- [/api/graphql/?query=posts.id|title|author.id|name](https://nextapi.getpop.org/api/graphql/?query=posts.id|title|author.id|name)

Otherwise, the selected resource, or set of resources, is the corresponding one to the URL, such as a [single post](https://nextapi.getpop.org/2013/01/11/markup-html-tags-and-formatting/) or a [collection of posts](https://nextapi.getpop.org/posts/):

- [/2013/01/11/markup-html-tags-and-formatting/api/graphql/?query=id|title|author.id|name](https://nextapi.getpop.org/2013/01/11/markup-html-tags-and-formatting/api/graphql/?query=id|title|author.id|name)
- [/posts/api/graphql/?query=id|title|author.id|name](https://nextapi.getpop.org/posts/api/graphql/?query=id|title|author.id|name)

> Note: to enable GraphQL and/or REST endpoints, the corresponding package must be installed: [GraphQL package](https://github.com/getpop/api-graphql), [REST package](https://github.com/getpop/api-rest) 

### Visualize the schema

To visualize all available fields, use query field `__schema` from the root: 

- [/api/graphql/?query=__schema](https://nextapi.getpop.org/api/graphql/?query=__schema)


## Features

### Same advantages as both GraphQL and REST

The PoP API provides the benefits of both REST and GraphQL APIs, at the same time:

_From GraphQL:_

- ✅ No over/under-fetching data
- ✅ Shape of the response mirrors query
- ✅ Field arguments (for filtering/pagination/formatting/etc)

_From REST:_

- ✅ Server-side caching
- ✅ Secure: Not chance of Denial of Service attacks
- ✅ Pre-define fields

### Generate GraphQL and REST-compatible responses

The response of the API can use both the REST and GraphQL formats, simply by installing the corresponding extension:

- [PoP GraphQL API](https://github.com/getpop/api-graphql)
- [PoP REST API](https://github.com/getpop/api-rest)

### Additional features, unsupported by both GraphQL and REST

The PoP API provides several features that neither REST or GraphQL support:

- ✅ URL-based queries ([example](https://nextapi.getpop.org/api/graphql/?query=posts.id|title|date|content))
- ✅ Operators: `AND`, `OR`, `NOT`, etc ([example](https://nextapi.getpop.org/api/graphql/?query=posts.id|title|not(is-status(status:publish))))
- ✅ Field composition: Query fields inside of fields ([example](https://nextapi.getpop.org/api/graphql/?query=posts.id|title|or([is-status(status:publish),is-status(status:draft)])))
- ✅ Access context variables ([example](https://nextapi.getpop.org/api/graphql/?query=context), [example](https://nextapi.getpop.org/api/graphql/?query=var(name:output)))
- ✅ Lower time complexity to execute queries (see below)
- ✅ Complex query resolution without server-side coding (coming soon)
- Others (coming soon)


## Query syntax

PoP accepts the query through parameter `query`, with a syntax similar to that from GraphQL but provided as a single-line query, in which:

- Fields are separated with `,`
- The field path is delineated with `.`
- Properties on a node are grouped with `|`

For instance, the following GraphQL query:

```graphql
query {
  posts {
    id
    title
    url
    content
    comments {
        id
        content
        date
        author {
        id
        name
        url
        posts {
            id
            title
            url
        }
      }
    }
  }
}
```

Is equivalent to the following single-line query:

```
posts.id|title|url|content|comments.id|content|date|author.id|name|url|posts.id|title|url
```

Our endpoint therefore becomes:

[/api/graphql/?query=posts.id|title|url|content|comments.id|content|date|author.id|name|url|posts.id|title|url](https://nextapi.getpop.org/api/graphql/?query=posts.id|title|url|content|comments.id|content|date|author.id|name|url|posts.id|title|url)

### Field arguments

A field can have arguments: An array of `name:value` properties, appended next to the field name enclosed with `()` and separated with `,`, which modify the output (results, formatting, etc) from the field. 

Examples: 

- Order posts by title: [posts(order:title|asc)](https://nextapi.getpop.org/api/graphql/?query=posts(order:title|asc).id|title|url|date)
- Search "template" and limit it to 3 results: [posts(searchfor:template,limit:3)](https://nextapi.getpop.org/api/graphql/?query=posts(searchfor:template,limit:3).id|title|url|date)
- Format a date: [posts.date(format:d/m/Y)](https://nextapi.getpop.org/api/graphql/?query=posts.id|title|url|date(format:d/m/Y))

Argument names can also be deduced from the schema. Then, only the `value` needs be provided: 

- Format a date: [posts.date(d/m/Y)](https://nextapi.getpop.org/api/graphql/?query=posts.id|title|url|date(d/m/Y))

### Aliases

A field is, by default, output under its own definition (for instance, [posts(order:title|asc)](https://nextapi.getpop.org/api/graphql/?query=posts(order:title|asc).id|title|url|date) is output under property `posts(order:title|asc)`). An “alias”, which is a property name prepended with `@`, allows to change this property to anything we desire.

Examples:

- [posts(order:title|asc)@orderedposts](https://nextapi.getpop.org/api/graphql/?query=posts(order:title|asc)@orderedposts.id|title|url|date)
- [posts.date(format:d/m/Y)@formatteddate](https://nextapi.getpop.org/api/graphql/?query=posts.id|title|url|date(format:d/m/Y)@formatteddate)

### Bookmarks

The query allows to iterate down a path using `.` (for instance: [posts.comments.author.id|name](https://nextapi.getpop.org/api/graphql/?query=posts.comments.author.id|name)). We can assign a “bookmark” to any specific level, as to start iterating from there once again. To use it, we place any name surrounded by `[...]` after the path level, and then the same name, also surrounded by `[...]`, as the root path level to iterate from there.

Example:

- [posts.comments[comments].author.id|name,[comments].post.id|title](https://nextapi.getpop.org/api/graphql/?query=posts.comments[comments].author.id|name,[comments].post.id|title)

### Bookmark with Alias

Bookmarks can be combined with aliases by adding `@` to the name surrounded by `[...]`.

Example:

- [posts.comments[@postcomments].author.id|name,[postcomments].post.id|title](https://nextapi.getpop.org/api/graphql/?query=posts.comments[@postcomments].author.id|name,[postcomments].post.id|title)

### Variables

We can use “variables”, which are names prepended with `$`, to pass field argument values defined through URL parameters: Either under URL parameter with the variable name, or under URL parameter `variables` and then the variable name.

Example:

- [posts(searchfor:$term,limit:$limit).id|title&variables[limit]=3&term=template](https://nextapi.getpop.org/api/graphql/?query=posts(searchfor:$term,limit:$limit).id|title&variables[limit]=3&term=template)

### Fragments

We can use “fragments”, which must be prepended using `--`, to re-use query sections.

Example:

- [posts(limit:2).--fr1,users(id:1).posts.--fr1&fragments[fr1]=id|author.posts(limit:1).id|title](https://nextapi.getpop.org/api/graphql/?query=posts(limit:2).--fr1,users(id:1).posts.--fr1&fragments[fr1]=id|author.posts(limit:1).id|title)

### Directives

A “directive” enables to modify the response from one or many fields, in any way. They must be surrounded by `<...>` and, if more than one directive is provided, separated by `,`. A directive can also receive arguments, with a syntax similar to field arguments: they are surrounded by `(...)`, and its pairs of `key:value` are separated by `,`.

Examples:

- [posts.id|title|url<include(if:$include)>&variables[include]=true](https://nextapi.getpop.org/api/graphql/?query=posts.id|title|url<include(if:$include)>&variables[include]=true)
- [posts.id|title|url<include(if:$include)>&variables[include]=](https://nextapi.getpop.org/api/graphql/?query=posts.id|title|url<include(if:$include)>&variables[include]=)

<!--
### Examples

**REST:**

- [Retrieving default data (implicit fields)](https://nextapi.getpop.org/en/posts/api/?datastructure=rest)
- [Retrieving client-custom data (explicit fields)](https://nextapi.getpop.org/en/posts/api/?datastructure=rest&query=id|title|url|content,comments.id|content|date,comments.author.id|name|url,comments.author.posts.id|title|url)

**GraphQL:**

- [Retrieving client-custom data](https://nextapi.getpop.org/en/posts/api/?datastructure=graphql&query=id|title|url|content,comments.id|content|date,comments.author.id|name|url,comments.author.posts.id|title|url)
- [Returning an author's posts that contain a certain string](https://nextapi.getpop.org/author/themedemos/api/?datastructure=graphql&query=id|name,posts(searchfor:template).id|title|url)

**Note:** Setting parameter `datastructure` to either `graphql` or `rest` formats the response for the corresponding API. If `datastructure` is left empty, the response is the native one for PoP: a relational database structure (see "Data API layer" section below).
-->

## Time complexity of executing queries

PoP fetches a piece data from the database only once, even if the query fetches it several times. The query can include any number of nested relationships, and these are resolved with linear complexity time: worst case of `O(n^2)` (where `n` is the number of nodes that switch domain plus the number of retrieved results), and average case of `O(n)`.

<!--
## Comparison among APIs

REST, GraphQL and PoP native compare like this:

<table>
<thead><th>&nbsp;</th><th>REST</th><th>GraphQL</th><th>PoP</th></thead>
<tr><th>Nature</th><td>Resource-based</td><td>Schema-based</td><td>Component-based</td></tr>
<tr><th>Endpoint</th><td>Custom endpoints based on resources</td><td>1 endpoint for the whole application</td><td>1 endpoint per page, simply adding parameter <code>output=json</code> to the page URL</td></tr>
<tr><th>Retrieved data</th><td>All data for a resource</td><td>All data for all resources in a component</td><td>All data for all resources in a component, for all components in a page</td></tr>
<tr><th>How are data fields retrieved?</th><td>Implicitly: already known on server-side</td><td>Explicitly: only known on client-side</td><td>Both Implicitly and Explicitly are supported (the developer decides)</td></tr>
<tr><th>Time complexity to fetch data</th><td>Constant (O(1))</td><td>At least <a href="https://blog.acolyer.org/2018/05/21/semantics-and-complexity-of-graphql/">Polynomial</a> (O(n^c))</td><td>Linear (O(n))</td></tr>
<tr><th>Can post data?</th><td>Yes</td><td>Yes</td><td>Yes</td></tr>
<tr><th>Can execute any type of other operation (eg: log in user, send an email, etc)?</th><td>No</td><td>No</td><td>Yes</td></tr>
<tr><th>Does it under/over-fetch data?</th><td>Yes</td><td>No</td><td>No</td></tr>
<tr><th>Is data normalized?</th><td>No</td><td>No</td><td>Yes</td></tr>
<tr><th>Support for configuration values?</th><td>No</td><td>No</td><td>Yes</td></tr>
<tr><th>Cacheable on server-side?</th><td>Yes</td><td>No</td><td>Yes</td></tr>
<tr><th>Open to DoS attack?</th><td>No</td><td><a href="https://blog.apollographql.com/securing-your-graphql-api-from-malicious-queries-16130a324a6b">Yes</a></td><td>No</td></tr>
<tr><th>Compatible with the other APIs</th><td>No</td><td>No</a></td><td>Yes</td></tr>
</table>
-->

## Examples

### Queries

Grouping properties: 

- [posts.id|title|url](https://nextapi.getpop.org/api/graphql/?query=posts.id|title|url)

Deep nesting: 

- [posts.id|title|url|comments.id|content|date|author.id|name|url|posts.id|title|url](https://nextapi.getpop.org/api/graphql/?query=posts.id|title|url|comments.id|content|date|author.id|name|url|posts.id|title|url)

Field arguments: 

- [posts(searchfor:template,limit:3).id|title](https://nextapi.getpop.org/api/graphql/?query=posts(searchfor:template,limit:3).id|title)

Variables: 

- [posts(searchfor:$search,limit:$limit).id|title&variables[limit]=3&variables[search]=template](https://nextapi.getpop.org/api/graphql/?query=posts(searchfor:$search,limit:$limit).id|title&variables[limit]=3&variables[search]=template)

Bookmarks: 

- [posts(searchfor:template,limit:3)[searchposts].id|title,[searchposts].author.id|name](https://nextapi.getpop.org/api/graphql/?query=posts(searchfor:template,limit:3)[searchposts].id|title,[searchposts].author.id|name)

Aliases: 

- [posts(searchfor:template,limit:3)@searchposts.id|title](https://nextapi.getpop.org/api/graphql/?query=posts(searchfor:template,limit:3)@searchposts.id|title)

Bookmark + Alias: 

- [posts(searchfor:template,limit:3)[@searchposts].id|title,[searchposts].author.id|name](https://nextapi.getpop.org/api/graphql/?query=posts(searchfor:template,limit:3)[@searchposts].id|title,[searchposts].author.id|name)

Field args: 

- [posts.id|title|is-status(status:draft)|is-status(status:published)](https://nextapi.getpop.org/api/graphql/?query=posts.id|title|is-status(status:draft)|is-status(status:published))

Operators: 

- [posts.id|title|or([is-status(status:draft),is-status(status:published)])](https://nextapi.getpop.org/api/graphql/?query=posts.id|title|or([is-status(status:draft),is-status(status:published)]))

Fragments: 

- [posts.--fr1&fragments[fr1]=id|author.posts(limit:1).id|title](https://nextapi.getpop.org/api/graphql/?query=posts.--fr1&fragments[fr1]=id|author.posts(limit:1).id|title)

Concatenating fragments: 

- [posts.--fr1.--fr2&fragments[fr1]=author.posts(limit:1)&fragments[fr2]=id|title](https://nextapi.getpop.org/api/graphql/?query=posts.--fr1.--fr2&fragments[fr1]=author.posts(limit:1)&fragments[fr2]=id|title)

Fragments inside fragments: 

- [posts.--fr1.--fr2&fragments[fr1]=author.posts(limit:1)&fragments[fr2]=id|title|--fr3&fragments[fr3]=author.id|url](https://nextapi.getpop.org/api/graphql/?query=posts.--fr1.--fr2&fragments[fr1]=author.posts(limit:1)&fragments[fr2]=id|title|--fr3&fragments[fr3]=author.id|url)

Fragments with aliases: 

- [posts.--fr1.--fr2&fragments[fr1]=author.posts(limit:1)@firstpost&fragments[fr2]=id|title](https://nextapi.getpop.org/api/graphql/?query=posts.--fr1.--fr2&fragments[fr1]=author.posts(limit:1)@firstpost&fragments[fr2]=id|title)

Fragments with variables: 

- [posts.--fr1.--fr2&fragments[fr1]=author.posts(limit:$limit)&fragments[fr2]=id|title&variables[limit]=1](https://nextapi.getpop.org/api/graphql/?query=posts.--fr1.--fr2&fragments[fr1]=author.posts(limit:$limit)&fragments[fr2]=id|title&variables[limit]=1)

Directives (with variables):

- Include: [posts.id|title|url<include(if:$include)>&variables[include]=true](https://nextapi.getpop.org/api/graphql/?query=posts.id|title|url<include(if:$include)>&variables[include]=true) and [posts.id|title|url<include(if:$include)>&variables[include]=](https://nextapi.getpop.org/api/graphql/?query=posts.id|title|url<include(if:$include)>&variables[include]=)
- Skip: [posts.id|title|url<skip(if:$skip)>&variables[skip]=true](https://nextapi.getpop.org/api/graphql/?query=posts.id|title|url<skip(if:$skip)>&variables[skip]=true) and [posts.id|title|url<skip(if:$skip)>&variables[skip]=](https://nextapi.getpop.org/api/graphql/?query=posts.id|title|url<skip(if:$skip)>&variables[skip]=)

Directives with fields:

- Include: [posts.id|title|comments<include(if:has-comments())>.id|content](https://nextapi.getpop.org/api/graphql/?query=posts.id|title|comments<include(if:has-comments())>.id|content)

Directives with operators and fields:

- Skip: [posts.id|title|comments<skip(if:not(has-comments()))>.id|content](https://nextapi.getpop.org/api/graphql/?query=posts.id|title|comments<skip(if:not(has-comments()))>.id|content)

Overriding fields #1: 

- Normal behaviour: [posts.id|title|excerpt](https://nextapi.getpop.org/api/graphql/?query=posts.id|title|excerpt)
- "Experimental" branch: [posts.id|title|excerpt(branch:experimental,length:30)](https://nextapi.getpop.org/api/graphql/?query=posts.id|title|excerpt(branch:experimental,length:30))

Overriding fields #2: 

- Normal vs "Try new features" behaviour: [posts(limit:2).id|title|content|content(branch:try-new-features,project:block-metadata)](https://nextapi.getpop.org/api/graphql/?query=posts(limit:2).id|title|content|content(branch:try-new-features,project:block-metadata))

Context: 

- [context](https://nextapi.getpop.org/api/graphql/?query=context)

Context variable: 

- [var(name:datastructure)](https://nextapi.getpop.org/api/graphql/?query=var(name:datastructure))

Operator over context variable: 

- [equals(var(name:datastructure),graphql)|equals(var(name:datastructure),rest)](https://nextapi.getpop.org/api/graphql/?query=equals(var(name:datastructure),graphql)|equals(var(name:datastructure),rest))

### Warning messages

Deprecated fields: 

- [posts.id|title|published](https://nextapi.getpop.org/api/graphql/?query=posts.id|title|published)

### Error messages

Schema errors: 

- [posts.id|title|non-existant-field|is-status(status:non-existant-value)|not()](https://nextapi.getpop.org/api/graphql/?query=posts.id|title|non-existant-field|is-status(status:non-existant-value)|not())

Variable errors: 

- [posts(searchfor:$search,limit:$limit).id|title&variables[limit]=3](https://nextapi.getpop.org/api/graphql/?query=posts(searchfor:$search,limit:$limit).id|title&variables[limit]=3)

Bookmark errors: 

- [posts(searchfor:template,limit:3)[searchposts].id|title,[searchpostswithtypo].author.id|name](https://nextapi.getpop.org/api/graphql/?query=posts(searchfor:template,limit:3)[searchposts].id|title,[searchpostswithtypo].author.id|name)

Fragment errors: 

- [posts.--fr1.--fr2&fragments[fr1]=author.posts(limit:1)&fragments[fr2]=id|title|--fr3withtypo&fragments[fr3]=author.id|url](https://nextapi.getpop.org/api/graphql/?query=posts.--fr1.--fr2&fragments[fr1]=author.posts(limit:1)&fragments[fr2]=id|title|--fr3withtypo&fragments[fr3]=author.id|url)

DB errors: 

- coming soon...



<!--
## Architecture Design and Implementation

### Custom-Querying API

Similar to GraphQL, PoP also provides an API which can be queried from the client, which retrieves exactly the data fields which are requested and nothing more. The custom-querying API is accessed by appending `/api` to the URL and adding parameter `query` with the list of fields to retrieve from the queried resources. 

For instance, the following link fetches a collection of posts. By adding `query=title,content,datetime` we retrieve only these items:

- Original: https://nextapi.getpop.org/posts/?output=json
- Custom-querying: https://nextapi.getpop.org/posts/api/?query=id|title|content|datetime

The links above demonstrate fetching data only for the queried resources. What about their relationships? For instance, let’s say that we want to retrieve a list of posts with fields "title" and "content", each post’s comments with fields "content" and "date", and the author of each comment with fields "name" and "url". To achieve this in GraphQL we would implement the following query:

```graph
query {
  post {
    title
    content
    comments {
      content
      date
      author {
        name
        url
      }
    }
  }
}
```

PoP, instead, uses a query translated into its corresponding “dot syntax” expression, which can then be supplied through parameter query. Querying on a “post” resource, this value is:

```properties
query=title,content,comments.content,comments.date,comments.author.name,comments.author.url
```

Or it can be simplified, using | to group all fields applied to the same resource:

```properties
query=title|content,comments.content|date,comments.author.name|url
```

When executing this query on a [single post](https://nextapi.getpop.org/2013/01/11/markup-html-tags-and-formatting/api/?query=id|title|content,comments.content|date,comments.author.name|url) we obtain exactly the required data for all involved resources:

```javascript
{
  "datasetmodulesettings": {
    "dataload-dataquery-singlepost-fields": {
      "dbkeys": {
        "id": "posts",
        "comments": "comments",
        "comments.author": "users"
      }
    }
  },
  "datasetmoduledata": {
    "dataload-dataquery-singlepost-fields": {
      "dbobjectids": [
        23691
      ]
    }
  },
  "databases": {
    "posts": {
      "23691": {
        "id": 23691,
        "title": "A lovely tango",
        "content": "<div class=\"responsiveembed-container\"><iframe width=\"480\" height=\"270\" src=\"https:\\/\\/www.youtube.com\\/embed\\/sxm3Xyutc1s?feature=oembed\" frameborder=\"0\" allowfullscreen><\\/iframe><\\/div>\n",
        "comments": [
          "25094",
          "25164"
        ]
      }
    },
    "comments": {
      "25094": {
        "id": "25094",
        "content": "<p><a class=\"hashtagger-tag\" href=\"https:\\/\\/newapi.getpop.org\\/tags\\/videos\\/\">#videos<\\/a>\\u00a0<a class=\"hashtagger-tag\" href=\"https:\\/\\/newapi.getpop.org\\/tags\\/tango\\/\">#tango<\\/a><\\/p>\n",
        "date": "4 Aug 2016",
        "author": "851"
      },
      "25164": {
        "id": "25164",
        "content": "<p>fjlasdjf;dlsfjdfsj<\\/p>\n",
        "date": "19 Jun 2017",
        "author": "1924"
      }
    },
    "users": {
      "851": {
        "id": 851,
        "name": "Leonardo Losoviz",
        "url": "https:\\/\\/newapi.getpop.org\\/u\\/leo\\/"
      },
      "1924": {
        "id": 1924,
        "name": "leo2",
        "url": "https:\\/\\/newapi.getpop.org\\/u\\/leo2\\/"
      }
    }
  }
}
```

Hence, PoP can query resources in a REST fashion, and specify schema-based queries in a GraphQL fashion, and we will obtain exactly what is required, without over or underfetching data, and normalizing data in the database so that no data is duplicated. The query can include any number of nested relationships, and these are resolved with linear complexity time: worst case of O(n+m), where n is the number of nodes that switch domain (in this case 2: `comments` and `comments.author`) and m is the number of retrieved results (in this case 5: 1 post + 2 comments + 2 users), and average case of O(n).
-->

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Testing

``` bash
$ composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CODE_OF_CONDUCT](CODE_OF_CONDUCT.md) for details.

## Security

If you discover any security related issues, please email leo@getpop.org instead of using the issue tracker.

## Credits

- [Leonardo Losoviz][link-author]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/getpop/api.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/getpop/api/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/getpop/api.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/getpop/api.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/getpop/api.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/getpop/api
[link-travis]: https://travis-ci.org/getpop/api
[link-scrutinizer]: https://scrutinizer-ci.com/g/getpop/api/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/getpop/api
[link-downloads]: https://packagist.org/packages/getpop/api
[link-author]: https://github.com/leoloso
[link-contributors]: ../../contributors



<!--
> **Note:** The usage below belong to [PoP API for WordPress](https://github.com/leoloso/PoP-API-WP). Other configurations (eg: for other CMSs, to set-up a website instead of an API, and others) are coming soon.

For the **REST-compatible API**, add parameter `datastructure=rest` to the endpoint URL. 

For the **GraphQL-compatible API**, add parameter `datastructure=graphql` to the endpoint URL, and parameter `query` with the fields to retrieve (using a [custom dot notation](https://github.com/leoloso/PoP#defining-what-data-to-fetch-through-fields)) from the list of fields defined below. In addition, a field may have [arguments](https://github.com/leoloso/PoP#field-arguments) to modify its results.

For the **PoP native API**, add parameter `query` to the endpoint URL, similar to GraphQL.

----

Currently, the API supports the following entities and fields:

### Posts

**Endpoints**:

_List of posts:_

- **REST:** [/posts/api/?datastructure=rest](https://nextapi.getpop.org/posts/api/?datastructure=rest)
- **GraphQL:** [/posts/api/?datastructure=graphql](https://nextapi.getpop.org/posts/api/?datastructure=graphql&query=id|title|url)
- **PoP native:** [/posts/api/](https://nextapi.getpop.org/posts/api/?query=id|title|url)

_Single post:_

- **REST:** [/{SINGLE-POST-URL}/api/?datastructure=rest](https://nextapi.getpop.org/2013/01/11/markup-html-tags-and-formatting/api/?datastructure=rest) 
- **GraphQL:** [/{SINGLE-POST-URL}/api/?datastructure=graphql](https://nextapi.getpop.org/2013/01/11/markup-html-tags-and-formatting/api/?datastructure=graphql&query=id|title|date|content)
- **PoP native:** [/{SINGLE-POST-URL}/api/](https://nextapi.getpop.org/2013/01/11/markup-html-tags-and-formatting/api/?query=id|title|date|content)

**GraphQL fields:**

<table>
<thead>
<tr><th>Property (arguments)</th><th>Relational (arguments)</th></tr>
</thead>
<tbody>
<tr valign="top"><td>id<br/>post-type<br/>published<br/>not-published<br/>title<br/>content<br/>url<br/>endpoint<br/>excerpt<br/>status<br/>is-draft<br/>date (format)<br/>datetime (format)<br/>comments-url<br/>comments-count<br/>has-comments<br/>published-with-comments<br/>cats<br/>cat<br/>cat-name<br/>cat-slugs<br/>tag-names<br/>has-thumb<br/>featuredimage<br/>featuredimage-props (size)</td><td>comments<br/>tags (limit, offset, order, searchfor)<br/>author</td></tr>
</tbody>
</table>

**Examples:**

_List of posts + author data:_<br/>[id|title|date|url,author.id|name|url,author.posts.id|title|url](https://nextapi.getpop.org/posts/api/?datastructure=graphql&query=id|title|date|url,author.id|name|url,author.posts.id|title|url)

_Single post + tags (ordered by slug), comments and comment author info:_<br/>[id|title|cat-slugs,tags(order:slug|asc).id|slug|count|url,comments.id|content|date,comments.author.id|name|url](https://nextapi.getpop.org/2013/01/11/markup-html-tags-and-formatting/api/?datastructure=graphql&query=id|title|cat-slugs,tags(order:slug|asc).id|slug|count|url,comments.id|content|date,comments.author.id|name|url)

### Users

**Endpoints:**

_List of users:_

- **REST:** [/users/api/?datastructure=rest](https://nextapi.getpop.org/users/api/?datastructure=rest)
- **GraphQL:** [/users/api/?datastructure=graphql](https://nextapi.getpop.org/users/api/?datastructure=graphql&query=id|name|url)
- **PoP native:** [/users/api/](https://nextapi.getpop.org/users/api/?query=id|name|url)

_Author:_

- **REST:** [/{AUTHOR-URL}/api/?datastructure=rest](https://nextapi.getpop.org/author/themedemos/api/?datastructure=rest) 
- **GraphQL:** [/{AUTHOR-URL}/api/?datastructure=graphql](https://nextapi.getpop.org/author/themedemos/api/?datastructure=graphql&query=id|name|description)
- **PoP native:** [/{AUTHOR-URL}/api/](https://nextapi.getpop.org/author/themedemos/api/?query=id|name|description)

**GraphQL fields:**

<table>
<thead>
<tr><th>Property (arguments)</th><th>Relational (arguments)</th></tr>
</thead>
<tbody>
<tr valign="top"><td>id<br/>username<br/>user-nicename<br/>nicename<br/>name<br/>display-name<br/>firstname<br/>lastname<br/>email<br/>url<br/>endpoint<br/>description<br/>website-url</td><td>posts (limit, offset, order, searchfor, date-from, date-to)</td></tr>
</tbody>
</table>

**Examples:**

_List of users + up to 2 posts for each, ordered by date:_<br/>[id|name|url,posts(limit:2;order:date|desc).id|title|url|date](https://nextapi.getpop.org/users/api/?datastructure=graphql&query=id|name|url,posts(limit:2,order:date|desc).id|title|url|date)

_Author + all posts, with their tags and comments, and the comment author info:_<br/>[id|name|url,posts.id|title,posts.tags.id|slug|count|url,posts.comments.id|content|date,posts.comments.author.id|name](https://nextapi.getpop.org/author/themedemos/api/?datastructure=graphql&query=id|name|url,posts.id|title,posts.tags.id|slug|count|url,posts.comments.id|content|date,posts.comments.author.id|name)

### Comments

**GraphQL fields:**

<table>
<thead>
<tr><th>Property (arguments)</th><th>Relational (arguments)</th></tr>
</thead>
<tbody>
<tr valign="top"><td>id<br/>content<br/>author-name<br/>author-url<br/>author-email<br/>approved<br/>type<br/>date (format)</td><td>author<br/>post<br/>post-id<br/>parent</td></tr>
</tbody>
</table>

**Examples:**

_Single post's comments:_<br/>[comments.id|content|date|type|approved|author-name|author-url|author-email](https://nextapi.getpop.org/2013/01/11/markup-html-tags-and-formatting/api/?datastructure=graphql&query=comments.id|content|date|type|approved|author-name|author-url|author-email)

### Tags

**Endpoints:**

_List of tags:_

- **REST:** [/tags/api/?datastructure=rest](https://nextapi.getpop.org/tags/api/?datastructure=rest)
- **GraphQL:** [/tags/api/?datastructure=graphql](https://nextapi.getpop.org/tags/api/?datastructure=graphql&query=id|slug|count|url)
- **PoP native:** [/tags/api/](https://nextapi.getpop.org/tags/api/?query=id|slug|count|url)

_Tag:_

- **REST:** [/{TAG-URL}/api/?datastructure=rest](https://nextapi.getpop.org/tag/html/api/?datastructure=rest) 
- **GraphQL:** [/{TAG-URL}/api/?datastructure=graphql](https://nextapi.getpop.org/tag/html/api/?datastructure=graphql&query=id|name|slug|count)
- **PoP native:** [/{TAG-URL}/api/](https://nextapi.getpop.org/tag/html/api/?query=id|name|slug|count)

**GraphQL fields:**

<table>
<thead>
<tr><th>Property (arguments)</th><th>Relational (arguments)</th></tr>
</thead>
<tbody>
<tr valign="top"><td>id<br/>symbol<br/>symbolnamedescription<br/>namedescription<br/>url<br/>endpoint<br/>symbolname<br/>name<br/>slug<br/>term_group<br/>term_taxonomy_id<br/>taxonomy<br/>description<br/>count</td><td>parent<br/>posts (limit, offset, order, searchfor, date-from, date-to)</td></tr>
</tbody>
</table>

**Examples:**

_List of tags + all their posts filtered by date and ordered by title, their comments, and the comment authors:_<br/>[id|slug|count|url,posts(date-from:2009-09-15;date-to:2010-07-10;order:title|asc).id|title|url|date](https://nextapi.getpop.org/tags/api/?datastructure=graphql&query=id|slug|count|url,posts(date-from:2009-09-15,date-to:2010-07-10,order:title|asc).id|title|url|date)

_Tag + all their posts, their comments and the comment authors:_<br/>[id|slug|count|url,posts.id|title,posts.comments.content|date,posts.comments.author.id|name|url](https://nextapi.getpop.org/tag/html/api/?datastructure=graphql&query=id|slug|count|url,posts.id|title,posts.comments.content|date,posts.comments.author.id|name|url)

### Pages

**Endpoints:**

_Page:_

- **REST:** [/{PAGE-URL}/api/?datastructure=rest](https://nextapi.getpop.org/about/api/?datastructure=rest)
- **GraphQL:** [/{PAGE-URL}/api/?datastructure=graphql](https://nextapi.getpop.org/about/api/?datastructure=graphql&query=id|title|content)
- **PoP native:** [/{PAGE-URL}/api/](https://nextapi.getpop.org/about/api/?query=id|title|content)

**GraphQL fields:**

<table>
<thead>
<tr><th>Property (arguments)</th><th>Relational (arguments)</th></tr>
</thead>
<tbody>
<tr valign="top"><td>id<br/>title<br/>content<br/>url</td><td>&nbsp;</td></tr>
</tbody>
</table>

**Examples:**

_Page:_<br/>[id|title|content|url](https://nextapi.getpop.org/about/api/?datastructure=graphql&query=id|title|content|url)
-->
