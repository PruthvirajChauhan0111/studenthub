
<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function getDatabase(): mysqli
{
    $db = new mysqli(
        "localhost",
        "root",
        "",
        "studenthub"
    );

    $db->set_charset("utf8mb4");
    return $db;
}
