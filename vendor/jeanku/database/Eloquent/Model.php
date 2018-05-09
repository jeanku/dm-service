<?php

namespace Jeanku\Database\Eloquent;

use Closure;
use Jeanku\Database\Support\Arr;
use Jeanku\Database\Support\Str;
use Jeanku\Database\Support\Collection as BaseCollection;
use Jeanku\Database\Query\Builder as QueryBuilder;
use Jeanku\Database\Support\Arrayable;
use Jeanku\Database\DatabaseManager;

abstract class Model implements Arrayable
{
    /**
     * The connection name for the model.
     * @var string
     */
    protected $connection;

    /**
     * The table associated with the model.
     * @var string
     */
    protected $table;

    /**
     * The primary key for the model.
     * @var string
     */
    protected $primaryKey = 'id';

    /**
     * The "type" of the auto-incrementing ID.
     * @var string
     */
    protected $keyType = 'int';

    /**
     * Indicates if the IDs are auto-incrementing.
     * @var bool
     */
    public $incrementing = true;

    /**
     * Indicates if the model should be timestamped.
     * @var bool
     */
    public $timestamps = true;

    /**
     * The model's attributes.
     * @var array
     */
    protected $attributes = [];

    /**
     * The model attribute's original state.
     * @var array
     */
    protected $original = [];

    /**
     * The loaded relationships for the model.
     * @var array
     */
    protected $relations = [];

    /**
     * The attributes that should be hidden for arrays.
     * @var array
     */
    protected $hidden = [];

    /**
     * The attributes that should be visible in arrays.
     * @var array
     */
    protected $visible = [];

    /**
     * The accessors to append to the model's array form.
     * @var array
     */
    protected $appends = [];

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = [];

    /**
     * The attributes that aren't mass assignable.
     * @var array
     */
    protected $guarded = ['*'];

    /**
     * The storage format of the model's date columns.
     * @var string
     */
    protected $dateFormat;

    /**
     * The attributes that should be cast to native types.
     * @var array
     */
    protected $casts = [];

    /**
     * The relationships that should be touched on save.
     * @var array
     */
    protected $touches = [];

    /**
     * User exposed observable events.
     * @var array
     */
    protected $observables = [];

    /**
     * The relations to eager load on every query.
     * @var array
     */
    protected $with = [];

    /**
     * The class name to be used in polymorphic relations.
     * @var string
     */
    protected $morphClass;

    /**
     * Indicates if the model exists.
     * @var bool
     */
    public $exists = false;

    /**
     * Indicates if the model was inserted during the current request lifecycle.
     * @var bool
     */
    public $wasRecentlyCreated = false;

    /**
     * Indicates whether attributes are snake cased on arrays.
     * @var bool
     */
    public static $snakeAttributes = true;

    /**
     * The connection resolver instance.
     * @var $resolver
     */
    protected static $resolver;


    /**
     * The array of booted models.
     * @var array
     */
    protected static $booted = [];

    /**
     * The array of global scopes on the model.
     * @var array
     */
    protected static $globalScopes = [];

    /**
     * Indicates if all mass assignment is enabled.
     * @var bool
     */
    protected static $unguarded = false;

    /**
     * The cache of the mutated attributes for each class.
     * @var array
     */
    protected static $mutatorCache = [];

    //表的创建时间字段
    const CREATED_AT = 'create_time';

    //表的更新时间字段
    const UPDATED_AT = 'update_time';

    //表的删除时间字段
    const DELETED_AT = null;

    //表的软删除字段无效值 1:有效 0:无效
    const INVALID_STATUS = 0;


    /**
     * construct function
     * @param  array $attributes
     */
    public function __construct(array $attributes = [])
    {
        $this->bootIfNotBooted();
        $this->syncOriginal();
        $this->fill($attributes);

    }

    /**
     * Check if the model needs to be booted and if so, do it.
     * @return void
     */
    protected function bootIfNotBooted()
    {
        if (!isset(static::$booted[static::class])) {
            static::$booted[static::class] = true;
            static::boot();
        }
        !self::$resolver && self::setConnectionResolver(new DatabaseManager());
    }

    /**
     * start a transaction.
     * @return void
     */
    public static function transaction(\Closure $callback)
    {
        !self::$resolver && self::setConnectionResolver(new DatabaseManager());
        self::$resolver->transaction($callback);
    }


    /**
     * The "booting" method of the model.
     * @return void
     */
    protected static function boot()
    {
        static::bootTraits();
    }

    /**
     * Boot all of the bootable traits on the model.
     * @return void
     */
    protected static function bootTraits()
    {
        $class = static::class;
        method_exists(static::class, $method = 'bootSoftDeletes') && forward_static_call([$class, $method]);
    }

    /**
     * Clear the list of booted models so they will be re-booted.
     * @return void
     */
    public static function clearBootedModels()
    {
        static::$booted = [];
        static::$globalScopes = [];
    }

    /**
     * Register a new global scope on the model.
     * @param  Scope $scope
     * @param  $implementation
     * @return mixed
     */
    public static function addGlobalScope($scope, Closure $implementation = null)
    {
        if (is_string($scope) && $implementation !== null) {
            return static::$globalScopes[static::class][$scope] = $implementation;
        }
        if ($scope instanceof Closure) {
            return static::$globalScopes[static::class][spl_object_hash($scope)] = $scope;
        }
        return static::$globalScopes[static::class][get_class($scope)] = $scope;
    }

    /**
     * Determine if a model has a global scope.
     * @param Scope|string $scope
     * @return bool
     */
    public static function hasGlobalScope($scope)
    {
        return !is_null(static::getGlobalScope($scope));
    }

    /**
     * Get a global scope registered with the model.
     * @param  $scope
     * @return Scope|null
     */
    public static function getGlobalScope($scope)
    {
        if (!is_string($scope)) {
            $scope = get_class($scope);
        }
        return Arr::get(static::$globalScopes, static::class . '.' . $scope);
    }

    /**
     * Get the global scopes for this class instance.
     * @return array
     */
    public function getGlobalScopes()
    {
        return Arr::get(static::$globalScopes, static::class, []);
    }


    /**
     * Fill the model with an array of attributes.
     * @param  array $attributes
     * @return $this
     * @throws Exception
     */
    public function fill(array $attributes)
    {
        $totallyGuarded = $this->totallyGuarded();
        foreach ($this->fillableFromArray($attributes) as $key => $value) {
            $key = $this->removeTableFromKey($key);
            if ($this->isFillable($key)) {
                $this->setAttribute($key, $value);
            } elseif ($totallyGuarded) {
                throw new \Exception($key);
            }
        }
        return $this;
    }

    /**
     * Get the fillable attributes of a given array.
     * @param  array $attributes
     * @return array
     */
    protected function fillableFromArray(array $attributes)
    {
        if (count($this->getFillable()) > 0 && !static::$unguarded) {
            return array_intersect_key($attributes, array_flip($this->getFillable()));
        }
        return $attributes;
    }

    /**
     * Create a new instance of the given model.
     * @param  array $attributes
     * @param  bool $exists
     * @return static
     */
    public function newInstance($attributes = [], $exists = false)
    {
        $model = new static((array)$attributes);
        $model->exists = $exists;
        return $model;
    }

    /**
     * Create a new model instance that is existing.
     * @param  array $attributes
     * @param  string|null $connection
     * @return static
     */
    public function newFromBuilder($attributes = [], $connection = null)
    {
        $model = $this->newInstance([], true);
        $model->setRawAttributes((array)$attributes, true);
        $model->setConnection($connection ?: $this->getConnectionName());
        return $model;
    }

    /**
     * Create a collection of models from plain arrays.
     * @param  array $items
     * @param  string|null $connection
     * @return \Jeanku\Database\Eloquent\Collection
     */
    public static function hydrate(array $items, $connection = null)
    {
        $instance = (new static)->setConnection($connection);
        $items = array_map(function ($item) use ($instance) {
            return $instance->newFromBuilder($item);
        }, $items);
        return $instance->newCollection($items);
    }

    /**
     * Create a collection of models from a raw query.
     * @param  string $query
     * @param  array $bindings
     * @param  string|null $connection
     * @return \Jeanku\Database\Eloquent\Collection
     */
    public static function hydrateRaw($query, $bindings = [], $connection = null)
    {
        $instance = (new static)->setConnection($connection);
        $items = $instance->getConnection()->select($query, $bindings);
        return static::hydrate($items, $connection);
    }

    /**
     * Save a new model and return the instance.
     * @param  array $attributes
     * @return static
     */
    public static function create(array $attributes = [])
    {
        $model = new static($attributes);
        $model->save();
        return $model;
    }

    /**
     * Begin querying the model.
     * @return \Jeanku\Database\Eloquent\Builder
     */
    public static function query()
    {
        return (new static)->newQuery();
    }

    /**
     * Begin querying the model on a given connection.
     * @param  string|null $connection
     * @return \Jeanku\Database\Eloquent\Builder
     */
    public static function on($connection = null)
    {
        $instance = new static;
        $instance->setConnection($connection);
        return $instance->newQuery();
    }

    /**
     * Begin querying the model on the write connection.
     * @return \Jeanku\Database\Query\Builder
     */
    public static function onWriteConnection()
    {
        $instance = new static;
        return $instance->newQuery()->useWritePdo();
    }

    /**
     * Get all of the models from the database.
     * @param  array|mixed $columns
     * @return \Jeanku\Database\Eloquent\Collection|static[]
     */
    public static function all($columns = ['*'])
    {
        $columns = is_array($columns) ? $columns : func_get_args();
        $instance = new static;
        return $instance->newQuery()->get($columns);
    }

    /**
     * Reload a fresh model instance from the database.
     *
     * @param  array|string $with
     * @return $this|null
     */
    public function fresh($with = [])
    
    {
        if (!$this->exists) {
            return;
        }
        if (is_string($with)) {
            $with = func_get_args();
        }
        $key = $this->getKeyName();
        return static::with($with)->where($key, $this->getKey())->first();
    }

    /**
     * Eager load relations on the model.
     * @param  array|string $relations
     * @return $this
     */
    public function load($relations)
    {
        if (is_string($relations)) {
            $relations = func_get_args();
        }
        $query = $this->newQuery()->with($relations);
        $query->eagerLoadRelations([$this]);
        return $this;
    }

    /**
     * Begin querying a model with eager loading.
     * @param  array|string $relations
     * @return \Jeanku\Database\Eloquent\Builder|static
     */
    public static function with($relations)
    {
        if (is_string($relations)) {
            $relations = func_get_args();
        }
        $instance = new static;
        return $instance->newQuery()->with($relations);
    }

    /**
     * Append attributes to query when building a query.
     * @param  array|string $attributes
     * @return $this
     */
    public function append($attributes)
    {
        if (is_string($attributes)) {
            $attributes = func_get_args();
        }
        $this->appends = array_unique(
            array_merge($this->appends, $attributes)
        );
        return $this;
    }


    /**
     * Retrieve the fully qualified class name from a slug.
     * @param  string $class
     * @return string
     */
    public function getActualClassNameForMorph($class)
    {
        return Arr::get(Relation::morphMap(), $class, $class);
    }


    /**
     * Get the joining table name for a many-to-many relation.
     * @param  string $related
     * @return string
     */
    public function joiningTable($related)
    {
        $base = Str::snake(class_basename($this));
        $related = Str::snake(class_basename($related));
        $models = [$related, $base];
        sort($models);
        return strtolower(implode('_', $models));
    }

    /**
     * Destroy the models for the given IDs.
     * @param  array|int $ids
     * @return int
     */
    public static function destroy($ids)
    {
        $count = 0;
        $ids = is_array($ids) ? $ids : func_get_args();
        $instance = new static;
        $key = $instance->getKeyName();
        foreach ($instance->whereIn($key, $ids)->get() as $model) {
            if ($model->delete()) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Perform the actual delete query on this model instance.
     * @return void
     */
    protected function performDeleteOnModel()
    {
        $this->setKeysForSaveQuery($this->newQueryWithoutScopes())->delete();
    }


    /**
     * Update the model in the database.
     * @param  array $attributes
     * @param  array $options
     * @return bool|int
     */
    public function update(array $attributes = [], array $options = [])
    {
        if (!$this->exists) {
            return false;
        }
        return $this->fill($attributes)->save($options);
    }

    /**
     * Save the model and all of its relationships.
     * @return bool
     */
    public function push()
    {
        if (!$this->save()) {
            return false;
        }
        foreach ($this->relations as $models) {
            $models = $models instanceof Collection ? $models->all() : [$models];
            foreach (array_filter($models) as $model) {
                if (!$model->push()) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Save the model to the database.
     * @param  array $options
     * @return bool
     */
    public function save(array $options = [])
    {
        $query = $this->newQueryWithoutScopes();
        if ($this->exists) {
            $saved = $this->performUpdate($query, $options);
        }
        else {
            $saved = $this->performInsert($query, $options);
        }
        return $saved;
    }

    /**
     * Save the model to the database using transaction.
     * @param  array $options
     * @return bool
     * @throws \Throwable
     */
    public function saveOrFail(array $options = [])
    {
        return $this->getConnection()->transaction(function () use ($options) {
            return $this->save($options);
        });
    }

    /**
     * Perform a model update operation.
     * @param  \Jeanku\Database\Eloquent\Builder $query
     * @param  array $options
     * @return bool
     */
    protected function performUpdate(Builder $query, array $options = [])
    {
        $dirty = $this->getDirty();
        if (count($dirty) > 0) {
            if ($this->timestamps && Arr::get($options, 'timestamps', true)) {
                $this->updateTimestamps();
            }
            $dirty = $this->getDirty();
            if (count($dirty) > 0) {
                $this->setKeysForSaveQuery($query)->update($dirty);
            }
        }
        return true;
    }

    /**
     * Perform a model insert operation.
     * @param  \Jeanku\Database\Eloquent\Builder $query
     * @param  array $options
     * @return bool
     */
    protected function performInsert(Builder $query, array $options = [])
    {
        if ($this->timestamps && Arr::get($options, 'timestamps', true)) {
            $this->updateTimestamps();
        }
        $attributes = $this->attributes;
        if ($this->getIncrementing()) {
            $this->insertAndSetId($query, $attributes);
        } else {
            $query->insert($attributes);
        }
        $this->exists = true;
        $this->wasRecentlyCreated = true;
        return true;
    }

    /**
     * Insert the given attributes and set the ID on the model.
     * @param  \Jeanku\Database\Eloquent\Builder $query
     * @param  array $attributes
     * @return void
     */
    protected function insertAndSetId(Builder $query, $attributes)
    {
        $id = $query->insertGetId($attributes, $keyName = $this->getKeyName());
        $this->setAttribute($keyName, $id);
    }


    /**
     * Determine if the model touches a given relation.
     * @param  string $relation
     * @return bool
     */
    public function touches($relation)
    {
        return in_array($relation, $this->touches);
    }


    /**
     * Set the keys for a save update query.
     * @param  \Jeanku\Database\Eloquent\Builder $query
     * @return \Jeanku\Database\Eloquent\Builder
     */
    protected function setKeysForSaveQuery(Builder $query)
    {
        $query->where($this->getKeyName(), '=', $this->getKeyForSaveQuery());
        return $query;
    }

    /**
     * Get the primary key value for a save query.
     * @return mixed
     */
    protected function getKeyForSaveQuery()
    {
        if (isset($this->original[$this->getKeyName()])) {
            return $this->original[$this->getKeyName()];
        }
        return $this->getAttribute($this->getKeyName());
    }

    /**
     * Update the model's update timestamp.
     * @return bool
     */
    public function touch()
    {
        if (!$this->timestamps) {
            return false;
        }
        $this->updateTimestamps();
        return $this->save();
    }

    /**
     * Update the creation and update timestamps.
     * @return void
     */
    protected function updateTimestamps()
    {
        $time = $this->freshTimestamp();
        if (!$this->isDirty(static::UPDATED_AT)) {
            $this->setUpdatedAt($time);
        }
        if (!$this->exists && !$this->isDirty(static::CREATED_AT)) {
            $this->setCreatedAt($time);
        }
    }

    /**
     * Set the value of the "created at" attribute.
     * @param  mixed $value
     * @return $this
     */
    public function setCreatedAt($value)
    {
        $this->{static::CREATED_AT} = $value;
        return $this;
    }

    /**
     * Set the value of the "updated at" attribute.
     * @param  mixed $value
     * @return $this
     */
    public function setUpdatedAt($value)
    {
        $this->{static::UPDATED_AT} = $value;
        return $this;
    }

    /**
     * Get the name of the "created at" column.
     * @return string
     */
    public function getCreatedAtColumn()
    {
        return static::CREATED_AT;
    }

    /**
     * Get the name of the "updated at" column.
     * @return string
     */
    public function getUpdatedAtColumn()
    {
        return static::UPDATED_AT;
    }

    /**
     * 获取当前时间
     * @return integer
     */
    public function freshTimestamp()
    {
        return time();
    }

    /**
     * 转换日期时间
     * @param  $mValue 日期时间
     * @return integer
     */
    public function fromDateTime($mValue)
    {
        return $mValue;
    }

    /**
     * Get a fresh timestamp for the model.
     * @return string
     */
    public function freshTimestampString()
    {
        return $this->fromDateTime($this->freshTimestamp());
    }

    /**
     * Get a new query builder for the model's table.
     * @return \Jeanku\Database\Eloquent\Builder
     */
    public function newQuery()
    {
        $builder = $this->newQueryWithoutScopes();
        foreach ($this->getGlobalScopes() as $identifier => $scope) {
            $builder->withGlobalScope($identifier, $scope);
        }
        return $builder;
    }

    /**
     * Get a new query instance without a given scope.
     * @param  \Jeanku\Database\Eloquent\Scope|string $scope
     * @return \Jeanku\Database\Eloquent\Builder
     */
    public function newQueryWithoutScope($scope)
    {
        $builder = $this->newQuery();

        return $builder->withoutGlobalScope($scope);
    }


    /**
     * Get a new query builder that doesn't have any global scopes.
     * @return \Jeanku\Database\Eloquent\Builder|static
     */
    public function newQueryWithoutScopes()
    {
        $builder = $this->newEloquentBuilder(
            $this->newBaseQueryBuilder()
        );
        return $builder->setModel($this)->with($this->with);
    }

    /**
     * Create a new Eloquent query builder for the model.
     * @param  \Jeanku\Database\Query\Builder $query
     * @return \Jeanku\Database\Eloquent\Builder|static
     */
    public function newEloquentBuilder($query)
    {
        return new Builder($query);
    }

    /**
     * Get a new query builder instance for the connection.
     * @return \Jeanku\Database\Query\Builder
     */
    protected function newBaseQueryBuilder()
    {
        $conn = $this->getConnection();

        return new QueryBuilder($conn);
    }

    /**
     * Create a new Eloquent Collection instance.
     * @param  array $models
     * @return \Jeanku\Database\Eloquent\Collection
     */
    public function newCollection(array $models = [])
    {
        return new Collection($models);
    }


    /**
     * Get the table associated with the model.
     * @return string
     */
    public function getTable()
    {
        if (isset($this->table)) {
            return $this->table;
        }
        return str_replace('\\', '', Str::snake(Str::plural(class_basename($this))));
    }

    /**
     * Set the table associated with the model.
     * @param  string $table
     * @return $this
     */
    public function setTable($table)
    {
        $this->table = $table;

        return $this;
    }

    /**
     * Get the value of the model's primary key.
     * @return mixed
     */
    public function getKey()
    {
        return $this->getAttribute($this->getKeyName());
    }

    /**
     * Get the queueable identity for the entity.
     * @return mixed
     */
    public function getQueueableId()
    {
        return $this->getKey();
    }

    /**
     * Get the primary key for the model.
     * @return string
     */
    public function getKeyName()
    {
        return $this->primaryKey;
    }

    /**
     * Set the primary key for the model.
     * @param  string $key
     * @return $this
     */
    public function setKeyName($key)
    {
        $this->primaryKey = $key;
        return $this;
    }

    /**
     * Get the table qualified key name.
     * @return string
     */
    public function getQualifiedKeyName()
    {
        return $this->getTable() . '.' . $this->getKeyName();
    }

    /**
     * Get the value of the model's route key.
     * @return mixed
     */
    public function getRouteKey()
    {
        return $this->getAttribute($this->getRouteKeyName());
    }

    /**
     * Get the route key for the model.
     *
     * @return string
     */
    public function getRouteKeyName()
    {
        return $this->getKeyName();
    }

    /**
     * Determine if the model uses timestamps.
     *
     * @return bool
     */
    public function usesTimestamps()
    {
        return $this->timestamps;
    }

    /**
     * Get the polymorphic relationship columns.
     * @param  string $name
     * @param  string $type
     * @param  string $id
     * @return array
     */
    protected function getMorphs($name, $type, $id)
    {
        $type = $type ?: $name . '_type';
        $id = $id ?: $name . '_id';
        return [$type, $id];
    }

    /**
     * Get the class name for polymorphic relations.
     * @return string
     */
    public function getMorphClass()
    {
        $morphMap = Relation::morphMap();
        $class = static::class;
        if (!empty($morphMap) && in_array($class, $morphMap)) {
            return array_search($class, $morphMap, true);
        }
        return $this->morphClass ?: $class;
    }

    /**
     * Get the default foreign key name for the model.
     * @return string
     */
    public function getForeignKey()
    {
        return Str::snake(class_basename($this)) . '_id';
    }

    /**
     * Get the hidden attributes for the model.
     * @return array
     */
    public function getHidden()
    {
        return $this->hidden;
    }

    /**
     * Set the hidden attributes for the model.
     * @param  array $hidden
     * @return $this
     */
    public function setHidden(array $hidden)
    {
        $this->hidden = $hidden;
        return $this;
    }

    /**
     * Add hidden attributes for the model.
     * @param  array|string|null $attributes
     * @return void
     */
    public function addHidden($attributes = null)
    {
        $attributes = is_array($attributes) ? $attributes : func_get_args();
        $this->hidden = array_merge($this->hidden, $attributes);
    }

    /**
     * Make the given, typically hidden, attributes visible.
     * @param  array|string $attributes
     * @return $this
     */
    public function makeVisible($attributes)
    {
        $this->hidden = array_diff($this->hidden, (array)$attributes);
        if (!empty($this->visible)) {
            $this->addVisible($attributes);
        }
        return $this;
    }

    /**
     * Make the given, typically visible, attributes hidden.
     * @param  array|string $attributes
     * @return $this
     */
    public function makeHidden($attributes)
    {
        $attributes = (array)$attributes;
        $this->visible = array_diff($this->visible, $attributes);
        $this->hidden = array_unique(array_merge($this->hidden, $attributes));
        return $this;
    }

    /**
     * Make the given, typically hidden, attributes visible.
     * @param  array|string $attributes
     * @return $this
     * @deprecated since version 5.2. Use the "makeVisible" method directly.
     */
    public function withHidden($attributes)
    {
        return $this->makeVisible($attributes);
    }

    /**
     * Get the visible attributes for the model.
     * @return array
     */
    public function getVisible()
    {
        return $this->visible;
    }

    /**
     * Set the visible attributes for the model.
     * @param  array $visible
     * @return $this
     */
    public function setVisible(array $visible)
    {
        $this->visible = $visible;
        return $this;
    }

    /**
     * Add visible attributes for the model.
     * @param  array|string|null $attributes
     * @return void
     */
    public function addVisible($attributes = null)
    {
        $attributes = is_array($attributes) ? $attributes : func_get_args();
        $this->visible = array_merge($this->visible, $attributes);
    }

    /**
     * Set the accessors to append to model arrays.
     * @param  array $appends
     * @return $this
     */
    public function setAppends(array $appends)
    {
        $this->appends = $appends;
        return $this;
    }

    /**
     * Get the fillable attributes for the model.
     * @return array
     */
    public function getFillable()
    {
        return $this->fillable;
    }

    /**
     * Set the fillable attributes for the model.
     * @param  array $fillable
     * @return $this
     */
    public function fillable(array $fillable)
    {
        $this->fillable = $fillable;
        return $this;
    }

    /**
     * Get the guarded attributes for the model.
     * @return array
     */
    public function getGuarded()
    {
        return $this->guarded;
    }

    /**
     * Set the guarded attributes for the model.
     * @param  array $guarded
     * @return $this
     */
    public function guard(array $guarded)
    {
        $this->guarded = $guarded;
        return $this;
    }

    /**
     * Disable all mass assignable restrictions.
     * @param  bool $state
     * @return void
     */
    public static function unguard($state = true)
    {
        static::$unguarded = $state;
    }

    /**
     * Enable the mass assignment restrictions.
     * @return void
     */
    public static function reguard()
    {
        static::$unguarded = false;
    }

    /**
     * Determine if current state is "unguarded".
     * @return bool
     */
    public static function isUnguarded()
    {
        return static::$unguarded;
    }

    /**
     * Run the given callable while being unguarded.
     * @param  callable $callback
     * @return mixed
     */
    public static function unguarded(callable $callback)
    {
        if (static::$unguarded) {
            return $callback();
        }
        static::unguard();
        try {
            return $callback();
        } finally {
            static::reguard();
        }
    }

    /**
     * Determine if the given attribute may be mass assigned.
     * @param  string $key
     * @return bool
     */
    public function isFillable($key)
    {
        if (static::$unguarded) {
            return true;
        }
        if (in_array($key, $this->getFillable())) {
            return true;
        }
        if ($this->isGuarded($key)) {
            return false;
        }
        return empty($this->getFillable()) && !Str::startsWith($key, '_');
    }

    /**
     * Determine if the given key is guarded.
     * @param  string $key
     * @return bool
     */
    public function isGuarded($key)
    {
        return in_array($key, $this->getGuarded()) || $this->getGuarded() == ['*'];
    }

    /**
     * Determine if the model is totally guarded.
     * @return bool
     */
    public function totallyGuarded()
    {
        return count($this->getFillable()) == 0 && $this->getGuarded() == ['*'];
    }

    /**
     * Remove the table name from a given key.
     * @param  string $key
     * @return string
     */
    protected function removeTableFromKey($key)
    {
        if (!Str::contains($key, '.')) {
            return $key;
        }
        return last(explode('.', $key));
    }

    /**
     * Get the relationships that are touched on save.
     * @return array
     */
    public function getTouchedRelations()
    {
        return $this->touches;
    }

    /**
     * Set the relationships that are touched on save.
     * @param  array $touches
     * @return $this
     */
    public function setTouchedRelations(array $touches)
    {
        $this->touches = $touches;
        return $this;
    }

    /**
     * Get the value indicating whether the IDs are incrementing.
     * @return bool
     */
    public function getIncrementing()
    {
        return $this->incrementing;
    }

    /**
     * Set whether IDs are incrementing.
     * @param  bool $value
     * @return $this
     */
    public function setIncrementing($value)
    {
        $this->incrementing = $value;
        return $this;
    }

    /**
     * Convert the model instance to JSON.
     * @param  int $options
     * @return string
     */
    public function toJson($options = 0)
    {
//        return json_encode($this->jsonSerialize(), $options);
    }

    /**
     * Convert the object into something JSON serializable.
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->toArray();
    }

    /**
     * Convert the model instance to an array.
     * @return array
     */
    public function toArray()
    {
        $attributes = $this->attributesToArray();
        return array_merge($attributes, $this->relationsToArray());
    }

    /**
     * Convert the model's attributes to an array.
     * @return array
     */
    public function attributesToArray()
    {
        $attributes = $this->getArrayableAttributes();
        $mutatedAttributes = $this->getMutatedAttributes();
        foreach ($mutatedAttributes as $key) {
            if (!array_key_exists($key, $attributes)) {
                continue;
            }
            $attributes[$key] = $this->mutateAttributeForArray(
                $key, $attributes[$key]
            );
        }
        foreach ($this->getArrayableAppends() as $key) {
            $attributes[$key] = $this->mutateAttributeForArray($key, null);
        }
        return $attributes;
    }

    /**
     * Get an attribute array of all arrayable attributes.
     * @return array
     */
    protected function getArrayableAttributes()
    {
        return $this->getArrayableItems($this->attributes);
    }

    /**
     * Get all of the appendable values that are arrayable.
     * @return array
     */
    protected function getArrayableAppends()
    {
        if (!count($this->appends)) {
            return [];
        }
        return $this->getArrayableItems(
            array_combine($this->appends, $this->appends)
        );
    }

    /**
     * Get the model's relationships in array form.
     * @return array
     */
    public function relationsToArray()
    {
        $attributes = [];
        foreach ($this->getArrayableRelations() as $key => $value) {
            if ($value instanceof Arrayable) {
                $relation = $value->toArray();
            }
            elseif (is_null($value)) {
                $relation = $value;
            }
            if (static::$snakeAttributes) {
                $key = Str::snake($key);
            }
            if (isset($relation) || is_null($value)) {
                $attributes[$key] = $relation;
            }
            unset($relation);
        }
        return $attributes;
    }

    /**
     * Get an attribute array of all arrayable relations.
     * @return array
     */
    protected function getArrayableRelations()
    {
        return $this->getArrayableItems($this->relations);
    }

    /**
     * Get an attribute array of all arrayable values.
     * @param  array $values
     * @return array
     */
    protected function getArrayableItems(array $values)
    {
        if (count($this->getVisible()) > 0) {
            $values = array_intersect_key($values, array_flip($this->getVisible()));
        }

        if (count($this->getHidden()) > 0) {
            $values = array_diff_key($values, array_flip($this->getHidden()));
        }

        return $values;
    }

    /**
     * Get an attribute from the model.
     * @param  string $key
     * @return mixed
     */
    public function getAttribute($key)
    {
        if (array_key_exists($key, $this->attributes) || $this->hasGetMutator($key)) {
            return $this->getAttributeValue($key);
        }
        return $this->getRelationValue($key);
    }

    /**
     * Get a plain attribute (not a relationship).
     * @param  string $key
     * @return mixed
     */
    public function getAttributeValue($key)
    {
        $value = $this->getAttributeFromArray($key);
        if ($this->hasGetMutator($key)) {
            return $this->mutateAttribute($key, $value);
        }
        if ($this->hasCast($key)) {
            return $this->castAttribute($key, $value);
        }
        if (in_array($key, $this->getDates()) && !is_null($value)) {
            return $this->asDateTime($value);
        }
        return $value;
    }

    /**
     * Get a relationship.
     * @param  string $key
     * @return mixed
     */
    public function getRelationValue($key)
    {
        if ($this->relationLoaded($key)) {
            return $this->relations[$key];
        }
        if (method_exists($this, $key)) {
            return $this->getRelationshipFromMethod($key);
        }
    }

    /**
     * Get an attribute from the $attributes array.
     * @param  string $key
     * @return mixed
     */
    protected function getAttributeFromArray($key)
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }
    }

    /**
     * Get a relationship value from a method.
     * @param  string $method
     * @return mixed
     * @throws \Exception
     */
    protected function getRelationshipFromMethod($method)
    {
        $relations = $this->$method();
        if (!$relations instanceof Relation) {
            throw new \Exception('Relationship method must return an object of Relation');
        }
        $this->setRelation($method, $results = $relations->getResults());
        return $results;
    }

    /**
     * Determine if a get mutator exists for an attribute.
     * @param  string $key
     * @return bool
     */
    public function hasGetMutator($key)
    {
        return method_exists($this, 'get' . Str::studly($key) . 'Attribute');
    }

    /**
     * Get the value of an attribute using its mutator.
     * @param  string $key
     * @param  mixed $value
     * @return mixed
     */
    protected function mutateAttribute($key, $value)
    {
        return $this->{'get' . Str::studly($key) . 'Attribute'}($value);
    }

    /**
     * Get the value of an attribute using its mutator for array conversion.
     * @param  string $key
     * @param  mixed $value
     * @return mixed
     */
    protected function mutateAttributeForArray($key, $value)
    {
        $value = $this->mutateAttribute($key, $value);
        return $value instanceof Arrayable ? $value->toArray() : $value;
    }

    /**
     * Determine whether an attribute should be cast to a native type.
     * @param  string $key
     * @param  array|string|null $types
     * @return bool
     */
    public function hasCast($key, $types = null)
    {
        if (array_key_exists($key, $this->getCasts())) {
            return $types ? in_array($this->getCastType($key), (array)$types, true) : true;
        }
        return false;
    }

    /**
     * Get the casts array.
     * @return array
     */
    public function getCasts()
    {
        if ($this->getIncrementing()) {
            return array_merge([
                $this->getKeyName() => $this->keyType,
            ], $this->casts);
        }
        return $this->casts;
    }

    /**
     * Determine whether a value is Date / DateTime castable for inbound manipulation.
     * @param  string $key
     * @return bool
     */
    protected function isDateCastable($key)
    {
        return $this->hasCast($key, ['date', 'datetime']);
    }

    /**
     * Determine whether a value is JSON castable for inbound manipulation.
     * @param  string $key
     * @return bool
     */
    protected function isJsonCastable($key)
    {
        return $this->hasCast($key, ['array', 'json', 'object', 'collection']);
    }

    /**
     * Get the type of cast for a model attribute.
     * @param  string $key
     * @return string
     */
    protected function getCastType($key)
    {
        return trim(strtolower($this->getCasts()[$key]));
    }

    /**
     * Cast an attribute to a native PHP type.
     *
     * @param  string $key
     * @param  mixed $value
     * @return mixed
     */
    protected function castAttribute($key, $value)
    {
        if (is_null($value)) {
            return $value;
        }

        switch ($this->getCastType($key)) {
            case 'int':
            case 'integer':
                return (int)$value;
            case 'real':
            case 'float':
            case 'double':
                return (float)$value;
            case 'string':
                return (string)$value;
            case 'bool':
            case 'boolean':
                return (bool)$value;
            case 'object':
                return $this->fromJson($value, true);
            case 'array':
            case 'json':
                return $this->fromJson($value);
            case 'collection':
                return new BaseCollection($this->fromJson($value));
            case 'date':
            case 'datetime':
                return $this->asDateTime($value);
            case 'timestamp':
                return $this->asTimeStamp($value);
            default:
                return $value;
        }
    }

    /**
     * Set a given attribute on the model.
     * @param  string $key
     * @param  mixed $value
     * @return $this
     */
    public function setAttribute($key, $value)
    {
        if ($this->hasSetMutator($key)) {
            $method = 'set' . Str::studly($key) . 'Attribute';

            return $this->{$method}($value);
        }
        elseif ($value && (in_array($key, $this->getDates()) || $this->isDateCastable($key))) {
            $value = $this->fromDateTime($value);
        }
        if ($this->isJsonCastable($key) && !is_null($value)) {
            $value = $this->asJson($value);
        }
        $this->attributes[$key] = $value;
        return $this;
    }

    /**
     * Determine if a set mutator exists for an attribute.
     * @param  string $key
     * @return bool
     */
    public function hasSetMutator($key)
    {
        return method_exists($this, 'set' . Str::studly($key) . 'Attribute');
    }

    /**
     * 使用时间戳, 不自动格式化时间
     * @return array
     */
    public function getDates()
    {
        return [];
    }

    /**
     * Get the format for database stored dates.
     * @return string
     */
    protected function getDateFormat()
    {
        return $this->dateFormat ?: $this->getConnection()->getQueryGrammar()->getDateFormat();
    }

    /**
     * Set the date format used by the model.
     * @param  string $format
     * @return $this
     */
    public function setDateFormat($format)
    {
        $this->dateFormat = $format;
        return $this;
    }

    /**
     * Encode the given value as JSON.
     * @param  mixed $value
     * @return string
     */
    protected function asJson($value)
    {
        return json_encode($value);
    }

    /**
     * Decode the given JSON back into an array or object.
     * @param  string $value
     * @param  bool $asObject
     * @return mixed
     */
    public function fromJson($value, $asObject = false)
    {
        return json_decode($value, !$asObject);
    }

    /**
     * Clone the model into a new, non-existing instance.
     * @param  array|null $except
     * @return \Jeanku\Database\Eloquent\Model
     */
    public function replicate(array $except = null)
    {
        $defaults = [
            $this->getKeyName(),
            $this->getCreatedAtColumn(),
            $this->getUpdatedAtColumn(),
        ];
        $except = $except ? array_unique(array_merge($except, $defaults)) : $defaults;
        $attributes = Arr::except($this->attributes, $except);
        $instance = new static;
        $instance->setRawAttributes($attributes);
        return $instance->setRelations($this->relations);
    }

    /**
     * Get all of the current attributes on the model.
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Set the array of model attributes. No checking is done.
     * @param  array $attributes
     * @param  bool $sync
     * @return $this
     */
    public function setRawAttributes(array $attributes, $sync = false)
    {
        $this->attributes = $attributes;
        if ($sync) {
            $this->syncOriginal();
        }
        return $this;
    }

    /**
     * Get the model's original attribute values.
     * @param  string|null $key
     * @param  mixed $default
     * @return mixed|array
     */
    public function getOriginal($key = null, $default = null)
    {
        return isset($this->attributes[$key]) ? $this->attributes[$key] : $default;
    }

    /**
     * Sync the original attributes with the current.
     * @return $this
     */
    public function syncOriginal()
    {
        $this->original = $this->attributes;
        return $this;
    }

    /**
     * Sync a single original attribute with its current value.
     * @param  string $attribute
     * @return $this
     */
    public function syncOriginalAttribute($attribute)
    {
        $this->original[$attribute] = $this->attributes[$attribute];
        return $this;
    }

    /**
     * Determine if the model or given attribute(s) have been modified.
     * @param  array|string|null $attributes
     * @return bool
     */
    public function isDirty($attributes = null)
    {
        $dirty = $this->getDirty();
        if (is_null($attributes)) {
            return count($dirty) > 0;
        }
        if (!is_array($attributes)) {
            $attributes = func_get_args();
        }
        foreach ($attributes as $attribute) {
            if (array_key_exists($attribute, $dirty)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get the attributes that have been changed since last sync.
     * @return array
     */
    public function getDirty()
    {
        $dirty = [];
        foreach ($this->attributes as $key => $value) {
            if (!array_key_exists($key, $this->original)) {
                $dirty[$key] = $value;
            } elseif ($value !== $this->original[$key] &&
                !$this->originalIsNumericallyEquivalent($key)
            ) {
                $dirty[$key] = $value;
            }
        }
        return $dirty;
    }

    /**
     * Determine if the new and old values for a given key are numerically equivalent.
     * @param  string $key
     * @return bool
     */
    protected function originalIsNumericallyEquivalent($key)
    {
        $current = $this->attributes[$key];
        $original = $this->original[$key];
        return is_numeric($current) && is_numeric($original) && strcmp((string)$current, (string)$original) === 0;
    }

    /**
     * Get all the loaded relations for the instance.
     * @return array
     */
    public function getRelations()
    {
        return $this->relations;
    }

    /**
     * Get a specified relationship.
     * @param  string $relation
     * @return mixed
     */
    public function getRelation($relation)
    {
        return $this->relations[$relation];
    }

    /**
     * Determine if the given relation is loaded.
     * @param  string $key
     * @return bool
     */
    public function relationLoaded($key)
    {
        return array_key_exists($key, $this->relations);
    }

    /**
     * Set the specific relationship in the model.
     * @param  string $relation
     * @param  mixed $value
     * @return $this
     */
    public function setRelation($relation, $value)
    {
        $this->relations[$relation] = $value;
        return $this;
    }

    /**
     * Set the entire relations array on the model.
     * @param  array $relations
     * @return $this
     */
    public function setRelations(array $relations)
    {
        $this->relations = $relations;
        return $this;
    }

    /**
     * Get the database connection for the model.
     * @return \Jeanku\Database\Connection
     */
    public function getConnection()
    {
        return static::resolveConnection($this->getConnectionName());
    }

    /**
     * Get the current connection name for the model.
     * @return string
     */
    public function getConnectionName()
    {
        return $this->connection;
    }

    /**
     * Set the connection associated with the model.
     * @param  string $name
     * @return $this
     */
    public function setConnection($name)
    {
        $this->connection = $name;
        return $this;
    }

    /**
     * Resolve a connection instance.
     * @param  string|null $connection
     * @return \Jeanku\Database\Connection
     */
    public static function resolveConnection($connection = null)
    {
        return static::$resolver->connection($connection);
    }

    /**
     * Get the connection resolver instance.
     * @return $resolver
     */
    public static function getConnectionResolver()
    {
        return static::$resolver;
    }

    /**
     * Set the connection resolver instance.
     * @param  $resolver
     * @return void
     */
    public static function setConnectionResolver($resolver)
    {
        static::$resolver = $resolver;
    }

    /**
     * Unset the connection resolver for models.
     * @return void
     */
    public static function unsetConnectionResolver()
    {
        static::$resolver = null;
    }


    /**
     * Get the mutated attributes for a given instance.
     * @return array
     */
    public function getMutatedAttributes()
    {
        $class = static::class;
        if (!isset(static::$mutatorCache[$class])) {
            static::cacheMutatedAttributes($class);
        }
        return static::$mutatorCache[$class];
    }

    /**
     * Extract and cache all the mutated attributes of a class.
     * @param  string $class
     * @return void
     */
    public static function cacheMutatedAttributes($class)
    {
        $mutatedAttributes = [];
        if (preg_match_all('/(?<=^|;)get([^;]+?)Attribute(;|$)/', implode(';', get_class_methods($class)), $matches)) {
            foreach ($matches[1] as $match) {
                if (static::$snakeAttributes) {
                    $match = Str::snake($match);
                }
                $mutatedAttributes[] = lcfirst($match);
            }
        }
        static::$mutatorCache[$class] = $mutatedAttributes;
    }

    /**
     * Dynamically retrieve attributes on the model.
     * @param  string $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->getAttribute($key);
    }

    /**
     * Dynamically set attributes on the model.
     * @param  string $key
     * @param  mixed $value
     * @return void
     */
    public function __set($key, $value)
    {
        $this->setAttribute($key, $value);
    }

    /**
     * Determine if an attribute or relation exists on the model.
     * @param  string $key
     * @return bool
     */
    public function __isset($key)
    {
        return !is_null($this->getAttribute($key));
    }

    /**
     * Unset an attribute on the model.
     * @param  string $key
     * @return void
     */
    public function __unset($key)
    {
        unset($this->attributes[$key], $this->relations[$key]);
    }

    /**
     * Handle dynamic method calls into the model.
     * @param  string $method
     * @param  array $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        $query = $this->newQuery();
        return call_user_func_array([$query, $method], $parameters);
    }

    /**
     * Handle dynamic static method calls into the method.
     * @param  string $method
     * @param  array $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        $instance = new static;
        return call_user_func_array([$instance, $method], $parameters);
    }

    /**
     * Convert the model to its string representation.
     * @return string
     */
    public function __toString()
    {
        return $this->toJson();
    }

    /**
     * When a model is being unserialized, check if it needs to be booted.
     * @return void
     */
    public function __wakeup()
    {
        $this->bootIfNotBooted();
    }
}
