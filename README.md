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

## Usage

### Query data

1. Select the endpoint:

    - GraphQL: [/api/graphql/](https://nextapi.getpop.org/api/graphql/)
    - REST: [/api/rest/](https://nextapi.getpop.org/api/rest/)
    - PoP native: [/api/](https://nextapi.getpop.org/api/)

2. Add your query under URL parameter `fields`

    [/api/graphql/?fields=posts.id|title|author.id|name](https://nextapi.getpop.org/api/graphql/?fields=posts.id|title|author.id|name)

### Visualize the schema

The schema listing all the available fields is available under field `__schema`:

[/api/graphql/?fields=__schema](https://nextapi.getpop.org/api/graphql/?fields=__schema)

### Query syntax

PoP accepts the query through parameter `fields`, with a syntax similar to that from GraphQL but provided as a single-line query, in which:

- Fields are separated with `,`
- The field path is delineated with `.`
- Properties on a node are grouped with `|`
<!--
- Field arguments are surrounded by `(...)`, and separated by `;`
- Bookmarks are surrounded by `[...]`
- Aliases are prepended with `@`
- Variables are prefixed with `$`
- Fragments are prefixed with `--`
- Directives are surrounded by `<...>`, inside which they follow the same syntax as a field
-->

For instance, the following GraphQL query:

```graphql
query {
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
```

Is equivalent to the following query:

```
id|title|url|content,comments.id|content|date,comments.author.id|name|url,comments.author.posts.id|title|url
```

Our endpoint therefore becomes:

[/api/graphql/?fields=id|title|url|content,comments.id|content|date,comments.author.id|name|url,comments.author.posts.id|title|url](https://nextapi.getpop.org/api/rest/?fields=id|title|url|content,comments.id|content|date,comments.author.id|name|url,comments.author.posts.id|title|url)

#### Field arguments

A field can have arguments: an array of `key:value` properties, appended next to the field name enclosed with `()` and separated with `;`, which modify the output from the field. 

For instance, an author's posts can be ordered (`posts(order:title|asc)`) and limited to a string and number of results (`posts(searchfor:template;limit:3)`), a date can be printed with a specific format (`posts.date(format:d/m/Y)`), the featured image can be retrieved for a specific size (`featuredimage-props(size:large)`), and others.

#### Aliases

Description coming soon...

#### Bookmarks

Description coming soon...

#### Variables

Description coming soon...

#### Fragments

Description coming soon...


## Features

### Same advantages as both GraphQL and REST

The PoP API provides the benefits of both REST and GraphQL APIs, at the same time:

- 🤘🏽 No over/under-fetching data (as in GraphQL)
- 🤘🏽 Shape of the response mirrors the query (as in GraphQL)
- 🤘🏽 Passing parameters to the query nodes, at any depth, for filtering/pagination/formatting/etc (as in GraphQL)
- 💪🏻 Server-side caching (as in REST)
- 💪🏻 Secure: Not chance of Denial of Service attacks (as in REST)
- 💪🏻 Provide default data when no query is provided (as in REST)

### Generate GraphQL and REST-compatible responses

The response of the API can use both the REST and GraphQL formats, simply by installing the corresponding extension:

- [PoP REST API](https://github.com/getpop/api-rest)
- [PoP GraphQL API](https://github.com/getpop/api-graphql)

### Additional features, unsupported by both GraphQL and REST

The PoP API provides several features that neither REST or GraphQL support:

- ✅ URL-based queries ([example](https://nextapi.getpop.org/api/rest/?fields=posts.id|title|date|content))
- ✅ Operators: `AND`, `OR`, `NOT`, etc ([example](https://nextapi.getpop.org/api/rest/?fields=posts.id|title|not(field:is-status(status:publish))))
- ✅ Field composition: Query fields inside of fields ([example](https://nextapi.getpop.org/api/rest/?fields=posts.id|title|or(fields:is-status(status:publish),is-status(status:draft))))
- ✅ Access context variables ([example](https://nextapi.getpop.org/api/rest/?fields=context), [example](https://nextapi.getpop.org/api/rest/?fields=var(name:output)))
- ✅ Lower time complexity to execute queries (see below)
- Others (coming soon)

<!--
### Examples

**REST:**

- [Retrieving default data (implicit fields)](https://nextapi.getpop.org/en/posts/api/?datastructure=rest)
- [Retrieving client-custom data (explicit fields)](https://nextapi.getpop.org/en/posts/api/?datastructure=rest&fields=id|title|url|content,comments.id|content|date,comments.author.id|name|url,comments.author.posts.id|title|url)

**GraphQL:**

- [Retrieving client-custom data](https://nextapi.getpop.org/en/posts/api/?datastructure=graphql&fields=id|title|url|content,comments.id|content|date,comments.author.id|name|url,comments.author.posts.id|title|url)
- [Returning an author's posts that contain a certain string](https://nextapi.getpop.org/author/themedemos/api/?datastructure=graphql&fields=id|name,posts(searchfor:template).id|title|url)

**Note:** Setting parameter `datastructure` to either `graphql` or `rest` formats the response for the corresponding API. If `datastructure` is left empty, the response is the native one for PoP: a relational database structure (see "Data API layer" section below).
-->

## Time complexity of executing queries

PoP fetches a piece data from the database only once, even if the query fetches it several times. The query can include any number of nested relationships, and these are resolved with linear complexity time: worst case of `O(n^2)` (where `n` is the number of nodes that switch domain plus the number of retrieved results), and average case of `O(n)`.

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

<!--
## Architecture Design and Implementation

### Custom-Querying API

Similar to GraphQL, PoP also provides an API which can be queried from the client, which retrieves exactly the data fields which are requested and nothing more. The custom-querying API is accessed by appending `/api` to the URL and adding parameter `fields` with the list of fields to retrieve from the queried resources. 

For instance, the following link fetches a collection of posts. By adding `fields=title,content,datetime` we retrieve only these items:

- Original: https://nextapi.getpop.org/posts/?output=json
- Custom-querying: https://nextapi.getpop.org/posts/api/?fields=id|title|content|datetime

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

PoP, instead, uses a query translated into its corresponding “dot syntax” expression, which can then be supplied through parameter fields. Querying on a “post” resource, this value is:

```properties
fields=title,content,comments.content,comments.date,comments.author.name,comments.author.url
```

Or it can be simplified, using | to group all fields applied to the same resource:

```properties
fields=title|content,comments.content|date,comments.author.name|url
```

When executing this query on a [single post](https://nextapi.getpop.org/posts/a-lovely-tango/api/?fields=id|title|content,comments.content|date,comments.author.name|url) we obtain exactly the required data for all involved resources:

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

For the **GraphQL-compatible API**, add parameter `datastructure=graphql` to the endpoint URL, and parameter `fields` with the fields to retrieve (using a [custom dot notation](https://github.com/leoloso/PoP#defining-what-data-to-fetch-through-fields)) from the list of fields defined below. In addition, a field may have [arguments](https://github.com/leoloso/PoP#field-arguments) to modify its results.

For the **PoP native API**, add parameter `fields` to the endpoint URL, similar to GraphQL.

----

Currently, the API supports the following entities and fields:

### Posts

**Endpoints**:

_List of posts:_

- **REST:** [/posts/api/?datastructure=rest](https://nextapi.getpop.org/posts/api/?datastructure=rest)
- **GraphQL:** [/posts/api/?datastructure=graphql](https://nextapi.getpop.org/posts/api/?datastructure=graphql&fields=id|title|url)
- **PoP native:** [/posts/api/](https://nextapi.getpop.org/posts/api/?fields=id|title|url)

_Single post:_

- **REST:** [/{SINGLE-POST-URL}/api/?datastructure=rest](https://nextapi.getpop.org/2013/01/11/markup-html-tags-and-formatting/api/?datastructure=rest) 
- **GraphQL:** [/{SINGLE-POST-URL}/api/?datastructure=graphql](https://nextapi.getpop.org/2013/01/11/markup-html-tags-and-formatting/api/?datastructure=graphql&fields=id|title|date|content)
- **PoP native:** [/{SINGLE-POST-URL}/api/](https://nextapi.getpop.org/2013/01/11/markup-html-tags-and-formatting/api/?fields=id|title|date|content)

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

_List of posts + author data:_<br/>[id|title|date|url,author.id|name|url,author.posts.id|title|url](https://nextapi.getpop.org/posts/api/?datastructure=graphql&fields=id|title|date|url,author.id|name|url,author.posts.id|title|url)

_Single post + tags (ordered by slug), comments and comment author info:_<br/>[id|title|cat-slugs,tags(order:slug|asc).id|slug|count|url,comments.id|content|date,comments.author.id|name|url](https://nextapi.getpop.org/2013/01/11/markup-html-tags-and-formatting/api/?datastructure=graphql&fields=id|title|cat-slugs,tags(order:slug|asc).id|slug|count|url,comments.id|content|date,comments.author.id|name|url)

### Users

**Endpoints:**

_List of users:_

- **REST:** [/users/api/?datastructure=rest](https://nextapi.getpop.org/users/api/?datastructure=rest)
- **GraphQL:** [/users/api/?datastructure=graphql](https://nextapi.getpop.org/users/api/?datastructure=graphql&fields=id|name|url)
- **PoP native:** [/users/api/](https://nextapi.getpop.org/users/api/?fields=id|name|url)

_Author:_

- **REST:** [/{AUTHOR-URL}/api/?datastructure=rest](https://nextapi.getpop.org/author/themedemos/api/?datastructure=rest) 
- **GraphQL:** [/{AUTHOR-URL}/api/?datastructure=graphql](https://nextapi.getpop.org/author/themedemos/api/?datastructure=graphql&fields=id|name|description)
- **PoP native:** [/{AUTHOR-URL}/api/](https://nextapi.getpop.org/author/themedemos/api/?fields=id|name|description)

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

_List of users + up to 2 posts for each, ordered by date:_<br/>[id|name|url,posts(limit:2;order:date|desc).id|title|url|date](https://nextapi.getpop.org/users/api/?datastructure=graphql&fields=id|name|url,posts(limit:2;order:date|desc).id|title|url|date)

_Author + all posts, with their tags and comments, and the comment author info:_<br/>[id|name|url,posts.id|title,posts.tags.id|slug|count|url,posts.comments.id|content|date,posts.comments.author.id|name](https://nextapi.getpop.org/author/themedemos/api/?datastructure=graphql&fields=id|name|url,posts.id|title,posts.tags.id|slug|count|url,posts.comments.id|content|date,posts.comments.author.id|name)

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

_Single post's comments:_<br/>[comments.id|content|date|type|approved|author-name|author-url|author-email](https://nextapi.getpop.org/2013/01/11/markup-html-tags-and-formatting/api/?datastructure=graphql&fields=comments.id|content|date|type|approved|author-name|author-url|author-email)

### Tags

**Endpoints:**

_List of tags:_

- **REST:** [/tags/api/?datastructure=rest](https://nextapi.getpop.org/tags/api/?datastructure=rest)
- **GraphQL:** [/tags/api/?datastructure=graphql](https://nextapi.getpop.org/tags/api/?datastructure=graphql&fields=id|slug|count|url)
- **PoP native:** [/tags/api/](https://nextapi.getpop.org/tags/api/?fields=id|slug|count|url)

_Tag:_

- **REST:** [/{TAG-URL}/api/?datastructure=rest](https://nextapi.getpop.org/tag/html/api/?datastructure=rest) 
- **GraphQL:** [/{TAG-URL}/api/?datastructure=graphql](https://nextapi.getpop.org/tag/html/api/?datastructure=graphql&fields=id|name|slug|count)
- **PoP native:** [/{TAG-URL}/api/](https://nextapi.getpop.org/tag/html/api/?fields=id|name|slug|count)

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

_List of tags + all their posts filtered by date and ordered by title, their comments, and the comment authors:_<br/>[id|slug|count|url,posts(date-from:2009-09-15;date-to:2010-07-10;order:title|asc).id|title|url|date](https://nextapi.getpop.org/tags/api/?datastructure=graphql&fields=id|slug|count|url,posts(date-from:2009-09-15;date-to:2010-07-10;order:title|asc).id|title|url|date)

_Tag + all their posts, their comments and the comment authors:_<br/>[id|slug|count|url,posts.id|title,posts.comments.content|date,posts.comments.author.id|name|url](https://nextapi.getpop.org/tag/html/api/?datastructure=graphql&fields=id|slug|count|url,posts.id|title,posts.comments.content|date,posts.comments.author.id|name|url)

### Pages

**Endpoints:**

_Page:_

- **REST:** [/{PAGE-URL}/api/?datastructure=rest](https://nextapi.getpop.org/about/api/?datastructure=rest)
- **GraphQL:** [/{PAGE-URL}/api/?datastructure=graphql](https://nextapi.getpop.org/about/api/?datastructure=graphql&fields=id|title|content)
- **PoP native:** [/{PAGE-URL}/api/](https://nextapi.getpop.org/about/api/?fields=id|title|content)

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

_Page:_<br/>[id|title|content|url](https://nextapi.getpop.org/about/api/?datastructure=graphql&fields=id|title|content|url)
-->
