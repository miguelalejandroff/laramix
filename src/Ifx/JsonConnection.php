<?php

namespace Miguelalejandroff\Laravel\Ifx;

use Miguelalejandroff\Laravel\Ifx\Query\Grammars\Grammar as QueryGrammarIfx;
use Miguelalejandroff\Laravel\Ifx\Query\Processors\Processor as QueryProcessorIfx;
use Miguelalejandroff\Laravel\Ifx\Schema\Builder;
use Miguelalejandroff\Laravel\Ifx\Schema\Grammars\Grammar as SchemaGrammarIfx;
use Closure;
use DateTimeInterface;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Database\Connection as BaseConnection;
use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class JsonConnection extends BaseConnection implements ConnectionInterface
{
    protected $config;

    /**
     * Create a new database connection instance.
     *
     * @param array $config
     *
     * @return void
     */
    public function __construct($config)
    {
        $this->config = $config;

        /*
         * We need to initialize a query grammar and the query post processors
         * which are both very important parts of the database abstractions
         * so we initialize these to their default values while starting.
         */
        $this->useDefaultQueryGrammar();

        $this->useDefaultPostProcessor();
    }

    public function table($table, $as = null)
    {
        throw new Exception('the method is not implemented.');
    }

    public function raw($value)
    {
        throw new Exception('the method is not implemented.');
    }

    public function selectOne($query, $bindings = [], $useReadPdo = true)
    {
        $records = $this->select($query, $bindings);

        return count($records) > 0 ? reset($records) : null;
    }

    public function insert($query, $bindings = [])
    {
        throw new Exception('the method is not implemented.');
    }

    public function update($query, $bindings = [])
    {
        throw new Exception('the method is not implemented.');
    }

    public function delete($query, $bindings = [])
    {
        throw new Exception('the method is not implemented.');
    }

    public function unprepared($query)
    {
        throw new Exception('the method is not implemented.');
    }

    public function transaction(Closure $callback, $attempts = 1)
    {
        throw new Exception('the method is not implemented.');
    }

    public function beginTransaction()
    {
        throw new Exception('the method is not implemented.');
    }

    public function commit()
    {
        throw new Exception('the method is not implemented.');
    }

    public function rollBack()
    {
        throw new Exception('the method is not implemented.');
    }

    public function transactionLevel()
    {
        throw new Exception('the method is not implemented.');
    }

    public function pretend(Closure $callback)
    {
        throw new Exception('the method is not implemented.');
    }

    public function getSchemaBuilder()
    {
        if (is_null($this->schemaGrammar)) {
            $this->useDefaultSchemaGrammar();
        }

        return new Builder($this);
    }

    protected function getDefaultPostProcessor()
    {
        return new QueryProcessorIfx();
    }

    protected function getDefaultQueryGrammar()
    {
        return $this->withTablePrefix(new QueryGrammarIfx());
    }

    protected function getDefaultSchemaGrammar()
    {
        return $this->withTablePrefix(new SchemaGrammarIfx());
    }

    public function prepareBindings(array $bindings)
    {
        $grammar = $this->getQueryGrammar();
        if ($this->isTransEncoding()) {
            $db_encoding = $this->getConfig('db_encoding');
            $client_encoding = $this->getConfig('client_encoding');
            foreach ($bindings as $key => &$value) {
                /*
                 * We need to transform all instances of DateTimeInterface into the actual
                 * date string. Each query grammar maintains its own date string format
                 * so we'll just ask the grammar for the format to get from the date.
                 */
                if ($value instanceof DateTimeInterface) {
                    $value = $value->format($grammar->getDateFormat());
                } elseif ($value === false) {
                    $value = 0;
                }
                if (is_string($value)) {
                    $value = $this->convertCharset($client_encoding, $db_encoding, $value);
                }
            }
        } else {
            foreach ($bindings as $key => &$value) {
                if ($value instanceof DateTimeInterface) {
                    $value = $value->format($grammar->getDateFormat());
                } elseif ($value === false) {
                    $value = 0;
                }
            }
        }

        return $bindings;
    }

    protected function isTransEncoding()
    {
        $db_encoding = $this->getConfig('db_encoding');
        $client_encoding = $this->getConfig('client_encoding');

        return $db_encoding && $client_encoding && ($db_encoding != $client_encoding);
    }

    protected function convertCharset($in_encoding, $out_encoding, $value)
    {
        //return iconv($in_encoding, "{$out_encoding}//IGNORE", trim($value));
        return $value;
    }

    public function select($query, $bindings = [], $useReadPdo = true)
    {
        if (config('app.debug')) {
            Log::debug('query: '.$query.' with '.implode(', ', $bindings));
        }

        $uri = Arr::get($this->config, 'uri');
        $source = Arr::get($this->config, 'source');
        $token = Arr::get($this->config, 'token');

        $client = new Client(['timeout' => Arr::get($this->config, 'timeout', 150), '
            connection_timeout'         => Arr::get($this->config, 'connection_timeout', 150)]);

        $substatments = explode('?', $query);

        $countBindings = count($bindings);

        if (count($substatments) != ($countBindings + 1)) {
            throw new Exception("the query {$query} not matches the count ({$countBindings}) of bingings.");
        }

        $sql = $substatments[0];
        if (count($substatments) > 1) {
            for ($i = 0; $i < $countBindings; $i++) {
                $bind = $bindings[$i];
                if ($bind === null) {
                    $sql .= 'null'.$substatments[$i + 1];
                } elseif (is_numeric($bind)) {
                    $sql .= "{$bind}".$substatments[$i + 1];
                } else {
                    $sql .= "'".str_replace("'", "''", $bind)."'".$substatments[$i + 1];
                }
            }
        }

        if (config('app.debug')) {
            Log::debug("the sql is ${sql}");
        }

        $response = $client->get($uri, [
            'header' => ['Content-Type'=> 'application/json; charset=utf-8'],
            'query'  => ['action' => 'queryList', 'source' => $source,
                '_token'          => $token, 'sql'=> $sql,
            ], ]);

        $json = $response->getBody()->getContents();

        if (config('app.debug')) {
            Log::debug("the response is {$json}");
        }

        if ($json) {
            $results = json_decode($json);
            if (is_array($results)) {
                $results = $this->parseResult($results);
            }

            return $results;
        }

        return [];
    }

    protected function parseResult($results)
    {
        if (!$results) {
            return $results;
        }
        foreach ($results as &$result) {
            if (!$result) {
                continue;
            }
            if (is_array($result)) {
                $result = $this->parseResult($result);
            } elseif (is_object($result)) {
                $result = $this->parseResult($result);
            } elseif (is_string($result)) {
                if (strpos($result, '%') !== false) {
                    $result = urldecode($result);
                }
            }
        }

        return $results;
    }

    public function statement($query, $bindings = [])
    {
        return $this->select($query, $bindings);
    }

    public function affectingStatement($query, $bindings = [])
    {
        throw new Exception('the method is not implemented.');
    }
}
