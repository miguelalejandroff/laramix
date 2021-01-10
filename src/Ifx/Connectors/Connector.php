<?php

namespace Archytech\Laravel\Ifx\Connectors;

use Exception;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Database\Connectors\Connector as BaseConnector;
use Illuminate\Database\Connectors\ConnectorInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use PDO;

class Connector extends BaseConnector implements ConnectorInterface
{
    protected $encrypter;

    /**
     * The PDO connector constructor.
     *
     * @param $encrypter
     */
    public function __construct(Encrypter $encrypter)
    {
        $this->encrypter = $encrypter;
    }

    /**
     * Create Connection PDO.
     *
     * @param string $dsn
     * @param array  $config
     * @param array  $options
     *
     * @throws Exception
     *
     * @return PDO
     */
    public function createConnection($dsn, array $config, array $options)
    {
        $username = Arr::get($config, 'username');

        $password = Arr::get($config, 'password');

        if ($this->encrypter && strlen($password) > 50) {
            if (Str::startsWith('base64:', $password)) {
                $password = $this->encrypter->decrypt(substr($password, 7));
            } else {
                $password = $this->encrypter->decrypt($password);
            }
        }

        try {
            $pdo = new PDO($dsn, $username, $password, $options);
        } catch (Exception $e) {
            $pdo = $this->tryAgainIfCausedByLostConnection(
                $e,
                $dsn,
                $username,
                $password,
                $options
            );
        }

        return $pdo;
    }

    /**
     * @param array $config
     *
     * @throws Exception
     *
     * @return PDO
     */
    public function connect(array $config)
    {
        $dsn = $this->getDsn($config);

        $opt = $this->getOptions($config);

        /*
         * We need to grab the PDO options that should be used while making the brand
         * new connection instance. The PDO options control various aspects of the
         * connection tests behavior, and some might be specified by the developers.
         */
        $conn = $this->createConnection($dsn, $config, $opt);

        if (Arr::get($config, 'initSqls', false)) {
            if (is_string($config['initSqls'])) {
                $conn->exec($config['initSqls']);
            }
            if (is_array($config['initSqls'])) {
                $conn->exec(implode('; ', $config['initSqls']));
            }
        }

        return $conn;
    }

    /**
     * Create a DSN string from a configuration.
     * Chooses socket or host/port based on the 'unix_socket' config value.
     *
     * @param array $config
     *
     * @return string
     */
    protected function getDsn(array $config)
    {
        return "informix:host={$config['host']}; database={$config['database']}; service={$config['service']}; server={$config['server']}; ".$this->getDsnOption($config);
    }

    /**
     * Create a DSN option string from a configuration.
     *
     * @param array $config
     *
     * @return string
     */
    protected function getDsnOption(array $config)
    {
        $options = 'protocol='.Arr::get($config, 'protocol', 'onsoctcp').';';

        if (isset($config['enable_scroll'])) {
            $options .= " EnableScrollableCursors={$config['enable_scroll']};";
        }

        if (isset($config['db_locale'])) {
            $options .= " DB_LOCALE={$config['db_locale']};";
        }

        if (isset($config['client_locale'])) {
            $options .= " CLIENT_LOCALE={$config['client_locale']};";
        }

        return $options;
    }
}
