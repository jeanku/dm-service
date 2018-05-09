<?php

namespace Jeanku\Database;

use Jeanku\Database\Connectors\ConnectionFactory;

class DatabaseManager
{

    public static $config = [];

    /**
     * The database connection factory instance.
     *
     * @var \Jeanku\Database\Connectors\ConnectionFactory
     */
    protected $factory;

    /**
     * The active connection instances.
     *
     * @var array
     */
    protected $connections = [];

    /**
     * The custom connection resolvers.
     *
     * @var array
     */
    protected $extensions = [];


    public static $instance = null;                                         //单例模式


    public static function make($path)
    {
        if (empty(self::$config)) {
            self::$config = require($path);
        }
    }

    /**
     * Create a new database manager instance.
     *
     * @param   Application  $app
     * @param  \Jeanku\Database\Connectors\ConnectionFactory  $factory
     *
     * @return void
     */
    public function __construct($app = null, ConnectionFactory $factory = null)
    {
        $this->factory = $factory ? $factory : new ConnectionFactory(new \stdClass());
    }


    /**
     * Get a database connection instance.
     *
     * @param  string  $name
     * @return \Jeanku\Database\Connection
     */
    public function connection($name = null)
    {
        list($name, $type) = $this->parseConnectionName($name);
        if (!isset($this->connections[$name])) {
            $connection = $this->makeConnection($name);

            $this->setPdoForType($connection, $type);

            $this->connections[$name] = $this->prepare($connection);
        }
        return $this->connections[$name];
    }

    /**
     * Parse the connection into an array of the name and read / write type.
     *
     * @param  string  $name
     * @return array
     */
    protected function parseConnectionName($name)
    {
        $name = $name ?: $this->getDefaultConnection();
        return strpos($name, '::') ? explode('::', $name, 2) : [$name, null];
    }

    /**
     * Disconnect from the given database and remove from local cache.
     *
     * @param  string  $name
     * @return void
     */
    public function purge($name = null)
    {
        $this->disconnect($name);

        unset($this->connections[$name]);
    }

    /**
     * Disconnect from the given database.
     *
     * @param  string  $name
     * @return void
     */
    public function disconnect($name = null)
    {
        if (isset($this->connections[$name = $name ?: $this->getDefaultConnection()])) {
            $this->connections[$name]->disconnect();
        }
    }

    /**
     * Reconnect to the given database.
     *
     * @param  string  $name
     * @return \Jeanku\Database\Connection
     */
    public function reconnect($name = null)
    {
        $this->disconnect($name = $name ?: $this->getDefaultConnection());

        if (! isset($this->connections[$name])) {
            return $this->connection($name);
        }

        return $this->refreshPdoConnections($name);
    }

    /**
     * Refresh the PDO connections on a given connection.
     *
     * @param  string  $name
     * @return \Jeanku\Database\Connection
     */
    protected function refreshPdoConnections($name)
    {
        $fresh = $this->makeConnection($name);

        return $this->connections[$name]
                                ->setPdo($fresh->getPdo())
                                ->setReadPdo($fresh->getReadPdo());
    }

    /**
     * Make the database connection instance.
     *
     * @param  string  $name
     * @return \Jeanku\Database\Connection
     */
    protected function makeConnection($name)
    {
        $config = $this->getConfig($name);                                                              //获取db配置信息
        return $this->factory->make($config, $name);
    }

    /**
     * Prepare the database connection instance.
     *
     * @param  \Jeanku\Database\Connection  $connection
     * @return \Jeanku\Database\Connection
     */
    protected function prepare($connection)
    {
        $connection->setFetchMode(\PDO::FETCH_CLASS);
        $connection->setReconnector(function ($connection) {
            $this->reconnect($connection->getName());
        });
        return $connection;
    }

    /**
     * Prepare the read write mode for database connection instance.
     *
     * @param  \Jeanku\Database\Connection  $connection
     * @param  string  $type
     * @return \Jeanku\Database\Connection
     */
    protected function setPdoForType($connection, $type = null)
    {
        if ($type == 'read') {
            $connection->setPdo($connection->getReadPdo());
        } elseif ($type == 'write') {
            $connection->setReadPdo($connection->getPdo());
        }
        return $connection;
    }

    /**
     * Get the configuration for a connection.
     *
     * @param  string  $name
     * @return array
     *
     * @throws \Exception
     */
    protected function getConfig($name)
    {
        $name = $name ?: $this->getDefaultConnection();
        $connections = self::$config['connections'];
        if (empty($connections[$name])) {
            throw new \Exception("Database [$name] not configured.", -1);
        }
        return $connections[$name];
    }

    /**
     * Get the default connection name.
     *
     * @return string
     */
    public function getDefaultConnection()
    {
        return self::$config['default'];
    }

    /**
     * Set the default connection name.
     *
     * @param  string  $name
     * @return void
     */
    public function setDefaultConnection($name)
    {
        $this->app['config']['database.default'] = $name;
    }

    /**
     * Get all of the support drivers.
     *
     * @return array
     */
    public function supportedDrivers()
    {
        return ['mysql', 'pgsql', 'sqlite', 'sqlsrv'];
    }

    /**
     * Get all of the drivers that are actually available.
     *
     * @return array
     */
    public function availableDrivers()
    {
        return array_intersect($this->supportedDrivers(), str_replace('dblib', 'sqlsrv', \PDO::getAvailableDrivers()));
    }

    /**
     * Register an extension connection resolver.
     *
     * @param  string    $name
     * @param  callable  $resolver
     * @return void
     */
    public function extend($name, callable $resolver)
    {
        $this->extensions[$name] = $resolver;
    }

    /**
     * Return all of the created connections.
     *
     * @return array
     */
    public function getConnections()
    {
        return $this->connections;
    }

    /**
     * Dynamically pass methods to the default connection.
     *
     * @param  string  $method
     * @param  array   $parameters
     * @return mixed
     */
    public function __call($method, $parameters)
    {
        return call_user_func_array([$this->connection(), $method], $parameters);
    }

}
