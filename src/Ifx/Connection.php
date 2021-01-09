<?php

namespace Archytech\Laravel\Ifx;

use Archytech\Laravel\Ifx\Query\Grammars\Grammar as QueryGrammar;
use Archytech\Laravel\Ifx\Query\Processors\Processor as QueryProcessor;
use Archytech\Laravel\Ifx\Schema\Grammars\Grammar as SchemaGrammar;
use Archytech\Laravel\Ifx\Schema\Builder as SchemaBuilder;
use DateTimeInterface;
use InvalidArgumentException;
use Illuminate\Database\Connection as BaseConnection;
use Illuminate\Database\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Database\Schema\Builder;
use Illuminate\Database\Eloquent\Model;

class Connection extends BaseConnection
{
    /**
     * Get a schema builder instance for the connection.
     *
     * @return Builder
     */
    public function getSchemaBuilder()
    {
        if (is_null($this->schemaGrammar)) {
            $this->useDefaultSchemaGrammar();
        }
        return new SchemaBuilder($this);
    }

    /**
     * Get the default post processor instance.
     *
     * @return Processor
     */
    protected function getDefaultPostProcessor()
    {
        return new QueryProcessor();
    }

    /**
     * Prepare the query bindings for execution.
     *
     * @param array $bindings
     * @return array
     */
    public function prepareBindings(array $bindings)
    {
        $grammar = $this->getQueryGrammar();
        if ($this->isTransEncoding()) {
            $db_encoding = $this->getConfig('db_encoding');
            $client_encoding = $this->getConfig('client_encoding');
            foreach ($bindings as $key => &$value) {
                // We need to transform all instances of DateTimeInterface into the actual
                // date string. Each query grammar maintains its own date string format
                // so we'll just ask the grammar for the format to get from the date.
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

    /**
     * Check encoding option string from a configuration.
     *
     * @return bool
     */
    protected function isTransEncoding()
    {
        $db_encoding = $this->getConfig('db_encoding');
        $client_encoding = $this->getConfig('client_encoding');
        return ($db_encoding && $client_encoding && ($db_encoding != $client_encoding));
    }

    /**
     * Convert charset value with encoding
     *
     * @param $in_encoding
     * @param $out_encoding
     * @param $value
     * @return bool|false|string
     */
    protected function convertCharset($in_encoding, $out_encoding, $value)
    {
        return iconv($in_encoding, "{$out_encoding}//IGNORE", trim($value));
    }

    /**
     * Run a select statement and return a single result.
     *
     * @param string $query
     * @param array $bindings
     * @param bool $useReadPdo
     * @return array|bool|false|string
     */
    public function select($query, $bindings = [], $useReadPdo = true)
    {
        $results = parent::select($query, $bindings, $useReadPdo);
        if ($this->isTransEncoding()) {
            if ($results) {
                $db_encoding = $this->getConfig('db_encoding');
                $client_encoding = $this->getConfig('client_encoding');
                if (is_array($results) || is_object($results)) {
                    foreach ($results as &$result) {
                        if (is_subclass_of($result, Model::class)) {
                            $attributes = $result->getAttributes();
                            foreach ($attributes as $key => $value) {
                                if (is_string($value)) {
                                    $value = $this->convertCharset($db_encoding, $client_encoding, $value);
                                    $result->$key = $value;
                                    $result->syncOriginalAttribute($key);
                                }
                            }
                        } else if (is_array($result) || is_object($result)) {
                            foreach ($result as $key => &$value) {
                                if (is_string($value)) {
                                    $value = $this->convertCharset($db_encoding, $client_encoding, $value);
                                }
                            }
                        } else if (is_string($result)) {
                            $result = $this->convertCharset($db_encoding, $client_encoding, $result);
                        }
                    }
                } else if (is_string($results)) {
                    $results = $this->convertCharset($db_encoding, $client_encoding, $results);
                }
            }
        }
        return $results;
    }

    /**
     * Get the default query grammar instance.
     *
     * @return Grammar
     */
    protected function getDefaultQueryGrammar()
    {
        return $this->withTablePrefix(new QueryGrammar);
    }

    /**
     * Get the default schema grammar instance.
     *
     * @return Grammar
     */
    protected function getDefaultSchemaGrammar()
    {
        return $this->withTablePrefix(new SchemaGrammar);
    }

    /**
     * Execute an SQL statement and return the boolean result.
     *
     * @param string $query
     * @param array $bindings
     * @return bool|mixed
     */
    public function statement($query, $bindings = [])
    {
        return $this->run($query, $bindings, function ($query, $bindings) {
            if ($this->pretending()) {
                return true;
            }

            $count = substr_count($query, '?');
            if ($count == count($bindings)) {
                $bindings = $this->prepareBindings($bindings);
                return $this->getPdo()->prepare($query)->execute($bindings);
            }

            if (count($bindings) % $count > 0)
                throw new InvalidArgumentException('the driver can not support multi-insert.');

            $mutiBindings = array_chunk($bindings, $count);
            $this->beginTransaction();

            try {
                $pdo = $this->getPdo();
                $stmt = $pdo->prepare($query);

                foreach ($mutiBindings as $mutiBinding) {
                    $mutiBinding = $this->prepareBindings($mutiBinding);
                    $stmt->execute($mutiBinding);
                }
            } catch (\Exception $e) {
                $this->rollBack();
                return false;
            } catch (\Throwable $e) {
                $this->rollBack();
                return false;
            }

            $this->commit();

            return true;
        });
    }

    /**
     * Run an SQL statement and get the number of rows affected.
     *
     * @param string $query
     * @param array $bindings
     * @return int
     */
    public function affectingStatement($query, $bindings = [])
    {
        return $this->affectingStatement($query, $bindings);
    }
}
