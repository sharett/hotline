<?php
/**
* @file
* Header
*
* Displays the top of the HTML page.
* 
* config.php must be included before this file
* 
*/

db_databaseConnect();

$pages = array("Broadcast" => "broadcast.php", 
    "Hotline" => "hotline_staff.php",
    "Call / Text" => "contact.php", 
    "Log" => "log.php");

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
    <title><?php echo $WEBSITE_NAME ?></title>

    <!-- Bootstrap -->

<!-- Latest compiled and minified CSS -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css" integrity="sha384-BVYiiSIFeK1dGmJRAkycuHAHRg32OmUcww7on3RYdg4Va+PmSTsz/K68vbdEjh4u" crossorigin="anonymous">

<!-- Optional theme -->
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap-theme.min.css" integrity="sha384-rHyoN1iRsVXV4nD0JutlnGaslCJuC7uwjduW9SVrLvRYooPp2bWYgmgJQIXwl/Sp" crossorigin="anonymous">

<!-- Custom styles for this template -->
    <link href="dashboard.css" rel="stylesheet">

</head>
<body>

    <nav class="navbar navbar-inverse navbar-fixed-top">
      <div class="container-fluid">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="index.php"><?php echo $WEBSITE_NAME ?></a>
        </div>
        <div id="navbar" class="navbar-collapse collapse">
          <ul class="nav navbar-nav navbar-right">
<?php
foreach ($pages as $title => $page) {
	// remove anything after an underscore, to allow for subpages
	$main_page = removeSubpage($_SERVER['PHP_SELF']);
    if ($main_page == '/' . removeSubpage($page)) {
?>
            <li class="active"><a href="<?php echo $page ?>"><?php echo $title ?> <span class="sr-only">(current)</span></a></li>
<?php
    } else {
?>
            <li><a href="<?php echo $page ?>"><?php echo $title ?></a></li>
<?php
    }
}
?>
          </ul>
        </div>
      </div>
    </nav>

    <div class="container-fluid">
      <div class="row">
         <div class="col-sm-12 main">
 
<?php

/**
* Remove any part of the page name after an underscore
*
* Pages may have subpages - these have an underscore and a subpage name.
* Return only the main page name.
* 
* @param string $page
*   The original page name
*   
* @return string
*   The main page name only.
*/

function removeSubpage($page)
{
	// find the first underscore, if any
	$pos = strpos($page, '_');
	if ($pos === false) {
		// no underscore, return the page unchanged
		return $page;
	}
	
	// return only the first part of the page name
	return substr($page, 0, $pos) . '.php';
}
 
?>
