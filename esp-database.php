<!--
  Rui Santos
  Complete project details at https://RandomNerdTutorials.com/cloud-weather-station-esp32-esp8266/

  Permission is hereby granted, free of charge, to any person obtaining a copy
  of this software and associated documentation files.

  The above copyright notice and this permission notice shall be included in all
  copies or substantial portions of the Software.
-->
<?php
$servername = "localhost";

// REPLACE with your Database name
$dbname = "db_esp";
// REPLACE with Database user
$username = "root";
// REPLACE with Database user password
$password = "";

function insertReading($sensor, $location, $value1, $value2, $value3)
{
  global $servername, $username, $password, $dbname;

  // Create connection
  $conn = new mysqli($servername, $username, $password, $dbname);
  // Check connection
  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
  }

  $sql = "INSERT INTO SensorData (sensor, location, value1, value2, value3)
    VALUES ('" . $sensor . "', '" . $location . "', '" . $value1 . "', '" . $value2 . "', '" . $value3 . "')";

  if ($conn->query($sql) === TRUE) {
    return "New record created successfully";
  } else {
    return "Error: " . $sql . "<br>" . $conn->error;
  }
  $conn->close();
}

function getAllReadings($limit)
{
  global $servername, $username, $password, $dbname;

  // Create connection
  $conn = new mysqli($servername, $username, $password, $dbname);
  // Check connection
  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
  }

  $sql = "SELECT id, sensor, location, value1, value2, value3, reading_time FROM SensorData order by reading_time desc limit " . $limit;
  if ($result = $conn->query($sql)) {
    return $result;
  } else {
    return false;
  }
  $conn->close();
}
function getLastReadings()
{
  global $servername, $username, $password, $dbname;

  // Create connection
  $conn = new mysqli($servername, $username, $password, $dbname);
  // Check connection
  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
  }

  $sql = "SELECT id, sensor, location, value1, value2, value3, reading_time FROM SensorData order by reading_time desc limit 1";
  if ($result = $conn->query($sql)) {
    return $result->fetch_assoc();
  } else {
    return false;
  }
  $conn->close();
}

function minReading($limit, $value)
{
  global $servername, $username, $password, $dbname;

  // Create connection
  $conn = new mysqli($servername, $username, $password, $dbname);
  // Check connection
  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
  }

  $sql = "SELECT MIN(" . $value . ") AS min_amount FROM (SELECT " . $value . " FROM SensorData order by reading_time desc limit " . $limit . ") AS min";
  if ($result = $conn->query($sql)) {
    return $result->fetch_assoc();
  } else {
    return false;
  }
  $conn->close();
}

function maxReading($limit, $value)
{
  global $servername, $username, $password, $dbname;

  // Create connection
  $conn = new mysqli($servername, $username, $password, $dbname);
  // Check connection
  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
  }

  $sql = "SELECT MAX(" . $value . ") AS max_amount FROM (SELECT " . $value . " FROM SensorData order by reading_time desc limit " . $limit . ") AS max";
  if ($result = $conn->query($sql)) {
    return $result->fetch_assoc();
  } else {
    return false;
  }
  $conn->close();
}

function avgReading($limit, $value)
{
  global $servername, $username, $password, $dbname;

  // Create connection
  $conn = new mysqli($servername, $username, $password, $dbname);
  // Check connection
  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
  }

  $sql = "SELECT AVG(" . $value . ") AS avg_amount FROM (SELECT " . $value . " FROM SensorData order by reading_time desc limit " . $limit . ") AS avg";
  if ($result = $conn->query($sql)) {
    return $result->fetch_assoc();
  } else {
    return false;
  }
  $conn->close();
}
function filterReadingsByTimestamp($start_time, $end_time, $limit = 50)
{
  global $servername, $username, $password, $dbname;

  // Create connection
  $conn = new mysqli($servername, $username, $password, $dbname);
  // Check connection
  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
  }

  // Convert the input times to local time format
  $start_time = date('Y-m-d H:i:s', strtotime($start_time));
  $end_time = date('Y-m-d H:i:s', strtotime($end_time));

  $sql = "SELECT id, sensor, location, value1, value2, value3, reading_time 
          FROM SensorData 
          WHERE reading_time BETWEEN '$start_time' AND '$end_time' 
          ORDER BY reading_time DESC 
          LIMIT $limit";

  if ($result = $conn->query($sql)) {
    return $result;
  } else {
    return false;
  }
  $conn->close();
}
?>