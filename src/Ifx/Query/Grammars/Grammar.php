<?php

namespace Archytech\Laravel\Ifx\Query\Grammars;

use Archytech\Laravel\Ifx\ReservedWords;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Query\Grammars\Grammar as QueryGrammar;
use Illuminate\Support\Str;

class Grammar extends QueryGrammar
{
    use ReservedWords;

    /**
     * The keyword identifier wrapper format.
     *
     * @var string
     */
    protected $wrapper = '%s';

    /**
     * @var string
     */
    protected $schema_prefix = '';

    /**
     * Compile the "limit" portions of the query.
     *
     * @param Builder $query
     * @param int     $limit
     *
     * @return string
     */
    protected function compileLimit(Builder $query, $limit)
    {
        return '';
    }

    /**
     * Compile the "offset" portions of the query.
     *
     * @param Builder $query
     * @param int     $offset
     *
     * @return string
     */
    protected function compileOffset(Builder $query, $offset)
    {
        return '';
    }

    /**
     * Compile the "select *" portion of the query.
     *
     * @param Builder $query
     * @param array   $columns
     *
     * @return string|void|null
     */
    protected function compileColumns(Builder $query, $columns)
    {
        /*
         * If the query is actually performing an aggregating select, we will let that
         * compiler handle the building of the select clauses, as it will need some
         * more syntax that is best handled by that function to keep things neat.
         */
        if (!is_null($query->aggregate)) {
            return;
        }

        $select = 'select';

        if ($query->offset > 0) {
            $select .= ' skip '.(int) $query->offset;
        }

        if ($query->limit > 0) {
            $select .= ' first '.(int) $query->limit;
        }

        $select .= $query->distinct ? ' distinct' : '';

        return $select.' '.$this->columnize($columns);
    }

    /**
     * Compile the lock into SQL.
     *
     * @param Builder     $query
     * @param bool|string $value
     *
     * @return bool|string
     */
    protected function compileLock(Builder $query, $value)
    {
        if (is_string($value)) {
            return $value;
        }

        return $value ? ' for update' : ' for read only';
    }

    /**
     * Wrapping SQL value.
     *
     * @param string $value
     *
     * @return mixed|string
     */
    protected function wrapValue($value)
    {
        if ($this->isReserved($value)) {
            return Str::upper(parent::wrapValue($value));
        }

        if ($value === '*') {
            return $value;
        }

        return str_replace('"', '', $value);
    }

    /**
     * Compile a select query into SQL.
     *
     * @param Builder $query
     *
     * @return string
     */
    public function compileSelect(Builder $query)
    {
        if (is_null($query->columns)) {
            $query->columns = ['*'];
        }

        $components = $this->compileComponents($query);
        if (key_exists('lock', $components)) {
            unset($components['orders']);
        }

        return trim($this->concatenate($components));
    }

    /**
     * Compile the "union" queries attached to the main query.
     *
     * @param Builder $query
     *
     * @return string
     */
    protected function compileUnions(Builder $query)
    {
        $sql = '';
        foreach ($query->unions as $union) {
            $sql .= $this->compileUnion($union);
        }

        if (isset($query->unionOrders)) {
            $sql .= ' '.$this->compileOrders($query, $query->unionOrders);
        }

        return ltrim($sql);
    }

    /**
     * Compile an exists statement into SQL.
     *
     * @param Builder $query
     *
     * @return string
     */
    public function compileExists(Builder $query)
    {
        $existsQuery = clone $query;
        $existsQuery->columns = [];
        $existsQuery->selectRaw('1 as "exists"')
                    ->whereRaw('rownum = 1');

        return $this->compileSelect($existsQuery);
    }

    /**
     * Compile an insert statement into SQL.
     *
     * @param Builder $query
     * @param array   $values
     *
     * @return string
     */
    public function compileInsert(Builder $query, array $values)
    {
        /*
         * Essentially we will force every insert to be treated as a batch insert which
         * simply makes creating the SQL easier for us since we can utilize the same
         * basic routine regardless of an amount of records given to us to insert.
         */
        $table = $this->wrapTable($query->from);

        if (!is_array(reset($values))) {
            $values = [$values];
        }

        $values = reset($values);
        $columns = $this->columnize(array_keys($values));

        /*
         * We need to build a list of parameter place-holders of values that are bound
         * to the query. Each insert should have the exact same amount of parameter
         * bindings so we will loop through the record and parameterize them all.
         */
        $parameters = [];
        $parameters[] = '('.$this->parameterize($values).')';
        $parameters = implode(', ', $parameters);

        return "insert into {$table} ({$columns}) values {$parameters}";
    }

    /**
     * Add whereBitand part to where clause.
     *
     * @param Builder $query
     * @param $where
     *
     * @return string
     */
    protected function whereBitand(Builder $query, $where)
    {
        $bitand = $where['not'] ? 'not bitand' : 'bitand';
        $values = $where['values'];

        return $bitand.'('.$this->wrap($where['column']).', '.$this->wrapValue($values[0]).' ) '.$where['operator'].' '.$this->wrapValue($values[1]);
    }

    /**
     * Return the schema prefix.
     *
     * @return string
     */
    public function getSchemaPrefix()
    {
        return !empty($this->schema_prefix) ? $this->wrapValue($this->schema_prefix).'.' : '';
    }

    /**
     * Set the schema prefix.
     *
     * @param string $prefix
     */
    public function setSchemaPrefix($prefix)
    {
        $this->schema_prefix = $prefix;
    }
}
