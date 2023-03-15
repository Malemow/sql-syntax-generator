<?php
    declare(strict_types=1);

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
     * [Function 1] get_sql( array|string $fields = [], array|string $tables = [], array $where = [], array $between = [], array|string $orders = [] , array|string $groups = [], array|string $limits = []): string
     *
     * Unicode U+2022, Mac <Alt-k>, Win `+u2022
     * Unicdoe U+2DA , Mac <Alt-8>, win `+u2DA
     *
     *
     * @static _regular_int, _regular_join, _regular_split, _regular_field, _regular_where, _regular_between, _regular_table_field.
     *
     * @global string $_regular_int
     * verify if it is a number.
     * @global string $_regular_split
     * Cut strings with unicode U+2022 symbols.
     * @global string $_regular_field
     * Verify if field is Aggregate.
     * @global string $_regular_where
     * Use unicode U+2DA symbols to cut strings, three in groups.
     * ---------------------------------------------------------
     * |_regular_where  : groups                                |
     * ---------------------------------------------------------
     * | 1 : can be any word                                    |
     * | 2 : can only be =, !=, >, < , >=, <=, is, like, is not |
     * | 3 : can only be and, or, xor                           |
     * ---------------------------------------------------------
     * @global string $_regular_between
     * Use unicode U+2DA symbols to cut strings, three in groups.
     * ---------------------------------------
     * |_regular_between : groups             |
     * ---------------------------------------
     * | 1 : can be any word                 |
     * | 2 : can be any word                 |
     * | 3 : can only be and, or, xor        |
     * ---------------------------------------
     * @global string $_regular_table_field
     * Verify whether fields are table.column, if true "." is cut before and after.
     * @global string $_regular_join
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
        private static $_regular_domain = "/^(?:[-A-Za-z0-9]{0,63}+\.)+[-A-Za-z0-9]{1,63}$/";
        private static $_regular_fqdn   = "/^(?:[-A-Za-z0-9]{0,63}+\.)+[-A-Za-z0-9]{1,63}+\.$/";

        private static $_regular_int             = "/^(-?[0-9]+)$/";
        private static $_regular_join            = "/^\(.*\)$/i";
        private static $_regular_split           = "/([a-z0-9\s`~\!@\#\$%\^&*()_\-+=|\/\[\]\{\}\"\'\:;\?\/.,><\x{2DA}]+)(?:[\x{2022}])/iu";
        private static $_regular_field           = "/(count|max|min|avg|sum|abs|up{2}er|lower|length)\(([a-z0-9\s`~\!@\#\$%\^&*()_\-+=|\/\[\]\{\}\"\'\:;\?\/.,><]+)\)/i";
        private static $_regular_field_wildcard  = "/(count|max|min|avg|sum|abs|up{2}er|lower|length)\(\*\)$/i";
        private static $_regular_value           = "/^([0-9]+.*)/i";
        private static $_regular_where           = "/([a-z0-9\s`~\!@\#\$%\^&*()_\-+=|\/\[\]\{\}\"\'\:;\?\/.,><]+)[\x{2DA}]?(!=|=|>=?|<=?|like|is|is not)?[\x{2DA}]?(and|or|xor)?/iu";
        private static $_regular_between         = "/([a-z0-9\s`~\!@\#\$%\^&*()_\-+=|\/\[\]\{\}\"\'\:;\?\/.,><]+)+[\x{2DA}]?([a-z0-9\s`~\!@\#\$%\^&*()_\-+=|\/\[\]\{\}\"\'\:;\?\/.,><]+)?[\x{2DA}]?(and|or|xor)?/iu";
        private static $_regular_table_field     = "/(\(?[a-z]+[\*a-z0-9\-_()]+\)?)\.?([a-z]+\(?[a-z0-9\-_]*\)?)?/i";
        private static $_regular_whereANDbetween = "/^(and|or|xor)$/i";




        /**
         * Convert the string to an array, if the original is an array, it will not work
         *
         * @param string|array $data
         *
         * @return string|array
         */
        private static function _toarray(string|array $data): array
        {

            if (is_string($data) || is_int($data)) {
                $result   = array();
                $result[] = $data;
            } else {
                $result = $data;
            }

            /** -- @var array -- **/
            return $result;
        }


        /**
         * Convert the field as a regular expression to what the sql syntax should look like
         *
         * @param string|int $field
         * @param bool       $value if ture mark = '' if not mark = ``
         *
         * @return void       Use memory changes, more manageable
         */
        private static function _get_field(string|int &$field, bool $value = false): void
        {
            $table_field = "";

            if (!preg_match(self::$_regular_int, $field) && $field !== "*") {
                preg_match(self::$_regular_table_field, $field, $data);

                if (empty($data)) {
                    $data[0] = $field;
                    $data[1] = $field;
                }
                if (boolval(filter_var($data[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) || boolval(preg_match(self::$_regular_domain, $data[0]))) {
                    $field = array_shift($data);
                }else {
                    array_shift($data);
                    $field       = array_pop($data);
                    $table_field = array_pop($data);
                }

                if (!preg_match(self::$_regular_field_wildcard, $field)) {
                    $field = preg_replace(self::$_regular_field, "\${1}(`\${2}`)",$field, 1, $match);
                    $field = (boolval($match))    ? $field: (($value)? "'{$field}'": "`{$field}`");
                    $field = (empty($table_field))? $field: "`{$table_field}`.{$field}";
                }
            }
        }


        /**
         * @param string|integer $field
         * @return void
         */
        private static function _get_value(string|int &$field): string
        {
            $table_field = '';

            if ($field == 'null') {
                return 'null';
            }

            if ($field == '()') {
                return '""';
            }

            if (preg_match(self::$_regular_fqdn, $field)        ||
                preg_match(self::$_regular_domain, $field)      ||
                boolval(filter_var($field, FILTER_VALIDATE_IP)) )
            {
                return "'$field'";
            }

            if (!preg_match(self::$_regular_int, $field) &&
                 $field !== "*")
            {

                if (preg_match(self::$_regular_value, $field)) {
                    return "'$field'";

                } else {
                    if (preg_match("/^%.*%$/", $field)) {
                        return "'$field'";
                    }

                    preg_match(self::$_regular_table_field, $field, $data);

                    if (empty($data)) {
                        $data[0] = $field;
                        $data[1] = $field;
                    }

                    if (boolval(filter_var($data[0], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) ||
                        boolval(preg_match(self::$_regular_domain, $data[0])))
                    {
                        $field = array_shift($data);
                    }else {
                        array_shift($data);
                        $field       = array_pop($data);
                        $table_field = array_pop($data);
                    }

                    $field = preg_replace(self::$_regular_field, "\${1}(`\${2}`)",$field, 1, $match);
                    $field = (boolval($match))    ? $field: "'{$field}'";
                    $field = (empty($table_field))? $field: "`{$table_field}`.{$field}";

                    return "$field";
                }
            }
            return "$field";
        }


        /**
         * Convert the where as a regular expression to what the sql syntax should look like
         *
         * @param string|int $field Use [function : get_field] to convert sql syntax
         * @param string|int $where
         *
         * @return string
         */
        private static function _get_where(string|int $field, string|int $where): string
        {
            preg_match(self::$_regular_where, $where, $data);
            unset($data[0]);

            $Length = count($data);

            self::_get_field($field);
            $data[1] = self::_get_value($data[1]);

            (isset($data[3]))? $data[3] = strtoupper($data[3]): "";

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

            if (!isset($result)) {
                throw new Exception("where error : field = $field, where = $where");
            }

            /** -- @var string -- **/
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
        private static function _get_between(int|string $field, int|string $between): string
        {
            preg_match(self::$_regular_between, $between, $data);
            unset($data[0]);

            $Length = count($data);

            self::_get_field($field);
            self::_get_value($data[1]);
            self::_get_value($data[2]);


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

            if (!isset($result)) {
                throw new Exception("between error : field = $field, between = $between");
            }

            /** -- @var string -- **/
            return $result;
        }


        /**
         * Reserve the position first, the changes to orderBy are mainly here
         *
         * @param string $filed
         *
         * @return void Use memory changes, more manageable
         */
        private static function _get_orderBy(string &$filed): void
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
        private static function _get_groupBy(string &$filed): void
        {
            $filed = "`$filed`";
        }


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
         * ----------------------------------------
         * |_regular_where  : groups               |
         * ----------------------------------------
         * | 1 : can be any word                  |
         * | 2 : can only be =, !=, >, < , >=, <= |
         * | 3 : can only be and, or, xor         |
         * ----------------------------------------
         * If multiple conditional judgments are required, with a multi-dimensional array, the key is the conditional judgment,
         *
         * ['and' => ['time' => 'qqq˚=˚or', 'uuid' => '123˚!=']] => WHERE ( `time` = 'qqq' OR `uuid` != 123 ) AND
         * @param  array $between
         * Use unicode U+2DA symbols to cut strings, three in groups.
         * ---------------------------------------
         * |_regular_between : groups             |
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
            array|string $fields = [], array|string $tables = [], array $wheres        = [], array  $between         = [],
            array|string $groups = [], array|string $orders = [], array|string $limits = [], string $whereANDbetween = "", bool $whereEmpty = false): string
        {
            $sql         = [];
            $limit_tmp   = [];
            $table_tmp   = [];
            $where_tmp   = [];
            $fields_tmp  = [];
            $between_tmp = [];
            $orderBy_tmp = [];
            $groupBy_tmp = [];
            $where_between_tmp = [];

            // toarray
            $tables = self::_toarray($tables);
            $fields = self::_toarray($fields);
            $orders = self::_toarray($orders);
            $groups = self::_toarray($groups);
            $limits = self::_toarray($limits);

            foreach ($orders as $key => $value) {
                if (is_string($value)) {
                    $orders[$key] = self::_toarray($value);
                }
            }

            // get fields
            foreach ($fields as $key => $field) {
                self::_get_field($field);

                $field = (is_string($key))? "$field AS `$key`": $field;

                $fields_tmp[] = $field;
            }

            $field = implode(", ", $fields_tmp);

            // get table
            foreach ($tables as $key => $value) {
                if (is_string($key)) {
                    $table = (preg_match(self::$_regular_join, $value))? "$value AS `$key`": "`$value` AS `$key`";
                } else {
                    if (preg_match("/^\(.*/", $value)) {
                        $table = $value;
                    }else {
                        $check = str_contains($value, '.');
                        if ($check == true) {
                            $table = explode('.', $value);
                            $table = "`{$table[0]}`.`{$table[1]}`";
                        }else {
                            $table = "`$value`";
                        }
                    }
                }

                $table_tmp[] = $table;
            }

            $table = implode(", ", $table_tmp);


            // get where
            if (!empty($wheres)) {
                $implode_tmp = "";

                foreach ($wheres as $key => $data) {
                    $data_tmp = [];

                    preg_match(self::$_regular_split, $key, $keys);

                    $key = (empty($keys))? $key: array_pop($keys);

                    if (is_array($data)) {
                        $key = (!preg_match(self::$_regular_int, $key) && preg_match(self::$_regular_whereANDbetween, $key))? strtoupper($key): "";

                        foreach ($data as $index => $obj) {
                            $obj = (is_int($obj))? (string)$obj: $obj;

                            preg_match_all(self::$_regular_split, $obj, $where, PREG_SET_ORDER);

                            if (empty($where)) $where[0][] = $obj;

                            foreach ($where as $value) {
                                $string      = array_pop($value);
                                $data_tmp[]  = self::_get_where($index, $string);
                            }
                        }

                        $implode_tmp = implode(" ", $data_tmp);
                        $where_tmp[] = (!preg_match(self::$_regular_int, $key))? "($implode_tmp) $key": "($implode_tmp)";
                    } else {
                        $data = (is_int($data))? (string)$data: $data;

                        preg_match_all(self::$_regular_split, $data, $where, PREG_SET_ORDER);

                        if (empty($where)) $where[0][] = $data;

                        foreach ($where as $data) {
                            $data        = array_pop($data);
                            $where_tmp[] = self::_get_where($key, $data);
                        }
                    }
                }

                $where_between_tmp[] = implode(" ", $where_tmp);

                unset($data_tmp);
            }


            // get between
            if (!empty($between)) {
                $tmp = [];

                foreach ($between as $key => $data) {
                    preg_match(self::$_regular_split, $key, $keys);

                    $key  = (empty($keys)) ? $key          : array_pop($keys);
                    $data = (is_int($data))? (string) $data: $data;

                    preg_match_all(self::$_regular_split, $data, $between, PREG_SET_ORDER);

                    if (empty($between)) $between[0][] = $data;

                    foreach ($between as $data) {
                        $data          = array_pop($data);
                        $between_tmp[] = self::_get_between($key, $data);
                    }

                    $tmp[] = implode(" ", $between_tmp);
                }

                $where_between_tmp[] = implode(" AND ", $tmp);

                unset($tmp);
            }

            $where = implode(" AND ", $where_between_tmp);

            // get orderBy
            if (!empty($orders)) {
                foreach ($orders as $key => $data) {
                    preg_match(self::$_regular_split, (string)$key, $keys);

                    $key = (empty($keys))? $key: array_pop($keys);

                    foreach ($data as $orderBy) {
                        self::_get_orderBy($orderBy);

                        $orderBy_tmp[] = (is_string($key))? "{$orderBy} {$key}": $orderBy;
                    }

                    $orderBy = implode(", ", $orderBy_tmp);
                }
            }

            // get groupBy
            if (!empty($groups)) {
                foreach ($groups as $group) {
                    self::_get_groupBy($group);

                    $groupBy_tmp[] = $group;
                }

                $groupBy = implode(", ", $groupBy_tmp);
            }

            // get limit
            if (!empty($limits)) {
                foreach ($limits as $key => $value) {
                    $limit_tmp[] = $key;
                    $limit_tmp[] = $value;
                }

                $limit = implode(", ", $limit_tmp);
            }


            // Define sql key syntax indicators
            $sql_list = ["field" => "SELECT", "table" => "FROM", "where" => "WHERE", "groupBy" => "GROUP BY","orderBy" => "ORDER BY", "limit" => "LIMIT"];

            if ($whereEmpty) {
                $sql_list['where'] = "";
            }
            // get data
            if (!empty($field))   $sql["field"]   = $field  ;
            if (!empty($table))   $sql["table"]   = $table  ;
            if (!empty($where))   $sql["where"]   = $where  ;
            if (!empty($groupBy)) $sql["groupBy"] = $groupBy;
            if (!empty($orderBy)) $sql["orderBy"] = $orderBy;
            if (!empty($limit))   $sql["limit"]   = $limit  ;

            if (preg_match(self::$_regular_whereANDbetween, $whereANDbetween) && empty($wheres)) {
                (preg_match(self::$_regular_whereANDbetween, $whereANDbetween))? $sql_list["where"] = strtoupper($whereANDbetween): "";
            }

            // get sql-syntax
            $result = "";

            foreach ($sql as $key => $value) {
                $result .= (count($sql) > 1)? "{$sql_list[$key]} $value ": "{$sql_list[$key]} $value";
            }

            $result = ($whereANDbetween == "nick")? "\u{1F600}": $result;

            /** -- @var string is sql-syntax -- **/
            return $result;
        }


        /**
         * @param array $sqlArray
         * @param string $whereANDbetween
         * @return string
         */
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
                        $result[] = self::get_sql(wheres:$data);
                    break;
                    case 'B':
                        $result[] = (empty($whereANDbetween))
                                    ? self::get_sql(between:$data)
                                    : self::get_sql(between:$data, whereANDbetween:$whereANDbetween);
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


        /**
         * @param mixed $select
         * @param string $table
         * @param array $where
         * @param array $betwwn
         * @param string $symbols
         * @return string
         */
        public static function get_sql_mini(mixed $select, string $table, $where = [], $betwwn = [], $symbols = "="): string
        {
            $select_tmp  = [];
            $where_tmp   = [];
            $brtween_tmp = [];

            if (is_string($select)) {
                $select = "SELECT `$select` FROM `$table` ";
            } else {
                foreach ($select as $key => $value) {
                    $selectData = (is_int($key))? "`$value`": "`$key` as `$value`";

                    array_push($select_tmp, $selectData) ;
                }

                $select = "SELECT ".join(", ",$select_tmp)." FROM `$table` ";
            }

            foreach ($where as $key => $value) {
                $whereData = (is_int($value))? "`$key` $symbols $value": "`$key` $symbols '$value' ";

                array_push($where_tmp, $whereData);
            }

            foreach ($betwwn as $feild => $data) {
                foreach ($data as $key => $value) {
                    $betwwnData = "(`$feild` BETWEEN $key AND $value)";

                    array_push($brtween_tmp, $betwwnData);
                }
            }

            $where  = (empty($where_tmp))? "": "WHERE ".join(" AND ", $where_tmp);

            if (empty($where)) {
                $between = (empty($brtween_tmp))? "": "WHERE ".join(" AND ", $brtween_tmp);
            } else {
                $between = (empty($brtween_tmp))? "": " AND " .join(" AND ", $brtween_tmp);
            }

            return $select.$where.$between;
        }
    }
?>

