<?php

namespace Archytech\Laravel\Ifx\Query\Processors;

use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Processors\Processor as QueryProcessor;

class Processor extends QueryProcessor
{
    /**
     * Process the results of a column listing query.
     *
     * @param  array  $results
     * @return array
     */
    public function processColumnListing($results)
    {
        $mapping = function ($r) {
            $r = (object) $r;

            return $r->column_name;
        };

        return array_map($mapping, $results);
    }

    /**
     * @param Builder $query
     * @param string $sql
     * @param array $values
     * @param null $sequence
     * @return int
     */
    public function processInsertGetId(Builder $query, $sql, $values, $sequence = null)
    {
        return parent::processInsertGetId($query, $sql, $values, $sequence);
    }

    /**
     * @param Builder $query
     * @param array $results
     * @return array
     */
    public function processSelect(Builder $query, $results)
    {
        return $results;
    }

}
