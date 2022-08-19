<?php
    include_once('./GenerateSQL.php');

    $sql = new GenerateSQL();

    /***** test 1 *****/
    $field   = ['uuid' => 'uuid_j', 'dtime', 'status', 'method', 'feature'];
    $table   = ["GG" => "rpz", "GG•1" => "fasfasfs"];
    $where   = ['status' => "0"];
    $between = ["time" => "10˚20˚or"];
    $groupBy = "time";
    $orderBy = "time";
    $limit   = "2";

    echo $sql::get_sql(where:$where);
?>