<?php

namespace Jeanku\Database\Connectors;

use Jeanku\Database\MySqlConnection;

class ConnectionFactory
{

    /**
     * Establish a PDO connection based on the configuration.
     * @param  array   $config
     * @param  string  $name
     * @return \Jeanku\Database\Connection
     */
    public function make(array $config, $name = null)
    {
        $config = $this->parseConfig($config, $name);
        return $this->createSingleConnection($config);
    }

    /**
     * Create a single database connection instance.
     * @param  array  $config
     * @return \Jeanku\Database\Connection
     */
    protected function createSingleConnection(array $config)
    {
        $pdo = function () use ($config) {
            return $this->createConnector($config)->connect($config);
        };
        return $this->createConnection($config['driver'], $pdo, $config['database'], $config['prefix'], $config);
    }

    /**
     * Parse and prepare the database configuration.
     * @param  array   $config
     * @param  string  $name
     * @return array
     */
    protected function parseConfig(array $config, $name)
    {
        $config['name'] = $name;
        return $config;
    }

    /**
     * Create a connector instance based on the configuration.
     * @param  array  $config
     * @return \Jeanku\Database\Connectors\ConnectorInterface
     * @throws \Exception
     */
    public function createConnector(array $config)
    {
        if (!isset($config['driver'])) {
            throw new \Exception('A driver must be specified.', -1);
        }
        switch ($config['driver']) {
            case 'mysql':
                return new MySqlConnector;
        }
        throw new \Exception("Unsupported driver [{$config['driver']}]", -1);
    }

    /**
     * Create a new connection instance.
     * @param  string   $driver
     * @param  \PDO|\Closure     $connection
     * @param  string   $database
     * @param  string   $prefix
     * @param  array    $config
     * @return \Jeanku\Database\Connection
     * @throws \Exception
     */
    protected function createConnection($driver, $connection, $database, $prefix = '', array $config = [])
    {
        switch ($driver) {
            case 'mysql':
                return new MySqlConnection($connection, $database, $prefix, $config);
        }
        throw new \Exception("Unsupported driver [$driver]", -1);
    }
}
