<?php
// this API only responds with plaintext
header("Content-type: text/plain");

// allow cross-domain requests to this API by default
cors();

// authenticate the user - script may exit here
authenticate();

// check which major function they want to call otherwise
if (isset($_REQUEST["proxy"])) {
  proxy();
} elseif (isset($_REQUEST["state"])) {
  state();
} else {
  // if we got to here nothing has been returned yet
  // assume auth request and return something useful
  success("AUTHENTICATED");
}

// ******* Main functions ******* //

// authenticate a user
function authenticate() {
  // location of the authfile
  $authfilename = "users.txt";

  // Authenticates users against a text file of username/password pairs.
  $authfile = file_get_contents($authfilename);

  // if the file does not exist yet
  if ($authfile == false) {
    ensure_authfile($authfilename);
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
        success("AUTH_LOGGED_OUT");
      }
    }
  }
}

// simple server side storage of an authenticated client's state
function state() {
  // where this user's state is stored
  $statefilename = basename(str_replace(".", "", $_SESSION["user"])) . ".txt";
  // if the use is POSTing a new state
  if (isset($_REQUEST["state"]) && $_REQUEST["state"] != NULL) {
    json_decode($_REQUEST["state"]);
    if (json_last_error() == JSON_ERROR_NONE) {
      file_put_contents($statefilename, $_REQUEST["state"]);
      success("STATE_WRITTEN");
    } else {
      error("STATE_NOT_JSON_ERROR");
    }
  } else {
    // load up this users's session file (if any) and return it
    if (file_exists($statefilename)) {
      $statefile = file_get_contents($statefilename);
      if ($statefile) {
        die($statefile);
      } else {
        die(json_encode(NULL));
      }
    } else {
      die(json_encode(NULL));
    }
  }
}

// very simple proxy server for cross-domain ajax GET requests for authenticated clients
function proxy() {
  // fetch a remote file and output it directly - ensuring it's an HTTP request
  if ($_REQUEST["proxy"] && (substr($_REQUEST["proxy"], 0, 7) === "http://" || substr($_REQUEST["proxy"], 0, 8) === "https://")) {
    $response = file_get_contents($_REQUEST["proxy"]);
    json_decode($response);
    // if the destination returns json then don't double encode
    if (isset($_REQUEST["raw"]) || (strpos($_SERVER['QUERY_STRING'], "json") !== false && json_last_error() == JSON_ERROR_NONE)) {
      die($response);
    } else {
      die(json_encode($response));
    }
  } else {
    error("INVALID_PROXY_REQUEST");
  }
}

// ******* Helper functions ******* //

// refresh the session id every few requests to prevent session hijacking
function session_refresh() {
  if (!isset($_SESSION['session-regeneration-counter']) || ++$_SESSION['session-regeneration-counter'] >= 16) {
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
function ensure_authfile($authfilename) {
  // if they have asked us to create the first auth
  if (isset($_REQUEST['username']) && isset($_REQUEST['password'])) {
    // create the first authfile
    file_put_contents($authfilename, $_REQUEST['username'] . ": " . htpass($_REQUEST['password']));
    success("AUTH_FILE_CREATED");
  } else {
    header('HTTP/1.0 403 Forbidden');
    error("AUTH_NO_FILE");
  }
}

// try to log a user in using supplied credentials
function check_credentials($authtable) {
    // did they supply a username and password?
    if(isset($_REQUEST['username']) && isset($_REQUEST['password'])) {
      if($authtable[$_REQUEST['username']] == $_REQUEST['password'] || $authtable[$_REQUEST['username']] == htpass($_REQUEST['password'])) {
        // auth okay, setup session
        $_SESSION['user'] = $_REQUEST['username'];
        // return the fact the user was successfully logged in
        success("AUTHENTICATED");
      } else {
        header('HTTP/1.0 403 Forbidden');
        error("AUTH_FAILED");
      }
    } else {
      // username and password not given so go back to login
      header('HTTP/1.0 403 Forbidden');
      error("AUTH_NO_CREDENTIALS");
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

function error($code) {
  die(json_encode(Array("api-error" => $code)));
}

function success($code) {
  die(json_encode(Array("api" => $code)));
}
?>
