<?php

$host = "localhost";
$dbname = "sql_web";
$username = "root";
$password = "";


$conn = mysqli_connect($host, $username, $password, $dbname);


if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}


function query($sql) {
    global $conn;
    $result = mysqli_query($conn, $sql);
    if (!$result) {
        die("Query failed: " . mysqli_error($conn));
    }
    return $result;
}


function fetch_assoc($result) {
    return mysqli_fetch_assoc($result);
}


function fetch_all($result) {
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $rows[] = $row;
    }
    return $rows;
}


function affected_rows() {
    global $conn;
    return mysqli_affected_rows($conn);
}


function last_insert_id() {
    global $conn;
    return mysqli_insert_id($conn);
}


function escape_string($string) {
    global $conn;
    return mysqli_real_escape_string($conn, $string);
}

?>