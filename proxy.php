<?php
// very simple proxy server for cross-domain ajax GET requests for authenticated clients

// ensure the user is authenticated
include("auth.php");

// fetch a remote file and output it directly - ensuring it's an HTTP request
if ($_REQUEST["url"] && (substr($_REQUEST["url"], 0, 7) === "http://") || substr($_REQUEST["url"], 0, 8) === "https://")) {
  $response = file_get_contents($_REQUEST["url"]);
  // if the destination returns json then don't double encode
  if (strpos($_SERVER['QUERY_STRING'], "json") !== false) {
    print($response);
  } else {
    print(json_encode($response));
  }
}
?>
