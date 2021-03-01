# GPS-Trackers-Using-JS-PHP-
1. WISMO – Where is my order  Developing a system to store and track an order from start to end. Volume of order is expected to be 10 millions per day and each order may have 15 tracking events.

                                             CASE STUDY .
 
1. WISMO – Where is my order
Developing a system to store and track an order from start to end. Volume of order is expected to be 10 millions per day and each order may have 15 tracking events.

The Link to the source code is :

Here the Source code is Followed by the Explanation of steps, 

A basic GPS tracking system with PHP and Javascript requires the following components:
A database table to record the last known locations.
PHP library and endpoint to accept and manage the location updates.
A webpage or application to send the rider’s current GPS location to the server.
Lastly, an admin page to track all the riders.


STEP 1 :


Field
Description
rider_id
Primary key, the rider ID… Or the ID of whatever you want to track.
track_time
Time the rider last “checked in”.
track_lng
Longitude.
track_lat
Latitude.

And , that is all we need. Just a table to store the last-known locations of the riders.
THE CODE :

CREATE TABLE `gps_track` (
  `rider_id` int(11) NOT NULL,
  `track_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `track_lng` decimal(11,7) NOT NULL,
  `track_lat` decimal(11,7) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `gps_track`
  ADD PRIMARY KEY (`rider_id`),
  ADD KEY `track_time` (`track_time`);


STEP 2: PHP Core Library and End Points 

Next, we will establish another piece of the foundations – The endpoint of the GPS tracking system which will receive and serve the GPS coordinates.
This one looks intimidating at first, but keep calm and analyze slowly.
A & B – When the $_TRACK = new Track() object is created, the constructor will automatically connect to the database. The destructor closes the connection.
C – query() is a simple helper function that runs an SQL query.
D – update() records a rider’s GPS coordinates.
E – get() returns a rider’s last known coordinates.
F – getAll() returns all riders’ last known coordinates.
THE CODE :

<?php
class Track {
  // (A) CONSTRUCTOR - CONNECT TO DATABASE
  public $pdo = null;
  public $stmt = null;
  public $error = "";
  function __construct () {
    try {
      $this->pdo = new PDO(
        "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET, 
        DB_USER, DB_PASSWORD, [
          PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
          PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
      );
    } catch (Exception $ex) { exit($ex->getMessage()); }
  }

  // (B) DESTRUCTOR - CLOSE CONNECTION
  function __destruct () {
    if ($this->stmt !== null) { $this->stmt = null; }
    if ($this->pdo !== null) { $this->pdo = null; }
  }

  // (C) HELPER FUNCTION - EXECUTE SQL QUERY
  function query ($sql, $data=null) {
    try {
      $this->stmt = $this->pdo->prepare($sql);
      $this->stmt->execute($data);
      return true;
    } catch (Exception $ex) {
      $this->error = $ex->getMessage();
      return false;
    }
  }
  
  // (D) UPDATE RIDER COORDINATES
  function update ($id, $lng, $lat) {
    return $this->query(
      "REPLACE INTO `gps_track` (`rider_id`, `track_time`, `track_lng`, `track_lat`) VALUES (?, ?, ?, ?)",
      [$id, date("Y-m-d H:i:s"), $lng, $lat]
    );
  }

  // (E) GET RIDER COORDINATES
  function get ($id) {
    $this->query("SELECT * FROM `gps_track` WHERE `rider_id`=?", [$id]);
    return $this->stmt->fetch();
  }

  // (F) GET ALL THE RIDER LOCATIONS
  function getAll () {
    $this->query("SELECT * FROM `gps_track`");
    return $this->stmt->fetchAll();
  }
}

// (G) DATABASE SETTINGS - CHANGE THESE TO YOUR OWN
define('DB_HOST', 'localhost');
define('DB_NAME', 'test');
define('DB_CHARSET', 'utf8');
define('DB_USER', 'root');
define('DB_PASSWORD', '');

// (H) START!
$_TRACK = new Track();



2b. AJAX END POINT
 
The core library is not going to work itself, so here is the AJAX endpoint. How it works is very simple – Just pass in a $_POST['req'] to specify the request, followed by the required parameters.
Request
Description
update
Update the location of the given rider. Parameters:
rider_id
lng
lat
get
Get the last known location of a given rider. Parameters:
rider_id
getAll
Get all the last-known locations of the riders


THE CODE : 

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




Step 3 ; Tracker USing JS

Now that the server-side foundations ready, we have to build a simple client-side tracker that will update the server of the rider’s current locations.

<!DOCTYPE html>
<html>
  <head>
    <title>Geolocation Tracking Demo</title>
    <script>
    var track = {
      // (A) PROPERTIES & SETTINGS
      rider : 999, // Rider ID - Fixed to 999 for this demo.
      delay : 10000, // Delay between GPS update, in milliseconds.
      timer : null, // Interval timer.
      display : null, // HTML <p> element.

      // (B) INIT
      init : function () {
        track.display = document.getElementById("display");
        if (navigator.geolocation) {
          track.update();
          setInterval(track.update, track.delay);
        } else {
          track.display.innerHTML = "Geolocation is not supported!";
        }
      },

      // (C) UPDATE CURRENT LOCATION TO SERVER
      update : function () {
        navigator.geolocation.getCurrentPosition(function (pos) {
          // (C1) LOCATION DATA
          var data = new FormData();
          data.append('req', 'update');
          data.append('rider_id', track.rider);
          data.append('lat', pos.coords.latitude);
          data.append('lng', pos.coords.longitude);

          // (C2) AJAX
          var xhr = new XMLHttpRequest();
          xhr.open('POST', "2b-ajax-track.php");
          xhr.onload = function () {
            var res = JSON.parse(this.response);
            if (res.status==1) {
              track.display.innerHTML = Date.now() + " | Lat: " + pos.coords.latitude + " | Lng: " + pos.coords.longitude;
            } else {
              track.display.innerHTML = res.message;
            }
          };
          xhr.send(data);
        });
      }
    };
    window.addEventListener("DOMContentLoaded", track.init);
    </script>
  </head>
  <body>
    <p id="display"></p>
  </body>
</html>


Yep, this seems massive at first. But it is basically using navigator.geolocation.getCurrentPosition() to get the current GPS location, and uploading it to the server via AJAX. That is the gist of it, and of course, there is always the option to create an Android/iOS app to do this as well.

STEP 4 Dummy admin Panel 

<!DOCTYPE html>
<html>
  <head>
    <title>GPS Tracking Demo</title>
    <script>
    var track = {
      // (A) PROPERTIES
      map : null, // HTML map
      delay : 10000, // Delay between location refresh

      // (B) INIT
      init : function () {
        track.map = document.getElementById("map");
        track.show();
        setInterval(track.show, track.delay);
      },

      // (C) GET DATA FROM SERVER AND UPDATE MAP
      show : function () {
        // (C1) DATA
        var data = new FormData();
        data.append('req', 'getAll');

        // (C2) AJAX
        var xhr = new XMLHttpRequest();
        xhr.open('POST', "2b-ajax-track.php");
        xhr.onload = function () {
          track.map.innerHTML = "<div>LOADED "+Date.now()+"</div>";
          var res = JSON.parse(this.response);
          if (res.status==1) {
            for (let rider of res.message) {
              var dummy = document.createElement("div");
              dummy.innerHTML = "Rider ID " + rider.rider_id + " | Lng " + rider.track_lng + " | Lat " + rider.track_lat + " | Updated " + rider.track_time;
              track.map.appendChild(dummy);
            }
          } else { track.map.innerHTML = res.message; }
        };
        xhr.send(data);
      }
    };
    window.addEventListener("DOMContentLoaded", track.init);
    </script>
  </head>
  <body>
    <div id="map"></div>
  </body>
</html>


This is just a dummy admin page that shows all the locations of the riders. 









