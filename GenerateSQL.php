<?php

    declare(strict_types=1);

    use Exception;

    /**
     * start page of sql-syntax-generator
     *
     * PHP version 7.4
     *
     * @category  PHP
     * @package   sql-syntax-generator
     * @author    Nick.Lin <nick.8716280@gmail.com>
     * @license   https://github.com/Malemow/sql-syntax-generator.git
     * @copyright 2022 sql-syntax-generator
     * @version   1.0
     */

    /**
     * Complete the patchwork sql syntax.
     *
     * [Function 1] get_sql( array|string $fields = [], array|string $tables = [], array $where = [], array $between = [], array|string $orders = [] , array|string $groups = [], array|string $limits = []): string.
     *
     * Unicode U+2022, Mac <Alt-k>, Win `+u2022
     * Unicdoe U+2DA , Mac <Alt-8>, win `+u2DA
     *
     *
     * @static regular_int, regular_join, regular_split, regular_field, regular_where, regular_between, regular_table_field.
     *
     * @global string $regular_int
     * verify if it is a number.
     * @global string $regular_split
     * Cut strings with unicode U+2022 symbols.
     * @global string $regular_field
     * Verify if field is Aggregate.
     * @global string $regular_where
     * Use unicode U+2DA symbols to cut strings, three in groups.
     * ----------------------------------------
     * |regular_where  : groups               |
     * ----------------------------------------
     * | 1 : can be any word                  |
     * | 2 : can only be =, !=, >, < , >=, <= |
     * | 3 : can only be and, or, xor         |
     * ----------------------------------------
     * @global string $regular_between
     * Use unicode U+2DA symbols to cut strings, three in groups.
     * ---------------------------------------
     * |regular_between : groups             |
     * ---------------------------------------
     * | 1 : can be any word                 |
     * | 2 : can be any word                 |
     * | 3 : can only be and, or, xor        |
     * ---------------------------------------
     * @global string $regular_table_field
     * Verify whether fields are table.column, if true "." is cut before and after.
     * @global string $regular_join
     * Verify that the table field is in join format. Use ^(.*)$ to verify
     *----------------------------------------------
     * @category  PHP
     * @author    Nick.Lin <nick.8716280@gmail.com>
     * @copyright 2022 sql
     * @example   location ./sqltest.php
     * @version   1.0
     */

    class GenerateSQL
    {
        private static $regular_int             = "/^(-?[0-9]+)$/";
        private static $regular_join            = "/^\(.*\)$/i";
        private static $regular_split           = "/([.*]+)[\x{2022}]?/iu";
        private static $regular_field           = "/(count|max|min|avg|sum|abs|up{2}er|lower|length)\(([a-z]+[\w()-:@&%#~`!*_=+\\'\"]*\/)\)+/i";
        private static $regular_where           = "/([\w()-:@&%#~`!*_=+\\'\"\/]+)[\x{2DA}]?(!=|==?|>=?|<=?)?[\x{2DA}]?(and|or|xor)?/iu";
        private static $regular_between         = "/([\w()-:@&%#~`!*_=+\\'\"\/]+)[\x{2DA}]?([\w()-:@&%#~`!*_=+\\'\"\/]+)?[\x{2DA}]?(and|or|xor)?/iu";
        private static $regular_table_field     = "/([a-z]+[\w()-_]*)(?:\.)?([a-z()-_]+[()\w-_]*)?/i";
        private static $regular_whereANDbetween = "/^(and|or|xor)$/i";


        /**
         * Get a set of sql syntax strings
         *
         * @param  array|string $fields
         * "value" is column "key" is alias, "key" can be without, "value" can be table.column, column can be with Aggregate.
         * if there is only one piece of data, you can directly write a string in
         * @param  array|string $tables
         * "value" is the tableName (If you need From (SELECT.....) as (alias), Please add () to the value yourself before and after).
         * if required aliases are defined in "key", if there is only one piece of data, you can directly write a string in
         * @param  array $where
         * Use unicode U+2DA symbols to cut strings, three in groups.
         * ---------------------------------------
         * |regular_where  : groups              |
         * ---------------------------------------
         * | 1 : can be any word                 |
         * | 2 :can only be =, !=, >, < , >=, <= |
         * | 3 : can only be and, or, xor        |
         * ---------------------------------------
         * If multiple conditional judgments are required, with a multi-dimensional array, the key is the conditional judgment,
         *
         * ['and' => ['time' => '10˚20˚or•50˚60']] => WHERE ( `time` = 'qqq' OR `uuid` != 123 ) AND
         * @param  array $between
         * Use unicode U+2DA symbols to cut strings, three in groups.
         * ---------------------------------------
         * |regular_between : groups             |
         * ---------------------------------------
         * | 1 : can be any word                 |
         * | 2 : can be any word                 |
         * | 3 : can only be and, or, xor        |
         * ---------------------------------------
         * @param  array|string $groups
         * "value" is field data.
         * if there is only one piece of data, you can directly write a string in.
         * @param  array|string $orders
         * "key" can be filled in ASC, DESC will be brought to the end of ORDER BY, "value" is field data.
         * if there is only one piece of data, you can directly write a string in.
         * @param  array|string $limits
         * If the number is recent, it will be written as limit 0, number by default.
         * If you need "limit 10, 20" , please write [10 => 20].
         *
         * @return string It will only return if there is a need brought in, if it is not brought in, it will return "".
         */
        public static function get_sql(
            array|string $fields = [], array|string $tables = [], array $where         = [], array   $between         = [],
            array|string $groups = [], array|string $orders = [], array|string $limits = [], string  $whereANDbetween = ""): string
        {
            $sql               = [];
            $limit_tmp         = [];
            $table_tmp         = [];
            $where_tmp         = [];
            $fields_tmp        = [];
            $between_tmp       = [];
            $orderBy_tmp       = [];
            $groupBy_tmp       = [];
            $where_between_tmp = [];


            //toarray
            $tables  = self::toarray($tables);
            $fields  = self::toarray($fields);
            $orders  = self::toarray($orders);
            $groups  = self::toarray($groups);
            $limits  = self::toarray($limits);

            foreach($orders as $key => $value)
            {
                if(is_string($value))
                {
                    $orders[$key] = self::toarray($value);
                }
            }


            // get table
            foreach ($tables as $key => $value) {
                if(is_string($key)){
                    $table =(!preg_match(self::$regular_join, $value))? "`$value` AS `$key`": "$value AS `$key`";
                }else {
                    $table = (!preg_match(self::$regular_join, $value))? "`$value`": "$value";
                }
                $table_tmp[] = $table;
            }
            $table = implode(", ", $table_tmp);


            // get fields
            foreach ($fields as $key => $field) {
                self::get_field($field);
                $field = (is_string($key))? "$field AS `$key`": $field;

                $fields_tmp[] = $field;
            }
            $field = implode(", ", $fields_tmp);

            //get where
            if(!empty($where)){
                $data_tmp         = [];
                $implode_tmp = "";
                foreach ($where as $key => $data) {
                    preg_match(self::$regular_split, $key, $keys);
                    $key = array_pop($keys);

                    if (is_array($data)) {
                        $key = (!preg_match(self::$regular_int, $key))? strtoupper($key): "";
                        foreach ($data as $index => $obj) {
                            preg_match_all(self::$regular_split, $obj, $where, PREG_SET_ORDER);
                            foreach ($where as $value) {
                                $string = array_pop($value);
                                $data_tmp[]  = self::get_where($index, $string);
                            }
                        }
                        $implode_tmp = implode(" ", $data_tmp);
                        $where_tmp[] =(!preg_match(self::$regular_int, $key))? "($implode_tmp) $key": "($implode_tmp)";
                    }else {
                        preg_match_all(self::$regular_split, $data, $where, PREG_SET_ORDER);
                        foreach ($where as $data) {
                            $data = array_pop($data);
                            $where_tmp[] = self::get_where($key, $data);
                        }
                    }
                }
                $where_between_tmp[] = implode(" ", $where_tmp);
                unset($data_tmp);
            }


            // get between
            if(!empty($between)){
                $tmp = [];
                foreach ($between as $key => $data) {
                    preg_match(self::$regular_split, $key, $keys);
                    $key = array_pop($keys);

                    preg_match_all(self::$regular_split, $data, $between, PREG_SET_ORDER);
                    foreach ($between as $data) {
                        $data = array_pop($data);
                        $between_tmp[] = self::get_between($key, $data);
                    }
                    $tmp[] = implode(" ", $between_tmp);
                }
                $where_between_tmp[] = implode(" AND ", $tmp);
                unset($tmp);
            }

            $where = implode(" AND ", $where_between_tmp);

            //get orderBy
            if(!empty($orders)){
                foreach ($orders as $key => $data) {
                    preg_match(self::$regular_split, $key, $keys);
                    $key = array_pop($keys);
                    foreach ($data as $index => $orderBy) {
                        preg_match(self::$regular_split, $index, $indexs);
                        $index = array_pop($indexs);

                        self::get_orderBy($orderBy);
                        $orderBy_tmp[] =(!preg_match(self::$regular_int, $index))? "$orderBy $index": $orderBy;
                    }
                    $orderBy = implode(", ", $orderBy_tmp);
                    $orderBy = (is_string($key))? "{$orderBy} {$key}": $orderBy;
                }
                unset($tmp);
            }

            //get groupBy
            if(!empty($groups)){
                foreach ($groups as $group) {
                    self::get_groupBy($group);
                    $groupBy_tmp[] = $group;
                }
                $groupBy = implode(", ", $groupBy_tmp);
            }

            //get limit
            if(!empty($limits)){
                foreach ($limits as $key => $value) {
                    $limit_tmp[] = $key;
                    $limit_tmp[] = $value;
                }
                $limit = implode(", ", $limit_tmp);
            }




            // Define sql key syntax indicators
            $sql_list = ["field" => "SELECT", "table" => "FROM", "where" => "WHERE", "groupBy" => "GROUP BY","orderBy" => "ORDER BY", "limit" => "LIMIT"];

            // get data
            (!empty($field))  ? $sql["field"]   = $field  : "";
            (!empty($table))  ? $sql["table"]   = $table  : "";
            (!empty($where))  ? $sql["where"]   = $where  : "";
            (!empty($groupBy))? $sql["groupBy"] = $groupBy: "";
            (!empty($orderBy))? $sql["orderBy"] = $orderBy: "";
            (!empty($limit))  ? $sql["limit"]   = $limit  : "";

            (preg_match(self::$regular_whereANDbetween, $whereANDbetween))? $sql_list["where"] = strtoupper($whereANDbetween): "";

            $result = "";
            // get sql-syntax
            foreach ($sql as $key => $value) {
                $result .=(count($sql) > 1)? "{$sql_list[$key]} $value ": "{$sql_list[$key]} $value";
            }

            $result = ($whereANDbetween == "nick")? json_decode('"\ud83d\ude00"'): $result;

            /**
             * is sql-syntax
             *
             * @var string
             */
            return $result;
        }


        /**
         * Convert the string to an array, if the original is an array, it will not work
         *
         * @param string|array $data
         *
         * @return string|array
         */
        private static function toarray(string|array $data): array
        {

            if(is_string($data) || is_int($data)){
                $result   = array();
                $result[] = $data;
            }else {
                $result = $data;
            }

            /**
             * @var array
             */
            return $result;
        }


        /**
         * Convert the field as a regular expression to what the sql syntax should look like
         *
         * @param string|int $field
         * @param bool       $mark if ture mark = '' if not mark = ``
         *
         * @return void       Use memory changes, more manageable
         */
        private static function get_field(string|int &$field, bool $mark = false): void
        {
            if(!preg_match(self::$regular_int, $field) && $field !== "*"){
                preg_match(self::$regular_table_field, $field, $data);

                $table_field = (count($data) == 3)? $data['1']: "";
                $field = (count($data) == 2)? $data[1]: $data[2];
                $field = preg_replace(self::$regular_field, "\${1}(`\${2}`)",$field, 1, $match);
                $field = (boolval($match))? $field: (($mark)? "'{$field}'": "`{$field}`");
                $field = (empty($table_field))? $field: "`{$table_field}`.{$field}";
            }
        }


        /**
         * Convert the where as a regular expression to what the sql syntax should look like
         *
         * @param string|int $field Use [function : get_field] to convert sql syntax
         * @param string|int $where
         *
         * @return string
         */
        private static function get_where(string|int $field, string|int $where): string
        {
            preg_match(self::$regular_where, $where, $data);
            unset($data[0]);

            $Length = count($data);

            self::get_field($field);
            self::get_field($data[1], true);

            (isset($data[3]))? $data[3] = strtoupper($data[3]):"";

            switch ($Length) {
                case 1:
                    $result = "$field = {$data[1]}";
                break;
                case 2:
                    $result = "$field {$data[2]} {$data[1]}";
                break;
                case 3:
                    $result = "$field {$data[2]} {$data[1]} {$data[3]}";
                break;
                default:
                break;
            }

            if(!isset($result)){
                throw new Exception("where error : field = $field, where = $where");
            }

            /**
             * @var string
             */
            return $result;
        }


        /**
         * Convert the between as a regular expression to what the sql syntax should look like
         *
         * @param int|string $field Use [function : get_field] to convert sql syntax
         * @param int|string $between
         *
         * @return string
         */
        private static function get_between(int|string $field, int|string $between): string
        {
            preg_match(self::$regular_between, $between, $data);
            unset($data[0]);

            $Length = count($data);

            self::get_field($field);
            self::get_field($data[1], true);
            self::get_field($data[2], true);

            (isset($data[3]))? $data[3] = strtoupper($data[3]):"";
            switch ($Length) {
                case 2:
                    $result = "($field BETWEEN {$data[1]} AND {$data[2]})";
                break;
                case 3:
                    $result = "($field BETWEEN {$data[1]} AND {$data[2]}) {$data[3]}";
                break;
                default:
                break;
            }

            if(!isset($result)){
                throw new Exception("between error : field = $field, between = $between");
            }

            /**
             * @var string
             */
            return $result;
        }


        /**
         * Reserve the position first, the changes to orderBy are mainly here
         *
         * @param string $filed
         *
         * @return void Use memory changes, more manageable
         */
        private static function get_orderBy(string &$filed): void
        {
            $filed = "`$filed`";
        }


        /**
         * Reserve the position first, the changes to groupBy are mainly here
         *
         * @param string $filed
         *
         * @return void Use memory changes, more manageable
         */
        private static function get_groupBy(&$filed): void
        {
            $filed =  "`$filed`";
        }


        public static function get_sql_all(array $sqlArray, string $whereANDbetween = ""): string
        {
            $tmp    = [];
            $result = [];
            foreach ($sqlArray as $key => $data) {
                $tmp[$key] = $data;
            }

            foreach ($tmp as $key => $data) {
                $key = strtoupper($key);
                $key = substr($key, 0, 1);
                switch ($key) {
                    case 'F':
                        $result[] = self::get_sql(fields:$data);
                    break;
                    case 'T':
                        $result[] = self::get_sql(tables:$data);
                    break;
                    case 'W':
                        $result[] = self::get_sql(where:$data);
                    break;
                    case 'B':
                        $result[] = (empty($whereANDbetween))? self::get_sql(between:$data):
                                                               self::get_sql(between:$data, whereANDbetween:$whereANDbetween);
                    break;
                    case 'O':
                        $result[] = self::get_sql(orders:$data);
                    break;
                    case 'G':
                        $result[] = self::get_sql(groups:$data);
                    break;
                    case 'L':
                        $result[] = self::get_sql(limits:$data);
                    break;
                    default:
                    break;
                }
            }
            return implode(" ", $result);
        }
    }
?>
