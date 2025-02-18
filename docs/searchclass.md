# Search

This class allows you to perform search operations.


## Initiation


```php
require_once __DIR__ . '/vendor/autoload.php';
use Manticoresearch\Client;
use Manticoresearch\Search;
$config = ['host' => '127.0.0.1', 'port' => '9308'];
$client = new Client($config);
$search = new Search($client);
```

## Set the index
```php
$search->setIndex('indexname');
```


## Performing a search

All methods of the `Search` class can be chained.

When all the search conditions and options are set, `get()` is called to process and query the search engine.

The `get()` method returns the results as a [ResultSet](searchresults.md#resultset-object) object.

### search()

This method accepts either a full-text match string or a [BoolQuery](query.md#boolquery) object.

The full Manticore query syntax (https://manual.manticoresearch.com/Searching/Full_text_matching/Operators) is supported.

```php
$search->search('find me')->get();
```

It returns a [ResultSet](searchresults.md#resultset-object) object.

### match()

`Match` is a simplified search method. The query string is interpreted as a bag of words with OR as the default operator.

The first parameter can be a query string:

```php
$search->match('find me fast');
```
or an array containing the query string and the operator:
```php
$search->match(['query'=>'find me fast','operator'=>'and']);
```
If the match should be restricted to one or more `text` fields, they can be set in a second argument:

```php
$search->match('find me fast','title,long_title');
```

### limit() and offset()

Set limit and offset for the result set:

```php
$search->limit(24)->offset(12);
```

### maxMatches()

Set [max_matches](https://mnt.cr/max_matches) for the search.

```php
$search->limit(10000)->maxMatches(10000);
```

### filter(), orFilter() and notFilter()

Allow adding an attribute filter.

It can expect 3 parameters for filtering an attribute:

- attribute name. It can also be an alias of an expression;
- operator. Accepted operators are `range`, `lt`, `lte`, `gt`,  `gte`, `equals`, `in`;
- values for filtering. It can be an array or a single value. Currently, filters support integer, float, and string values.
- filtering condition. It can accept one of `AND`, `OR`, `NOT` values. Set to `AND` by default.

`notFilter()` executes a negation of the operator. Alternatively, the `NOT` filtering condition can be used.

`orFilter()` executes logical disjunction in case of multiple filters. Alternatively, the `OR` filtering condition can be used.

```php
$search->filter('year', 'equals', 2000);
$search->filter('year', 'lte', 2000);
$search->filter('year', 'range', [1960,1992]);
$search->filter('year', 'in', [1960,1975,1990]);
```

```php
$search->filter('year', 'equals', 2000, 'OR');
$search->filter('year', 'equals', 2002, 'OR');

$search->filter('year', 'range', [1960,1992], 'OR');
$search->filter('year', 'range', [1995,2000], 'OR');

$search->orFilter('year', 'equals', 2000);
$search->orFilter('year', 'equals', 2002);

$search->orFilter('year', 'range', [1960,1992]);
$search->orFilter('year', 'range', [1995,2000]);
```

```php
$search->filter('year', 'equals', 2000, 'NOT');
$search->filter('year', 'lte', 1995, 'NOT');

$search->notFilter('year', 'equals', 2000);
$search->notFilter('year', 'lte', 1995);

```


Note that the `equals` operator can be omitted, and the filter function can be called only with the `value` parameter, as shown in the example below:

```php
$search->filter('year', 2000);
```

The functions can also accept a single parameter as a filter class like `Range()`, `Equals()`, or `Distance()`.

```php
$search->filter(new Range('year', ['lte' => 2020]));
```

### sort()

Adds a sorting rule. The sorting rules will be applied in the order they are added.

It can accept two parameters:

- attribute or alias name
- direction of sorting; can be `asc` or `desc`

If the attribute is a MVA (multi-valued attribute), a third parameter can be used to set which value to choose from the list:
- mode can be `min` or `max`

```php
$search->sort('name','asc');
$search->sort('tags','asc','max');
```

`Sort` can also accept the first argument as an array with key-value pairs as attribute -> direction:

```php
$search->sort(['name'=>'asc']);
````
By default, rules are added to the existing ones. If the second argument is set, the input array will not be added but will replace an existing rule set.

```php
$search->sort(['name'=>'desc'],true);
````

The first argument can also be a geo distance sort expression:

```php
$search->sort([
               '_geo_distance' =>[
                   'location_anchor'=>
                       [
                           'lat'=>52.396,
                           'lon'=> -1.774
                       ],
                   'location_source' => [
                       'latitude_deg',
                       'longitude_deg'
                   ]
               ]
           ]);
````

The `sort` method can be chained. For example:

```php
$search->sort('name','asc')->sort('tags', 'desc')->sort('year', 'asc');
```

Note that the maximum number of attributes to sort by is equal to 5.

### highlight()

Enables highlighting.

The function can accept two parameters, and neither is mandatory.

- `fields` - an array with field names from which to extract the texts for highlighting. If missing, all `text` fields will be used.
- `settings` - an array with settings for highlighting. For more details, check the HTTP API [Text highlighting](https://manual.manticoresearch.com/Searching/Full_text_matching/Basic_usage#HTTP-JSON).

```php
$search->highlight();
```

```php
$search->highlight(
    ['title'],
    ['pre_tags' => '<i>','post_tags'=>'</i>']
);
```

The highlight excerpts are attached to each hit. They can be retrieved with the `getHighlight()` function of the [ResultHit](searchresults.md#resulthit-object).

`getHighlight()` will contain a list of excerpts for each field declared for highlighting in the request.

### setSource()

By default, all document fields are returned. This method can set which fields should be returned. It accepts several formats:

- setSource('attr*') - only fields like `attr*` will be returned.
- setSource(['attr1','attr2']) - only fields `attr1` and `attr2` will be returned.
- setSource([
    'included' => ['attr1','attri*'],
    'excludes' => ['desc*']
  ]) - field `attr1` and fields like `attri*` are included, while any field like `desc*` is excluded. If an attribute is found in both lists, the excluding wins.


### facet()
The `facet()` method allows you to add a facet (aggregation) to your search query.

```php
$search->facet($field, $group = null, $limit = null);
```
Parameters:
 * attribute name - This is a required parameter and can also be an expression name.
 * facet alias - If not provided, the attribute name will be utilized.
 * limit - Defines the maximum number of facet values to return.

Facets will be included in the result set and can be accessed using [ResultSet:getFacets()](searchresults.md#resultset-object).

### option()

Pass options to the search query.
You can also customize the ranker by setting a custom expression. Check out the available [built-in rankers](https://manual.manticoresearch.com/Searching/Sorting_and_ranking#Formula-expressions-for-all-the-built-in-rankers).

```php
    $search->option('cutoff', 1);
    $search->option('retry_count', 3);
    $search->option('field_weights', ['title' => 100, 'description' => 200]);

    // chain options
    $search->option('ranker', 'sph04')->option('retry_delay', 5);
    //set a custom ranker based on field values
    $search->option('ranker', 'expr(\'sum((4*lcs+2*(min_hit_pos==1)+exact_hit)*user_weight)*1000 + bm25 + IF(IN(field1, "1"), 0, 10000) + IF(IN(field2, "1"), 0, 10000)\')')
    
    // unset options by passing null
    $search->option('ranker', null);
```

### trackScores()
Enables weight calculation by setting the `track_scores` option to `true`.

```php
    $search->trackScores(true); // enables weight calculation
    $search->trackScores(false); // disables weight calculation
    $search->trackScores(null); // unsets track_scores option
```

### stripBadUtf8()
Enables the removal of bad UTF-8 characters from the results by setting the `strip_bad_utf8` option to `true`.

```php
    $search->stripBadUtf8(true); // enables the removal of bad utf8 characters
    $search->stripBadUtf8(false); // disables the removal of bad utf8 characters
    $search->stripBadUtf8(null); // unsets the strip_bad_utf8 option
```

### profile()

Provides query profiling in the result set.


### reset()

This method clears all search conditions, including the index name.

<!-- proofread -->