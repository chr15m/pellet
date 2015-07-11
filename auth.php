<?php
// this API only responds with plaintext
header("Content-type: text/plain");

// allow cross-domain requests to this API by default
cors();

// location of the authfile
$authfilename = "users.txt";

// Authenticates users against a text file of username/password pairs.
$authfile = file_get_contents($authfilename);

// if the file does not exist yet
if ($authfile == false) {
  ensure_authfile();
// the authfile exists
} else {
  // set up the session
  session_start();
  // regenerate the session key every so often
  session_refresh();
  // is the user logged in already?
  if(!isset($_SESSION['user'])) {
    $authtable = parse_authfile($authfile);
    check_credentials($authtable);
  } else {
    // user requested logout
    if (isset($_REQUEST["logout"])) {
      session_destroy();
      die(json_encode("AUTH_LOGGED_OUT"));
    }
  }
}

// refresh the session id every few requests to prevent session hijacking
function session_refresh() {
  if (++$_SESSION['session-regeneration-counter'] >= 16) {
    $_SESSION['session-regeneration-counter'] = 0;
    session_regenerate_id(true);
  }
}

// hash a password in the htpasswd style
function htpass($pw) {
  return crypt($pw, base64_encode($pw));
}

// parse the auth file into a hash of username -> password key-vals
function parse_authfile($authfile) {
  // parse the authfile
  $authtable = Array();
  $authlines = explode("\n", $authfile);
  foreach ($authlines as $line) {
    // remove spaces
    $line = preg_replace('/\s+/', '', $line);
    if ($line) {
      list($user, $pass) = preg_split("/[=:]/", $line, 2);
      $authtable[$user] = $pass;
    }
  }
  return $authtable;
}

// create an authfile and do not proceed without one
function ensure_authfile() {
  // if they have asked us to create the first auth
  if (isset($_REQUEST['username']) && isset($_REQUEST['password'])) {
    // create the first authfile
    file_put_contents($authfilename, $_REQUEST['username'] . ": " . htpass($_REQUEST['password']));
    die(json_encode("AUTH_FILE_CREATED"));
  } else {
    header('HTTP/1.0 403 Forbidden');
    die(json_encode("AUTH_NO_FILE"));
  }
}

// try to log a user in using supplied credentials
function check_credentials($authtable) {
    // did they supply a username and password?
    if(isset($_REQUEST['username']) && isset($_REQUEST['password'])) {
      if($authtable[$_REQUEST['username']] == $_REQUEST['password'] || $authtable[$_REQUEST['username']] == htpass($_REQUEST['password'])) {
        // auth okay, setup session
        $_SESSION['user'] = $_REQUEST['username'];
        // redirect to required page
        die(json_encode("AUTHENTICATED"));
      } else {
        header('HTTP/1.0 403 Forbidden');
        die(json_encode("AUTH_FAILED"));
      }
    } else {
      // username and password not given so go back to login
      header('HTTP/1.0 403 Forbidden');
      die(json_encode("AUTH_NO_CREDENTIALS"));
    }
}

function cors() {
    // Allow from any origin
    if (isset($_SERVER['HTTP_ORIGIN'])) {
        header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');    // cache for 1 day
    }
    // Access-Control headers are received during OPTIONS requests
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
            header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
            header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");
        exit(0);
    }
}
?>
