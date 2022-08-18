<?php
    include_once('./sql.php');

    $sql = new sql();

    /***** test 1 *****/
    $field   = "*";
    $table   = "rpz";
    $where   = ["or" => ["price•1" => "100˚=˚or", 'uuid' => "Nick˚!="], "and" => ["uuid" => "Jay˚=˚and", "price•2" => "200˚!="], "time" => "100˚<"];
    $between = ["time" => "10˚20˚or•50˚60"];
    $groupBy = "time";
    $orderBy = "time";
    $limit   = "2";

    $sqlCommand = $sql::get_sql(fields:$field, tables:$table, where:$where, between:$between, groups:$groupBy, orders:$orderBy, limits:$limit).PHP_EOL;

    echo $sqlCommand;

    /***** test 2 *****/
    $field   = ["*", "time", "apple" => "apple.max(price)", "windows" => "windows.max(price)"];
    $table   = ["recursor" => "rpz"];
    $where   = ["or" => ["price•1" => "100˚=˚or", 'uuid' => "Nick˚!="], ["uuid" => "Jay˚=˚and", "price•2" => "200˚!="]];
    $between = ["time" => "10˚20˚or•50˚60"];
    $groupBy = ["time", "price"];
    $orderBy = ["desc" => ["time", "price"]];
    $limit   = [10 => 20];

    $sqlCommand = $sql::get_sql(fields:$field, tables:$table, where:$where, between:$between, groups:$groupBy, orders:$orderBy, limits:$limit).PHP_EOL;

    echo $sqlCommand;

    /***** test 3 *****/
    $field   = ["*", "time", "apple" => "apple.max(price)", "windows" => "windows.max(price)"];
    $table   = ["recursor" => "(SELECT * FROM `rpz` WHERE (`price` = 100 OR `uuid` != 'Nick') OR (`price` = 100 OR `uuid` != 'Nick' `uuid` = 'Jay' AND `price` != 200) AND `time` < 100 AND (`time` BETWEEN 10 AND 20) OR (`time` BETWEEN 50 AND 60) GROUP BY `time` ORDER BY `time`  LIMIT 0, 2)"];
    $where   = ["or" => ["price•1" => "100˚=˚or", 'uuid' => "Nick˚!="], ["uuid" => "Jay˚=˚and", "price•2" => "200˚!="]];
    $between = ["time" => "10˚20˚or•50˚60"];
    $groupBy = ["time", "price"];
    $orderBy = ["desc" => ["time", "price"]];
    $limit   = [10 => 20];

    $sqlCommand = $sql::get_sql(fields:$field, tables:$table, where:$where, between:$between, groups:$groupBy, orders:$orderBy, limits:$limit).PHP_EOL;

    echo $sqlCommand;

    /***** test 4 *****/
    $field   = "*";
    $table   = "rpz";
    $limit   = [10 => 20];
    $orderBy = [["asc" => "time", "desc" => "user"]];

    $sqlCommand = $sql::get_sql(fields:$field, tables:$table, orders:$orderBy, limits:$limit).PHP_EOL;

    echo $sqlCommand;

    /***** test 5 *****/
    $field   = "*";
    $table   = "rpz";
    $limit   = [10 => 20];
    $orderBy = ["desc" => "user"];

    $sqlCommand = $sql::get_sql(fields:$field, tables:$table, orders:$orderBy, limits:$limit).PHP_EOL;

    echo $sqlCommand;
?>
