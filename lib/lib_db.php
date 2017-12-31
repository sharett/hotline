<?php
/**
* @file
* Library of database and other common functions.
*
* Automatically included from config.php
*
*/

/**
* Connect to the database
*
* Establishes connection to the database server if not already connected.
*
*/

function db_databaseConnect()
{
    global $db;
    global $HOTLINE_DB_DATABASE, $HOTLINE_DB_USERNAME, $HOTLINE_DB_PASSWORD,
           $HOTLINE_DB_HOSTNAME;

    // are we already connected?
    if ($db) {
        return;
    }

    // mysqli
    $db = new mysqli(
        $HOTLINE_DB_HOSTNAME,
        $HOTLINE_DB_USERNAME,
                     $HOTLINE_DB_PASSWORD,
        $HOTLINE_DB_DATABASE
    );

    if ($db->connect_errno) {
        die($db->connect_error);
    }
}

/**
* Disconnect from the database
*
* Disconnects from the database if connected.
*
*/

function db_databaseDisconnect()
{
    global $db;

    if (!$db) {
        // not connected
        return;
    }

    // disconnect from the database
    $db->close();
}

/**
* Records an error
*
* Records an error in the error_log table.
*
* @param string $description
*   Error description.
* @param string $severity
*   One of 'notice', 'warning', 'error', or 'critical'.
*
* @return bool
*   True if recorded successfully
*/

function db_error($description, $severity = 'error')
{
    $admin_user = $_SERVER['PHP_AUTH_USER'] ? $_SERVER['PHP_AUTH_USER'] : $_SERVER['REMOTE_USER'];

    $sql = "INSERT INTO errors SET ".
        "severity='".trim(addslashes($severity))."',".
        "source='".addslashes($_SERVER['SCRIPT_NAME'])."',".
        "admin_user='".addslashes($admin_user)."',".
        "error_time=NOW(),".
        "description='".trim(addslashes($description))."'";
    return db_db_command($sql, $error);
}

/**
* Database command query
*
* Executes a SQL query
*
* @param string $sql
*   SQL query to execute.
* @param string &$error
*   Descriptive error if the function returns false.
*
* @return bool
*   True if the query was successful, otherwise false.
*/

function db_db_command($sql, &$error)
{
    global $db;

    if (!$res = $db->query($sql)) {
        $error = $db->error . " (SQL: {$sql})";
        return false;
    }

    return true;
}

/**
* Get the number of affected rows from the last query
*
* ...
*
* @return int
*   The number of affected rows.
*/

function db_db_affected_rows()
{
    global $db;
    return $db->affected_rows;
}

/**
* Database query
*
* Executes a SQL query and passes back the results.
*
* @param string $sql
*   SQL query to execute.
* @param array &$results
*   An array of associative arrays (field name => value).
* @param string &$error
*   Descriptive error if the function returns false.
*
* @return bool
*   True if the query was successful, otherwise false.
*/

function db_db_query($sql, &$results, &$error)
{
    global $db;

    if (!$res = $db->query($sql)) {
        $error = $db->error . " (SQL: {$sql})";
        return false;
    }

    $results = array();
    while ($row = $res->fetch_assoc()) {
        $results[] = $row;
    }

    return true;
}

/**
* Database query for one row
*
* Executes a SQL query and passes back the results.
*
* @param string $sql
*   SQL query to execute.
* @param array &$results
*   An associative array (field name => value).
* @param string &$error
*   Descriptive error if the function returns false.
*
* @return bool
*   True if the query was successful, otherwise false.
*/

function db_db_getrow($sql, &$results, &$error)
{
    global $db;

    if (!db_db_query($sql, $results, $error)) {
        return false;
    }

    $results = (isset($results[0]) ? $results[0] : null);
    return true;
}

/**
* Database query for one column
*
* Executes a SQL query and passes back the results.
*
* @param string $sql
*   SQL query to execute.
* @param array &$results
*   An array of results.
* @param string &$error
*   Descriptive error if the function returns false.
*
* @return bool
*   True if the query was successful, otherwise false.
*/

function db_db_getcol($sql, &$results, &$error)
{
    global $db;

    if (!$res = $db->query($sql)) {
        $error = $db->error . " (SQL: {$sql})";
        return false;
    }

    $results = array();
    while ($row = $res->fetch_row()) {
        $results[] = $row[0];
    }

    return true;
}

/**
* Database query for one value
*
* Executes a SQL query and passes back the results.
*
* @param string $sql
*   SQL query to execute.
* @param string &$results
*   The SQL result.
* @param string &$error
*   Descriptive error if the function returns false.
*
* @return bool
*   True if the query was successful, otherwise false.
*/

function db_db_getone($sql, &$results, &$error)
{
    global $db;

    if (!$res = $db->query($sql)) {
        $error = $db->error . " (SQL: {$sql})";
        return false;
    }

    $row = $res->fetch_row();
    $results = $row[0];

    return true;
}

/**
* Database query that returns an associative array
*
* Executes a SQL query and passes back the results.
*
* @param string $sql
*   SQL query to execute.
* @param array &$results
*   An associative array where the key is the first field and the
* 	value is the second.
* @param string &$error
*   Descriptive error if the function returns false.
*
* @return bool
*   True if the query was successful, otherwise false.
*/

function db_db_getassoc($sql, &$results, &$error)
{
    global $db;

    if (!$res = $db->query($sql)) {
        $error = $db->error . " (SQL: {$sql})";
        return false;
    }

    $results = $res->fetch_assoc();
    return true;
}

/**
* Prints an variable wrapped in <pre> tags.
*
* Uses the print_r function to display all the data in the variable.
*
* @param mixed $data
*   Variable to print.
*/

function db_print($data)
{
    echo "<pre>";
    print_r($data);
    echo "</pre>";
}
