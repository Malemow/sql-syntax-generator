<?php
    include_once('./GenerateSQL.php');

    /***** test 1 *****/

    $sql = GenerateSQL::get_sql(
        fields: '*',
        tables: 'test_1',
        wheres: [
            "name" => sprintf("%s˚=˚AND", "Nick"),
            "age" => sprintf("%d˚=˚", 5),
        ]
    );

    var_dump($sql);
