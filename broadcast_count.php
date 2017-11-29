<?php
/**
* @file
* Return a count of how many texts will be sent, when supplied with
* a list of tags
* 
*/

require_once 'config.php';

db_databaseConnect();

// URL parameters
$tag_boxes = isset($_REQUEST['tag_boxes']) ? $_REQUEST['tag_boxes'] : array();
$tag_names = isset($_REQUEST['tag_names']) ? $_REQUEST['tag_names'] : array();

// Create an array of selected tags from the $tag_names and $tag_boxes parameters
$tags_selected = array();
foreach ($tag_boxes as $tag_number => $tag_box) {
	if ($tag_box == 'on') {
		$tags_selected[] = $tag_names[$tag_number];
	}
}

// Are any tags selected?
if (count($tags_selected)) {
	// yes, load the active numbers, limited by tags
	
	// create the SQL for the tags
	$sql_tags = '';
	foreach ($tags_selected as $tag) {
		$sql_tags[] = "tag='".addslashes($tag)."'";
	}
	
	$sql = "SELECT COUNT(DISTINCT phone) FROM broadcast ".
		"LEFT JOIN broadcast_tags ON broadcast.id = broadcast_tags.broadcast_id ".
        "WHERE status='active' AND (".implode(' OR ', $sql_tags).")";
	if (db_db_getone($sql, $broadcast_count, $error)) {
		echo $broadcast_count;
	} else {
		// an error, but no way to report it
		echo 0;
	}	
} else {
	// no tags, get the count of the active numbers
	$sql = "SELECT COUNT(*) FROM broadcast WHERE status='active'";
	if (db_db_getone($sql, $broadcast_count, $error)) {
		echo $broadcast_count;
	} else {
		// an error, but no way to report it
		echo 0;
	}
}

db_databaseDisconnect();

?>
