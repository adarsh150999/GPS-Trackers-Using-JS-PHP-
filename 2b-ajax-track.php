<?php
// (A) INIT
require "2a-core.php";
switch ($_POST['req']) {
  // (B) INVALID REQUEST
  default:
    echo "Invalid request.";
    break;

  // (C) UPDATE RIDER LOCATION
  case "update":
    $pass = $_TRACK->update($_POST['rider_id'], $_POST['lng'], $_POST['lat']);
    echo json_encode([
      "status" => $pass ? 1 : 0,
      "message" => $pass ? "OK" : $_TRACK->error
    ]);
    break;

  // (D) GET RIDER LOCATION
  case "get":
    $location = $_TRACK->get($_POST['rider_id']);
    echo json_encode([
      "status" => is_array($location) ? 1 : 0,
      "message" => $location
    ]);
    break;

  // (E) GET ALL RIDER LOCATIONS
  case "getAll":
    $location = $_TRACK->getAll();
    echo json_encode([
      "status" => is_array($location) ? 1 : 0,
      "message" => $location
    ]);
    break;
}