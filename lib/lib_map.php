<?php
/**
* @file
* Library of mapping functions
* 
* ../config.php is required before including this file
*
*/

/**
* Retrieve the broadcast numbers within a certain distance of a point
*
* Uses a very approximate bounding box method which is very fast.
* 
* @param float $lat
*   Latitude of the central point
* @param float $lon
*   Longitude of the central point
* @param float $distance
*   Distance, in miles.
* @param array &$numbers
*   Set to an array of numbers within the specified distance
* @param string &$error
*   An error if one occurred.
*   
* @return bool
*   True if retrieved successfully.
*/

function map_getNumbersWithin($lat, $lon, $distance, &$numbers, &$error)
{
	$R = 3956.5; // radius of the earth, in miles
	
    // calculate a bounding box
	$maxLat = $lat + rad2deg($distance / $R);
	$minLat = $lat - rad2deg($distance / $R);
	$maxLon = $lon + rad2deg(asin($distance / $R) / cos(deg2rad($lat)));
	$minLon = $lon - rad2deg(asin($distance / $R) / cos(deg2rad($lat)));

	$sql = "SELECT phone FROM broadcast ".
		"LEFT JOIN locations ON broadcast.zipcode = locations.zipcode ".
		"WHERE broadcast.zipcode != '' AND ".
		"latitude BETWEEN '".addslashes($minLat)."' AND '".addslashes($maxLat)."' AND ".
		"longitude BETWEEN '".addslashes($minLon)."' AND '".addslashes($maxLon)."'";
    if (!db_db_getcol($sql, $numbers, $error)) {
        return false;
    }

    return true;
}

?>
