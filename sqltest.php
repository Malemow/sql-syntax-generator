<?php
    include_once('./GenerateSQL.php');

    $sql = new GenerateSQL();

    /***** test 1 *****/
    $field   = "qwdqwd.count(qwdqw)";
    $table   = ["GG" => "rpz", "GG•1" => "fasfasfs"];
    $where   = ['or' => ['ipv4' => "192.168.0.1"]];
    $between = ["time" => "10˚20˚or"];
    $groupBy = "time";
    $orderBy = "time";
    $limit   = "2";

    ($sql::get_sql(between:$between));
?>
