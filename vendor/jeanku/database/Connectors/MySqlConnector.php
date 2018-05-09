<?php

namespace Jeanku\Database\Connectors;

use PDO;

class MySqlConnector
{
    /**
     * Establish a database connection.
     * @param  array  $config
     * @return \PDO
     */
    public function connect(array $config)
    {
        $dsn = $this->getDsn($config);
        $connection = $this->createConnection($dsn, $config);
        //use database
        if (!empty($config['database'])) {
            $connection->exec("use `{$config['database']}`;");
        }
        //set charset
        if (isset($config['charset'])) {
            $charset = $config['charset'];
            $collation = $config['collation'];
            $names = "set names '$charset'". (! is_null($collation) ? " collate '$collation'" : '');
            $connection->prepare($names)->execute();
        }
        //$this->setModes($connection, $config);
        return $connection;
    }

    /**
     * Create a DSN string from a configuration.
     * Chooses socket or host/port based on the 'unix_socket' config value.
     * @param  array   $config
     * @return string
     */
    protected function getDsn(array $config)
    {
        return $this->configHasSocket($config) ? $this->getSocketDsn($config) : $this->getHostDsn($config);
    }

    /**
     * Determine if the given configuration array has a UNIX socket value.
     * @param  array  $config
     * @return bool
     */
    protected function configHasSocket(array $config)
    {
        return isset($config['unix_socket']) && ! empty($config['unix_socket']);
    }

    /**
     * Get the DSN string for a socket configuration.
     * @param  array  $config
     * @return string
     */
    protected function getSocketDsn(array $config)
    {
        return "mysql:unix_socket={$config['unix_socket']};dbname={$config['database']}";
    }

    /**
     * Get the DSN string for a host / port configuration.
     * @param  array  $config
     * @return string
     */
    protected function getHostDsn(array $config)
    {
        return isset($port) ? "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']}"
                        : "mysql:host={$config['host']};dbname={$config['database']}";
    }

    /**
     * Set the modes for the connection.
     * @param  \PDO  $connection
     * @param  array  $config
     * @return void
     */
    protected function setModes(PDO $connection, array $config)
    {
        if (isset($config['modes'])) {
            $modes = implode(',', $config['modes']);
            $connection->prepare("set session sql_mode='".$modes."'")->execute();
        } elseif (isset($config['strict'])) {
            if ($config['strict']) {
                $connection->prepare("set session sql_mode='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'")->execute();
            } else {
                $connection->prepare("set session sql_mode='NO_ENGINE_SUBSTITUTION'")->execute();
            }
        }
    }

    /**
     * Create a new PDO connection.
     *
     * @param  string $dsn
     * @param  array $config
     * @param  array $options
     * @return PDO
     * @throws \Exception
     */
    public function createConnection($dsn, array $config, array $options = [])
    {
        try {
            $pdo = new PDO($dsn, $config['username'], $config['password'], $options);
        } catch (\Exception $e) {
            throw $e;
        }
        return $pdo;
    }
}
