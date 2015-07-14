<?php
// simple server side storage of an authenticated client's state

// ensure the user is authenticated
include("auth.php");

// where this user's state is stored
$statefilename = basename(str_replace($_SESSION["user"], ".", "")) . ".txt";

// if the use is POSTing a new state
if (isset($_REQUEST["state"])) {
  file_put_contents($statefilename, $_REQUEST["state"]);
  die(json_encode("STATE_WRITTEN"));
} else {
  // load up this users's session file (if any) and return it
  print file_get_contents($statefilename);
}
?>
