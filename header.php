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

pp_databaseConnect();

$pages = array("Broadcast" => "broadcast.php", "Log" => "log.php", 
    "Call / Text" => "contact.php", 
    "Contacts" => "contacts.php", "Languages" => "languages.php", 
	"Database" => "sanctdb/");

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
    if ($_SERVER['PHP_SELF'] == '/' . $page) {
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
 
