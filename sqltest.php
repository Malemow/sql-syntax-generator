<?php
    include_once('./GenerateSQL.php');

    $sql_1 = GenerateSQL::get_sql(
        fields: '*',
        tables: 'test_1',
        wheres: [
            "AND" => [
                "status" => sprintf("%d˚=˚AND", 1),
                "address" => sprintf("%s˚like˚", "台北市%")
            ],
            "name" => sprintf("%s˚=", "Nick"),
        ],
        between: [
            "age" => sprintf("%d˚%d˚", 12, 18)
        ],
        groups: ["name", "age"],
        orders: [
            "desc" => ["address", "age"],
            "asc" => ["bbb"]
        ],
        limits: [10 =>  20]
    );

    $test_tables = GenerateSQL::get_sql(
        fields: '*',
        tables: ['test1', sprintf("(%s)", $sql_1)]
    );

    var_dump($test_tables);

