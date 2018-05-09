<?php

namespace Jeanku\Database;

use PDO;
use Closure;
use Exception;
use Throwable;
use DateTimeInterface;
use Jeanku\Database\Support\Arr;
use Jeanku\Database\Support\Log;
use Jeanku\Database\Query\Expression;
use Jeanku\Database\Query\Builder as QueryBuilder;
use Jeanku\Database\Query\Grammars\Grammar as QueryGrammar;

class Connection
{

    /**
     * The active PDO connection.
     * @var PDO
     */
    protected $pdo;

    /**
     * The active PDO connection used for reads.
     * @var PDO
     */
    protected $readPdo;

    /**
     * The reconnector instance for the connection.
     * @var callable
     */
    protected $reconnector;

    /**
     * The query grammar implementation.
     * @var \Jeanku\Database\Query\Grammars\Grammar
     */
    protected $queryGrammar;

    /**
     * The default fetch mode of the connection.
     * @var int
     */
    protected $fetchMode = PDO::FETCH_OBJ;

    /**
     * The argument for the fetch mode.
     * @var mixed
     */
    protected $fetchArgument;

    /**
     * The constructor arguments for the PDO::FETCH_CLASS fetch mode.
     * @var array
     */
    protected $fetchConstructorArgument = [];

    /**
     * The number of active transactions.
     * @var int
     */
    protected $transactions = 0;

    /**
     * All of the queries run against the connection.
     * @var array
     */
    protected $queryLog = [];

    /**
     * Indicates whether queries are being logged.
     * @var bool
     */
    protected $loggingQueries = false;

    /**
     * Indicates if the connection is in a "dry run".
     * @var bool
     */
    protected $pretending = false;

    /**
     * The name of the connected database.
     * @var string
     */
    protected $database;


    /**
     * The table prefix for the connection.
     * @var string
     */
    protected $tablePrefix = '';

    /**
     * The database connection configuration options.
     * @var array
     */
    protected $config = [];

    /**
     * Create a new database connection instance.
     * @param  \PDO|\Closure $pdo
     * @param  string $database
     * @param  string $tablePrefix
     * @param  array $config
     * @return void
     */
    public function __construct($pdo, $database = '', $tablePrefix = '', array $config = [])
    {
        $this->pdo = $pdo;
        $this->database = $database;
        $this->tablePrefix = $tablePrefix;
        $this->config = $config;
        $this->queryGrammar = $this->getDefaultQueryGrammar();
    }

    /**
     * Get the default query grammar instance.
     * @return \Jeanku\Database\Query\Grammars\MySqlGrammar
     */
    protected function getDefaultQueryGrammar()
    {
        return new QueryGrammar;
    }

    /**
     * Begin a fluent query against a database table.
     * @param  string $table
     * @return \Jeanku\Database\Query\Builder
     */
    public function table($table)
    {
        return $this->query()->from($table);
    }

    /**
     * Get a new query builder instance.
     * @return \Jeanku\Database\Query\Builder
     */
    public function query()
    {
        return new QueryBuilder($this, $this->getQueryGrammar());
    }

    /**
     * Get a new raw query expression.
     * @param  mixed $value
     * @return \Jeanku\Database\Query\Expression
     */
    public function raw($value)
    {
        return new Expression($value);
    }

    /**
     * Run a select statement and return a single result.
     * @param  string $query
     * @param  array $bindings
     * @return mixed
     */
    public function selectOne($query, $bindings = [])
    {
        $records = $this->select($query, $bindings);
        return count($records) > 0 ? reset($records) : null;
    }

    /**
     * Run a select statement against the database.
     * @param  string $query
     * @param  array $bindings
     * @return array
     */
    public function selectFromWriteConnection($query, $bindings = [])
    {
        return $this->select($query, $bindings, false);
    }

    /**
     * Run a select statement against the database.
     * @param  string $query
     * @param  array $bindings
     * @param  bool $useReadPdo
     * @return array
     */
    public function select($query, $bindings = [], $useReadPdo = true)
    {
        return $this->run($query, $bindings, function ($me, $query, $bindings) use ($useReadPdo) {
            if ($me->pretending()) {
                return [];
            }
            $statement = $this->getPdoForSelect($useReadPdo)->prepare($query);
            $statement->execute($me->prepareBindings($bindings));
            $fetchMode = $me->getFetchMode();
            $fetchArgument = $me->getFetchArgument();
            $fetchConstructorArgument = $me->getFetchConstructorArgument();
            if ($fetchMode === PDO::FETCH_CLASS && !isset($fetchArgument)) {
                $fetchArgument = 'StdClass';
                $fetchConstructorArgument = null;
            }
            return isset($fetchArgument)
                ? $statement->fetchAll($fetchMode, $fetchArgument, $fetchConstructorArgument)
                : $statement->fetchAll($fetchMode);
        });
    }

    /**
     * Run a select statement against the database and returns a generator.
     *
     * @param  string $query
     * @param  array $bindings
     * @param  bool $useReadPdo
     * @return \Generator
     */
    public function cursor($query, $bindings = [], $useReadPdo = true)
    {
        $statement = $this->run($query, $bindings, function ($me, $query, $bindings) use ($useReadPdo) {
            if ($me->pretending()) {
                return [];
            }
            $statement = $this->getPdoForSelect($useReadPdo)->prepare($query);
            $fetchMode = $me->getFetchMode();
            $fetchArgument = $me->getFetchArgument();
            $fetchConstructorArgument = $me->getFetchConstructorArgument();
            if ($fetchMode === PDO::FETCH_CLASS && !isset($fetchArgument)) {
                $fetchArgument = 'StdClass';
                $fetchConstructorArgument = null;
            }
            if (isset($fetchArgument)) {
                $statement->setFetchMode($fetchMode, $fetchArgument, $fetchConstructorArgument);
            } else {
                $statement->setFetchMode($fetchMode);
            }
            $statement->execute($me->prepareBindings($bindings));
            return $statement;
        });
        while ($record = $statement->fetch()) {
            yield $record;
        }
    }

    /**
     * Get the PDO connection to use for a select query.
     * @param  bool $useReadPdo
     * @return \PDO
     */
    protected function getPdoForSelect($useReadPdo = true)
    {
        return !$useReadPdo ? $this->getReadPdo() : $this->getPdo();
    }

    /**
     * Run an insert statement against the database.
     * @param  string $query
     * @param  array $bindings
     * @return bool
     */
    public function insert($query, $bindings = [])
    {
        return $this->statement($query, $bindings);
    }

    /**
     * Run an update statement against the database.
     * @param  string $query
     * @param  array $bindings
     * @return int
     */
    public function update($query, $bindings = [])
    {
        return $this->affectingStatement($query, $bindings);
    }

    /**
     * Run a delete statement against the database.
     * @param  string $query
     * @param  array $bindings
     * @return int
     */
    public function delete($query, $bindings = [])
    {
        return $this->affectingStatement($query, $bindings);
    }

    /**
     * Execute an SQL statement and return the boolean result.
     *
     * @param  string $query
     * @param  array $bindings
     * @return bool
     */
    public function statement($query, $bindings = [])
    {
        return $this->run($query, $bindings, function ($me, $query, $bindings) {
            if ($me->pretending()) {
                return true;
            }
            $bindings = $me->prepareBindings($bindings);
            return $me->getPdo()->prepare($query)->execute($bindings);
        });
    }

    /**
     * Run an SQL statement and get the number of rows affected.
     * @param  string $query
     * @param  array $bindings
     * @return int
     */
    public function affectingStatement($query, $bindings = [])
    {
        return $this->run($query, $bindings, function ($me, $query, $bindings) {
            if ($me->pretending()) {
                return 0;
            }
            $statement = $me->getPdo()->prepare($query);
            $statement->execute($me->prepareBindings($bindings));
            return $statement->rowCount();
        });
    }

    /**
     * Run a raw, unprepared query against the PDO connection.
     * @param  string $query
     * @return bool
     */
    public function unprepared($query)
    {
        return $this->run($query, [], function ($me, $query) {
            if ($me->pretending()) {
                return true;
            }
            return (bool)$me->getPdo()->exec($query);
        });
    }

    /**
     * Prepare the query bindings for execution.
     * @param  array $bindings
     * @return array
     */
    public function prepareBindings(array $bindings)
    {
        $grammar = $this->getQueryGrammar();
        foreach ($bindings as $key => $value) {
            if ($value instanceof DateTimeInterface) {
                $bindings[$key] = $value->format($grammar->getDateFormat());
            } elseif ($value === false) {
                $bindings[$key] = 0;
            }
        }
        return $bindings;
    }

    /**
     * Execute a Closure within a transaction.
     * @param  \Closure $callback
     * @return mixed
     * @throws \Exception|\Throwable
     */
    public function transaction(Closure $callback)
    {
        $this->beginTransaction();
        try {
            $result = $callback($this);
            $this->commit();
        } catch (Exception $e) {
            $this->rollBack();
            throw $e;
        } catch (Throwable $e) {
            $this->rollBack();
            throw $e;
        }
        return $result;
    }

    /**
     * Start a new database transaction.
     * @return void
     * @throws Exception
     */
    public function beginTransaction()
    {
        ++$this->transactions;
        if ($this->transactions == 1) {
            try {
                $this->getPdo()->beginTransaction();
            } catch (Exception $e) {
                --$this->transactions;

                throw $e;
            }
        } elseif ($this->transactions > 1 && $this->queryGrammar->supportsSavepoints()) {
            $this->getPdo()->exec(
                $this->queryGrammar->compileSavepoint('trans'.$this->transactions)
            );
        }
    }

    /**
     * Commit the active database transaction.
     * @return void
     */
    public function commit()
    {
        if ($this->transactions == 1) {
            $this->getPdo()->commit();
        }
        $this->transactions = max(0, $this->transactions - 1);
    }

    /**
     * Rollback the active database transaction.
     * @return void
     */
    public function rollBack()
    {
        if ($this->transactions == 1) {
            $this->getPdo()->rollBack();
        } elseif ($this->transactions > 1 && $this->queryGrammar->supportsSavepoints()) {
            $this->getPdo()->exec(
                $this->queryGrammar->compileSavepointRollBack('trans'.$this->transactions)
            );
        }
        $this->transactions = max(0, $this->transactions - 1);
    }

    /**
     * Get the number of active transactions.
     * @return int
     */
    public function transactionLevel()
    {
        return $this->transactions;
    }

    /**
     * Execute the given callback in "dry run" mode.
     *
     * @param  \Closure $callback
     * @return array
     */
    public function pretend(Closure $callback)
    {
        $loggingQueries = $this->loggingQueries;
        $this->enableQueryLog();
        $this->pretending = true;
        $this->queryLog = [];
        $callback($this);
        $this->pretending = false;
        $this->loggingQueries = $loggingQueries;
        return $this->queryLog;
    }

    /**
     * Run a SQL statement and log its execution context.
     * @param  string $query
     * @param  array $bindings
     * @param  \Closure $callback
     * @return mixed
     * @throws \Exception
     */
    protected function run($query, $bindings, Closure $callback)
    {
        $start = microtime(true);
        try {
            $result = $this->runQueryCallback($query, $bindings, $callback);
        } catch (\Exception $e) {
            throw new \Exception('sql not valid exception', -1);
        }
        $time = $this->getElapsedTime($start);
        $this->logQuery($query, $bindings, $time);
        return $result;
    }

    /**
     * Run a SQL statement.
     * @param  string $query
     * @param  array $bindings
     * @param  \Closure $callback
     * @return mixed
     * @throws \Jeanku\Database\Exception
     */
    protected function runQueryCallback($query, $bindings, Closure $callback)
    {
        $result = $callback($this, $query, $bindings);
        return $result;
    }

    /**
     * Handle a query exception that occurred during query execution.
     * @param Exception|\Jeanku\Database\Exception $e
     * @param  string $query
     * @param  array $bindings
     * @param  \Closure $callback
     * @return mixed
     * @throws Exception
     */
    protected function tryAgainIfCausedByLostConnection(\Exception $e, $query, $bindings, Closure $callback)
    {
        if ($this->causedByLostConnection($e->getPrevious())) {
            $this->reconnect();
            return $this->runQueryCallback($query, $bindings, $callback);
        }

        throw $e;
    }

    /**
     * Disconnect from the underlying PDO connection.
     * @return void
     */
    public function disconnect()
    {
        $this->setPdo(null)->setReadPdo(null);
    }

    /**
     * Reconnect to the database.
     * @return void
     * @throws \Exception
     */
    public function reconnect()
    {
        if (is_callable($this->reconnector)) {
            return call_user_func($this->reconnector, $this);
        }
        throw new \Exception('Lost connection and no reconnector available.', -1);
    }

    /**
     * Reconnect to the database if a PDO connection is missing.
     *
     * @return void
     */
    protected function reconnectIfMissingConnection()
    {
        if (is_null($this->getPdo()) || is_null($this->getReadPdo())) {
            $this->reconnect();
        }
    }

    /**
     * Log a query in the connection's query log.
     *
     * @param  string $query
     * @param  array $bindings
     * @param  float|null $time
     * @return void
     */
    public function logQuery($query, $bindings, $time = null)
    {
        $query = vsprintf(str_replace('?', '%s', $query), $bindings);
        Log::notice("【SQL】:$query; 【time】:{$time}");
    }


    /**
     * Get the elapsed time since a given starting point.
     *
     * @param  int $start
     * @return float
     */
    protected function getElapsedTime($start)
    {
        return round((microtime(true) - $start) * 1000, 2);
    }

    /**
     * Is Doctrine available?
     *
     * @return bool
     */
    public function isDoctrineAvailable()
    {
        return class_exists('Doctrine\DBAL\Connection');
    }


    /**
     * Get the current PDO connection.
     *
     * @return \PDO
     */
    public function getPdo()
    {
        if ($this->pdo instanceof Closure) {
            return $this->pdo = call_user_func($this->pdo);
        }
        return $this->pdo;
    }

    /**
     * Get the current PDO connection used for reading.
     *
     * @return \PDO
     */
    public function getReadPdo()
    {
        if ($this->transactions >= 1) {
            return $this->getPdo();
        }
        return $this->readPdo ?: $this->getPdo();
    }

    /**
     * Set the PDO connection.
     * @param  \PDO|null $pdo
     * @return $this
     * @throws \Exception
     */
    public function setPdo($pdo)
    {
        if ($this->transactions >= 1) {
            throw new \Exception("Can't swap PDO instance while within transaction.", -1);
        }
        $this->pdo = $pdo;
        return $this;
    }

    /**
     * Set the PDO connection used for reading.
     *
     * @param  \PDO|null $pdo
     * @return $this
     */
    public function setReadPdo($pdo)
    {
        $this->readPdo = $pdo;

        return $this;
    }

    /**
     * Set the reconnect instance on the connection.
     *
     * @param  callable $reconnector
     * @return $this
     */
    public function setReconnector(callable $reconnector)
    {
        $this->reconnector = $reconnector;

        return $this;
    }

    /**
     * Get the database connection name.
     *
     * @return string|null
     */
    public function getName()
    {
        return $this->getConfig('name');
    }

    /**
     * Get an option from the configuration options.
     *
     * @param  string $option
     * @return mixed
     */
    public function getConfig($option)
    {
        return Arr::get($this->config, $option);
    }

    /**
     * Get the PDO driver name.
     *
     * @return string
     */
    public function getDriverName()
    {
        return $this->getConfig('driver');
    }

    /**
     * Get the query grammar used by the connection.
     *
     * @return \Jeanku\Database\Query\Grammars\Grammar
     */
    public function getQueryGrammar()
    {
        return $this->queryGrammar;
    }

    /**
     * Set the query grammar used by the connection.
     *
     * @param  \Jeanku\Database\Query\Grammars\Grammar $grammar
     * @return void
     */
    public function setQueryGrammar(Query\Grammars\Grammar $grammar)
    {
        $this->queryGrammar = $grammar;
    }

    /**
     * Determine if the connection in a "dry run".
     *
     * @return bool
     */
    public function pretending()
    {
        return $this->pretending === true;
    }

    /**
     * Get the default fetch mode for the connection.
     *
     * @return int
     */
    public function getFetchMode()
    {
        return $this->fetchMode;
    }

    /**
     * Get the fetch argument to be applied when selecting.
     *
     * @return mixed
     */
    public function getFetchArgument()
    {
        return $this->fetchArgument;
    }

    /**
     * Get custom constructor arguments for the PDO::FETCH_CLASS fetch mode.
     *
     * @return array
     */
    public function getFetchConstructorArgument()
    {
        return $this->fetchConstructorArgument;
    }

    /**
     * Set the default fetch mode for the connection, and optional arguments for the given fetch mode.
     *
     * @param  int $fetchMode
     * @param  mixed $fetchArgument
     * @param  array $fetchConstructorArgument
     * @return int
     */
    public function setFetchMode($fetchMode, $fetchArgument = null, array $fetchConstructorArgument = [])
    {
        $this->fetchMode = $fetchMode;
        $this->fetchArgument = $fetchArgument;
        $this->fetchConstructorArgument = $fetchConstructorArgument;
    }

    /**
     * Get the connection query log.
     *
     * @return array
     */
    public function getQueryLog()
    {
        return $this->queryLog;
    }

    /**
     * Clear the query log.
     *
     * @return void
     */
    public function flushQueryLog()
    {
        $this->queryLog = [];
    }

    /**
     * Enable the query log on the connection.
     *
     * @return void
     */
    public function enableQueryLog()
    {
        $this->loggingQueries = true;
    }

    /**
     * Disable the query log on the connection.
     *
     * @return void
     */
    public function disableQueryLog()
    {
        $this->loggingQueries = false;
    }

    /**
     * Determine whether we're logging queries.
     *
     * @return bool
     */
    public function logging()
    {
        return $this->loggingQueries;
    }

    /**
     * Get the name of the connected database.
     *
     * @return string
     */
    public function getDatabaseName()
    {
        return $this->database;
    }

    /**
     * Set the name of the connected database.
     *
     * @param  string $database
     * @return string
     */
    public function setDatabaseName($database)
    {
        $this->database = $database;
    }

    /**
     * Get the table prefix for the connection.
     *
     * @return string
     */
    public function getTablePrefix()
    {
        return $this->tablePrefix;
    }

    /**
     * Set the table prefix in use by the connection.
     *
     * @param  string $prefix
     * @return void
     */
    public function setTablePrefix($prefix)
    {
        $this->tablePrefix = $prefix;

        $this->getQueryGrammar()->setTablePrefix($prefix);
    }

    /**
     * Set the table prefix and return the grammar.
     *
     * @param  \Jeanku\Database\Grammar $grammar
     * @return \Jeanku\Database\Grammar
     */
    public function withTablePrefix($grammar)
    {
        $grammar->setTablePrefix($this->tablePrefix);
        return $grammar;
    }
}
