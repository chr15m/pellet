<?php
// very simple proxy server for cross-domain ajax GET requests for authenticated clients

// ensure the user is authenticated
include("auth.php");

// fetch a remote file and output it directly
if ($_REQUEST["url"]) {
  $response = file_get_contents($_REQUEST["url"]);
  // if the destination returns json then don't double encode
  if (strpos($_SERVER['QUERY_STRING'], "json") !== false) {
    print($response);
  } else {
    print(json_encode($response));
  }
}
?>
