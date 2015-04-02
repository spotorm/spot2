Spot DataMapper ORM v2.0 [![Build Status](https://travis-ci.org/vlucas/spot2.svg)](https://travis-ci.org/vlucas/spot2)
========================
Spot v2.x is built on the [Doctrine
DBAL](http://www.doctrine-project.org/projects/dbal.html), and targets PHP
5.4+.

The aim of Spot is to be a lightweight DataMapper alternative that is clear,
efficient, and simple - and doesn't use annotations or proxy classes.

Using Spot In Your Project
--------------------------

Spot is a standalone ORM that can be used in any project. Follow the
instructions below to get Spot setup in your project.

Installation with Composer
--------------------------

```bash
composer require vlucas/spot2
```


Connecting to a Database
------------------------

The `Spot\Locator` object is the main point of access to spot that you will
have to be able to access from everywhere you need to run queries or work with
your entities. It is responsible for loading mappers and managing configuration.
To create a Locator, you will need a `Spot\Config` object.

The `Spot\Config` object stores and references database connections by name.
Create a new instance of `Spot\Config` and add database connections with
DSN strings so Spot can establish a database connection, then create your
locator object:

```php
$cfg = new \Spot\Config();

// MySQL
$cfg->addConnection('mysql', 'mysql://user:password@localhost/database_name');
// Sqlite
$cfg->addConnection('sqlite', 'sqlite://path/to/database.sqlite');

$spot = new \Spot\Locator($cfg);
```

You can also use [DBAL-compatible configuration
arrays](http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/configuration.html)
instead of DSN strings if you prefer:

```php
$cfg->addConnection('mysql', [
    'dbname' => 'mydb',
    'user' => 'user',
    'password' => 'secret',
    'host' => 'localhost',
    'driver' => 'pdo_mysql',
]);
```

Accessing the Locator
--------------------

Since you have to have access to your mapper anywhere you use the
database, most people create a helper method to create a mapper instance
once and then return the same instance when required again. Such a
helper method might look something like this:

```php
function spot() {
    static $spot;
    if($spot === null) {
        $spot = new \Spot\Locator();
        $spot->config()->addConnection('test_mysql', 'mysql://user:password@localhost/database_name');
    }
    return $spot;
}
```

If you are using a framework with a dependency injection container or service,
you will want to use it so that the `Spot\Locator` object is available
everywhere in your application that you need it.

Getting A Mapper
----------------

Since Spot follows the DataMapper design pattern, you will need a mapper
instance for working with object Entities and database tables. You can get a
mapper instance from the `Spot\Locator` object's `mapper` method by providing
the fully qualified entity namespace + class name:

```php
$postMapper = $spot->mapper('Entity\Post');
```

Mappers only work with one entity type, so you will need one mapper per entity
class you work with (i.e. to save an Entity\Post, you will need the appropriate
mapper, and to save an Entity\Comment, you will need a comment mapper, not the
same post mapper. Relations will automatically be loaded and handled by their
corresponding mapper by Spot.

**NOTE: You do NOT have to create a mapper for each entity unless you need
custom finder methods or other custom logic. If there is no entity-specific
mapper for the entity you want, Spot will load the generic mapper for you and
return it.**

Creating Entities
-----------------

Entity classes can be named and namespaced however you want to set them
up within your project structure. For the following examples, the
Entities will just be prefixed with an `Entity` namespace for easy psr-0
compliant autoloading.

```php
namespace Entity;

use Spot\EntityInterface as Entity;
use Spot\MapperInterface as Mapper;

class Post extends \Spot\Entity
{
    protected static $table = 'posts';

    public static function fields()
    {
        return [
            'id'           => ['type' => 'integer', 'autoincrement' => true, 'primary' => true],
            'title'        => ['type' => 'string', 'required' => true],
            'body'         => ['type' => 'text', 'required' => true],
            'status'       => ['type' => 'integer', 'default' => 0, 'index' => true],
            'author_id'    => ['type' => 'integer', 'required' => true],
            'date_created' => ['type' => 'datetime', 'value' => new \DateTime()]
        ];
    }

    public static function relations(Mapper $mapper, Entity $entity)
    {
        return [
            'tags' => $mapper->hasManyThrough($entity, 'Entity\Tag', 'Entity\PostTag', 'tag_id', 'post_id'),
            'comments' => $mapper->hasMany($entity, 'Entity\Post\Comment', 'post_id')->order(['date_created' => 'ASC']),
            'author' => $mapper->belongsTo($entity, 'Entity\Author', 'author_id')
        ];
    }
}
```

Using Custom Mappers
--------------------

Although you do not have to create a mapper for each entity, sometimes it is
nice to create one if you have a lot of custom finder methods, or want a better
place to contain the logic of building all the queries you need.

Just specify the full mapper class name in your entity:
```php
namespace Entity;

class Post extends \Spot\Entity
{
    protected static $mapper = 'Entity\Mapper\Post';

    // ... snip ...
}
```

And then create your mapper:
```php
namespace Entity\Mapper;

use Spot\Mapper;

class Post extends Mapper
{
    /**
     * Get 10 most recent posts for display on the sidebar
     *
     * @return \Spot\Query
     */
    public function mostRecentPostsForSidebar()
    {
        return $this->where(['status' => 'active'])
            ->order(['date_created' => 'DESC'])
            ->limit(10);
    }
}
```

Then when you load the mapper like normal, Spot will see the custom
`Entity\Post::$mapper` you defined, and load that instead of the generic one,
allowing you to call your custom method:

```php
$mapper = $spot->mapper('Entity\Post');
$sidebarPosts = $mapper->mostRecentPostsForSidebar();
```

Field Types
-----------

Since Spot v2.x is built on top of DBAL, all the [DBAL
types](http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/types.html)
are used and fully supported in Spot:

Integer Types
 * `smallint`
 * `integer`
 * `bigint`

Decimal Types
 * `decimal`
 * `float`

String Types
 * `string`
 * `text`
 * `guid`

Binary String Types
 * `binary`
 * `blob`

Boolean/Bit Types
 * `boolean`

Date and Time Types
 * `date`
 * `datetime`
 * `datetimetz`
 * `time`

Array Types
 * `array` - PHP serialize/deserialze
 * `simple_array` - PHP implode/explode
 * `json_array` - json_encode/json_decode

Object Types
 * `object` - PHP serialize/deserialze

Please read the [Doctrine DBAL Types Reference
Page](http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/types.html)
thoroughly for more information and types and cross-database support. Some
types may be stored differently on different databases, depending on database
vendor support and other factors.

#### Registering Custom Field Types

If you want to register your own custom field type with custom
functionality on get/set, have a look at the [Custom Mapping Types on the DBAL
reference page](http://docs.doctrine-project.org/projects/doctrine-dbal/en/latest/reference/types.html#custom-mapping-types).

Since Spot uses the DBAL internally, there are no additional changes you have
to make for your custom type to work with Spot.

Migrations / Creating and Updating Tables
-----------------------------------------

Spot comes with a method for running migrations on Entities that will
automatically CREATE and ALTER tables based on the current Entity's `fields`
definition.

```php
$mapper = $spot->mapper('Entity\Post');
$mapper->migrate();
```

Your database should now have the `posts` table in it, with all the fields you
described in your `Post` entity.

**NOTE: Please note that re-naming columns is not supported in migrations because
there is no way for spot to know which column you renamed to what - Spot will
see a new column that needs to be created, and a column that no longer exists
and needs to be dropped. This could result in data loss during an
auto-migration.**

Finders (Mapper)
----------------

The main finders used most are `all` to return a collection of entities,
and `first` or `get` to return a single entity matching the conditions.

### all()

Find all entities and return a `Spot\Entity\Collection` of loaded `Spot\Entity`
objects.

### where([conditions])

Find all entities that match the given conditions and return a
`Spot\Entity\Collection` of loaded `Spot\Entity` objects.

```php
// Where can be called directly from the mapper
$posts = $mapper->where(['status' => 1]);

// Or chained using the returned `Spot\Query` object - results identical to above
$posts = $mapper->all()->where(['status' => 1]);

// Or more explicitly using using `select`, which always returns a `Spot\Query` object
$posts = $mapper->select()->where(['status' => 1]);
```

Since a `Spot\Query` object is returned, conditions and other statements
can be chained in any way or order you want. The query will be
lazy-executed on interation or `count`, or manually by ending the chain with a
call to `execute()`.

### first([conditions])

Find and return a single `Spot\Entity` object that matches the criteria.

```php
$post = $mapper->first(['title' => "Test Post"]);
```

Or `first` can be used on a previous query with `all` to fetch only the first
matching record.

```php
$post = $mapper->all(['title' => "Test Post"])->first();
```

A call to `first` will always execute the query immediately, and return either
a single loaded entity object, or boolean `false`.

### Conditional Queries

```php
# All posts with a 'published' status, descending by date_created
$posts = $mapper->all()
    ->where(['status' => 'published'])
    ->order(['date_created' => 'DESC']);

# All posts that are not published
$posts = $mapper->all()
    ->where(['status <>' => 'published'])

# All posts created before 3 days ago
$posts = $mapper->all()
    ->where(['date_created <' => new \DateTime('-3 days')]);

# Posts with 'id' of 1, 2, 5, 12, or 15 - Array value = automatic "IN" clause
$posts = $mapper->all()
    ->where(['id' => [1, 2, 5, 12, 15]]);
```

### Joins

Joins are currently not enabled by Spot's query builder. The Doctine DBAL query
builder does provide full support for them, so they may be enabled in the
future.

### Custom Queries

While ORMs like Spot are very nice to use, if you need to do complex queries,
it's best to just use custom queries with the SQL you know and love.

Spot provides a `query` method that allows you to run custom SQL, and load the
results into a normal collection of entity objects. This way, you can easily run
custom SQL queries with all the same ease of use and convenience as the
built-in finder methods and you won't have to do any special handling.

#### Using Custom SQL

```php
$posts = $mapper->query("SELECT * FROM posts WHERE id = 1");
```

#### Using Query Parameters

```php
$posts = $mapper->query("SELECT * FROM posts WHERE id = ?", [1]);
```

#### Using Named Placeholders

```php
$posts = $mapper->query("SELECT * FROM posts WHERE id = :id", ['id' => 1]);
```

**NOTE: Spot will load ALL returned columns on the target entity from the query
you run. So if you perform a JOIN or get more data than the target entity
normally has, it will just be loaded on the target entity, and no attempt will
be made to map the data to other entities or to filter it based on only the
defined fields.**

Relations
---------

Relations are convenient ways to access related, parent, and child entities from
another loaded entity object. An example might be `$post->comments` to query for
all the comments related to the current `$post` object.

### Live Query Objects

All relations are returned as instances of relation classes that extend
`Spot\Relation\RelationAbstract`. This class holds a `Spot\Query` object
internally, and allows you to chain your own query modifications on it so you
can do custom things with relations, like ordering, adding more query
conditions, etc.

```php
$mapper->hasMany($entity, 'Entity\Comment', 'post_id')
    ->where(['status' => 'active'])
    ->order(['date_created' => 'ASC']);
```

All of these query modifications are held in a queue, and are run when the
relation is actually executed (on `count` or `foreach` iteration, or when
`execute` is explicitly called).

### Eager Loading

All relation types are lazy-loaded by default, and can be eager-loaded to
solve the N+1 query problem using the `with` method:

```php
$posts = $posts->all()->with('comments');
```

Multiple relations can be eager-loaded using an array:
```php
$posts = $posts->all()->with(['comments', 'tags']);
```

### Relation Types

Entity relation types are:

 * `HasOne`
 * `BelongsTo`
 * `HasMany`
 * `HasManyThrough`

### HasOne

HasOne is a relation where the *related object has a field which points to the
current object* - an example might be `User` has one `Profile`.

#### Method

```php
$mapper->hasOne(Entity $entity, $foreignEntity, $foreignKey)
```
 * `$entity` - The current entity instance
 * `$foreignEntity` - Name of the entity you want to load
 * `$foreignKey` - Field name on the `$foreignEntity` that matches up with the
   primary key of the current entity

#### Example

```php
namespace Entity;

use Spot\EntityInterface as Entity;
use Spot\MapperInterface as Mapper;

class User extends \Spot\Entity
{
    protected static $table = 'users';

    public static function fields()
    {
        return [
            'id'           => ['type' => 'integer', 'autoincrement' => true, 'primary' => true],
            'username'     => ['type' => 'string', 'required' => true],
            'email'        => ['type' => 'string', 'required' => true],
            'status'       => ['type' => 'integer', 'default' => 0, 'index' => true],
            'date_created' => ['type' => 'datetime', 'value' => new \DateTime()]
        ];
    }

    public static function relations(Mapper $mapper, Entity $entity)
    {
        return [
            'profile' => $mapper->hasOne($entity, 'Entity\User\Profile', 'user_id')
        ];
    }
}
```

In this scenario, the `Entity\User\Profile` entity has a field named `user_id`
which the `Entity\User`'s `id` field as a value. Note that *no field exists on
this entity for this relation, but rather the related entity*.

### BelongsTo

BelongsTo is a relation where the *current object has a field which points to
the related object* - an example might be `Post` belongs to `User`.

#### Method

```php
$mapper->belongsTo(Entity $entity, $foreignEntity, $localKey)
```
 * `$entity` - The current entity instance
 * `$foreignEntity` - Name of the entity you want to load
 * `$localKey` - Field name on the current entity that matches up with the
   primary key of `$foreignEntity` (the one you want to load)

#### Example

```php
namespace Entity;

use Spot\EntityInterface as Entity;
use Spot\MapperInterface as Mapper;

class Post extends \Spot\Entity
{
    protected static $table = 'posts';

    public static function fields()
    {
        return [
            'id'           => ['type' => 'integer', 'autoincrement' => true, 'primary' => true],
            'user_id'      => ['type' => 'integer', 'required' => true],
            'title'        => ['type' => 'string', 'required' => true],
            'body'         => ['type' => 'text', 'required' => true],
            'status'       => ['type' => 'integer', 'default' => 0, 'index' => true],
            'date_created' => ['type' => 'datetime', 'value' => new \DateTime()]
        ];
    }

    public static function relations(Mapper $mapper, Entity $entity)
    {
        return [
            'user' => $mapper->belongsTo($entity, 'Entity\User', 'user_id')
        ];
    }
}
```

In this scenario, the `Entity\Post` entity has a field named `user_id` which is
the `Entity\User`'s `id` field's value. Note that *the field exists on this
entity for this relation, but not on the related entity*.

### HasMany

HasMany is used where a single record relates to multiple other records - an
example might be `Post` has many `Comments`.

#### Method

```php
$mapper->hasMany(Entity $entity, $entityName, $foreignKey, $localValue = null)
```
 * `$entity` - The current entity instance
 * `$entityName` - Name of the entity you want to load a collection of
 * `$foreignKey` - Field name on the `$entityName` that matches up with the
   current entity's primary key

#### Example

We start by adding a `comments` relation to our `Post` object:
```php
namespace Entity;

use Spot\EntityInterface as Entity;
use Spot\MapperInterface as Mapper;

class Post extends Spot\Entity
{
    protected static $table = 'posts';

    public static function fields()
    {
        return [
            'id'           => ['type' => 'integer', 'autoincrement' => true, 'primary' => true],
            'title'        => ['type' => 'string', 'required' => true],
            'body'         => ['type' => 'text', 'required' => true],
            'status'       => ['type' => 'integer', 'default' => 0, 'index' => true],
            'date_created' => ['type' => 'datetime', 'value' => new \DateTime()]
        ];
    }

    public static function relations(Mapper $mapper, Entity $entity)
    {
        return [
            'comments' => $mapper->hasMany($entity, 'Entity\Comment', 'post_id')->order(['date_created' => 'ASC']),
        ];
    }
}
```

And add a `Entity\Post\Comment` object with a 'belongsTo' relation back to the post:

```php
namespace Entity;

class Comment extends \Spot\Entity
{
    // ... snip ...

    public static function relations(Mapper $mapper, Entity $entity)
    {
        return [
            'post' => $mapper->belongsTo($entity, 'Entity\Post', 'post_id')
        ];
    }
}
```

### HasManyThrough

HasManyThrough is used for many-to-many relationships. An good example is
tagging. A post has many tags, and a tag has many posts. This relation is
a bit more complex than the others, because a HasManyThrough requires a
join table and mapper.

#### Method
```php
$mapper->hasManyThrough(Entity $entity, string $hasManyEntity, string $throughEntity, string $selectField, string $whereField)
```
 * `$entity` - The current entity instance
 * `$hasManyEntity` - This is the target entity you want a collection of. In this case, we want a collection of `Entity\Tag` objects.
 * `$throughEntity` - Name of the entity we are going through to get what we want - In this case, `Entity\PostTag`.
 * `$selectField` - Name of the field on the `$throughEntity` that will select records by the primary key of `$hasManyEntity`.
 * `$whereField` - Name of the field on the `$throughEntity` to select records by the current entities' primary key (we have a post, so this will be the `Entity\PostTag->post_id` field).

#### Example

We need to add the `tags` relation to our `Post` entity, specifying query
conditions for both sides of the relation.

```php
namespace Entity;

use Spot\EntityInterface as Entity;
use Spot\MapperInterface as Mapper;

class Post extends Spot\Entity
{
    protected static $table = 'posts';

    public static function fields()
    {
        return [
            'id'           => ['type' => 'integer', 'autoincrement' => true, 'primary' => true],
            'title'        => ['type' => 'string', 'required' => true],
            'body'         => ['type' => 'text', 'required' => true],
            'status'       => ['type' => 'integer', 'default' => 0, 'index' => true],
            'date_created' => ['type' => 'datetime', 'value' => new \DateTime()]
        ];
    }

    public static function relations(Mapper $mapper, Entity $entity)
    {
        return [
            'tags' => $mapper->hasManyThrough($entity, 'Entity\Tag', 'Entity\PostTag', 'tag_id', 'post_id'),
        ];
    }
```

#### Explanation

The result we want is a collection of `Entity\Tag` objects where the id equals
the `post_tags.tag_id` column. We get this by going through the
`Entity\PostTags` entity, using the current loaded post id matching
`post_tags.post_id`.

