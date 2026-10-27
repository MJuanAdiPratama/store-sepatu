<?php
/**
 * Database Connection & Query Helpers
 * QxuanStore - Shoe E-Commerce
 */

$hostname = 'localhost';
$username = 'root';
$password = '';
$dbname   = 'db_bukawarung';

$conn = mysqli_connect($hostname, $username, $password, $dbname);

if (!$conn) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

mysqli_set_charset($conn, "utf8mb4");

/**
 * Execute a SELECT query with prepared statement
 * Returns mysqli_result or false
 */
function db_query($sql, $params = [], $types = '') {
    global $conn;
    if (empty($params)) {
        return mysqli_query($conn, $sql);
    }
    
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        die("Query Prepare Error: " . mysqli_error($conn) . " in SQL: " . $sql);
    }
    
    if (empty($types)) {
        $types = '';
        foreach ($params as $param) {
            if (is_int($param)) {
                $types .= 'i';
            } elseif (is_float($param)) {
                $types .= 'd';
            } else {
                $types .= 's';
            }
        }
    }
    
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    mysqli_stmt_close($stmt);
    return $result;
}

/**
 * Execute an INSERT, UPDATE, or DELETE query with prepared statement
 * Returns boolean
 */
function db_execute($sql, $params = [], $types = '') {
    global $conn;
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        return false;
    }
    
    if (!empty($params)) {
        if (empty($types)) {
            $types = '';
            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } else {
                    $types .= 's';
                }
            }
        }
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    
    $res = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $res;
}

/**
 * Fetch a single row as an object
 */
function db_fetch_one($sql, $params = [], $types = '') {
    $res = db_query($sql, $params, $types);
    if ($res && mysqli_num_rows($res) > 0) {
        return mysqli_fetch_object($res);
    }
    return null;
}

/**
 * Fetch all rows as an array of objects
 */
function db_fetch_all($sql, $params = [], $types = '') {
    $res = db_query($sql, $params, $types);
    $data = [];
    if ($res) {
        while ($row = mysqli_fetch_object($res)) {
            $data[] = $row;
        }
    }
    return $data;
}

/**
 * Get the last inserted ID
 */
function db_insert_id() {
    global $conn;
    return mysqli_insert_id($conn);
}
