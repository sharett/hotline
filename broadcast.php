<?php
/**
* @file
* Tools to send a mass text, and import, remove and list numbers.
* 
*/

require_once 'config.php';
require_once $LIB_BASE . 'lib_sms.php';

// required to avoid output buffering problems when sending progress marks
// as texts are sent
header('Content-type: text/html; charset=utf-8');

include 'header.php';

// URL parameters
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
$text = isset($_REQUEST['text']) ? trim($_REQUEST['text']) : '';
$include_tags = isset($_REQUEST['include_tags']) ? $_REQUEST['include_tags'] : array();
$tag_names = isset($_REQUEST['tag_names']) ? $_REQUEST['tag_names'] : array();
$request_response = isset($_REQUEST['response']) ? trim($_REQUEST['response']) : '';
$confirmed = isset($_REQUEST['confirm']) ? $_REQUEST['confirm'] : '';
$communications_id = isset($_REQUEST['id']) ? $_REQUEST['id'] : '';

// initialize variables
$broadcast_tags = array();

// Create an array of selected tags from the $tag_names and $include_tags parameters
$tags_selected = array();
foreach ($include_tags as $tag_number => $include_tag) {
	if ($include_tag == 'on') {
		$tags_selected[] = $tag_names[$tag_number];
	}
}

// Authorized user?
$authorized = empty($BROADCAST_AUTHORIZED_USERS) || 
	in_array($_SERVER['PHP_AUTH_USER'], $BROADCAST_AUTHORIZED_USERS);
if (!$authorized) {
	// no
	$error = "You are not authorized to send broadcast texts.";
}

// *** ACTIONS ***

// send a text message?
if ($action == 'broadcast' && $authorized) {
	if ($confirmed == 'on') {
		// confirmed, send the broadcast
		if (sendBroadcastText($text, $request_response, $tags_selected, $error, $success)) {
			$text = '';
			$request_response = '';
		}
	} else {
		// checkbox wasn't checked
		$error = "Please check the confirmation checkbox.";
	}
}

// send a text message to those who responded to a previous broadcast?
if ($action == 'broadcast_response' && $authorized) {
	if (sendBroadcastResponseText($text, $communications_id, $error, $success)) {
		$text = '';
	}
}

// close the broadcast update?
if ($action == 'broadcast_close' && $authorized) {
	if ($confirmed == 'on') {
		// confirmed, close the broadcast
		if (sms_closeBroadcastResponse($error)) {
			$success = "The broadcast update was closed.";
		}
	} else {
		// checkbox wasn't checked
		$error = "Please check the confirmation checkbox.";
	}
}

// load the tags
loadBroadcastTags($broadcast_tags, $error);

// any error message?
if (!empty($error)) {
?>
	      <div class="alert alert-danger" role="alert"><?php echo $error ?></div>
<?php
}

// any success message?
if (!empty($success)) {
?>
	      <div class="alert alert-success" role="alert"><?php echo $success ?></div>
<?php
}

// display the broadcast information
?>

	      <script type="text/javascript">
			var broadcast_count = 0;  
			
			// calculate the number of characters and cost to send this text
		    function showTextLength() {
				var count = $('#text_entry').val().trim().length;
				var response_count = $('#request_response').val().trim().length;
				if (response_count) {
					count += response_count + 1;
				}
				var sms_count = Math.floor(count / 160) + 1;
				var cost = sms_count * broadcast_count * <?php echo $TWILIO_COST_PER_TEXT ?>;
				
				if (count == 0) {
					$('#text_entry_length').text('(to ' + broadcast_count + ' numbers, 0 characters)');
				} else {
					$('#text_entry_length').text('(to ' + broadcast_count + ' numbers, ' + count + ' characters, $' + cost.toFixed(2) + ')');
				}
			}
			
			// load the count of numbers to send to, which changes depending on which tags are checked
			function updateBroadcastCount() {
				$.post("broadcast_count.php", $( "#broadcast" ).serialize(), 
					function(data) {
						// the count is returned
						broadcast_count = parseInt(data);
						showTextLength();
					} 
				);
			}
	      </script>
	      
          <h2 class="sub-header">Broadcast</h2>
   		  <ul class="nav nav-pills">
			<li role="presentation" class="active"><a href="broadcast.php">Send</a></li>
			<li role="presentation"><a href="broadcast_admin.php">Import &amp; Remove</a></li>
			<li role="presentation"><a href="broadcast_admin.php?action=list">List</a></li>
			<li role="presentation"><a href="log.php?ph=all_broadcast">Log</a></li>
		  </ul>
		  <br />
		  
          <form id="broadcast" action="broadcast.php" method="POST">
		   <input type="hidden" name="action" value="broadcast">
		   <div class="form-group">
			<label for="text_entry">Send a new broadcast text message:</label>
			<input type="text" class="form-control" name="text" maxLength="1600" 
			       id="text_entry" onKeyUp="showTextLength();" onKeyDown="showTextLength();"
			       placeholder="Text message" value="<?php echo $text ?>">
			<p class="help-block" id="text_entry_length">&nbsp;</p>
 		   </div>

		   <div class="form-group">
			 <label>Limit to tags:</label><br>
<?php
	if (count($broadcast_tags) > 0) {
		foreach ($broadcast_tags as $tag => $tag_data) {
?>
 		     <label class="checkbox-inline">
			   <input type="hidden" name="tag_names[<?php echo $tag_data['id'] ?>]" value="<?php echo addslashes($tag) ?>">
			   <input type="checkbox" name="include_tags[<?php echo $tag_data['id'] ?>]" onClick="updateBroadcastCount();" <?php if ($include_tags[$tag_data['id']] == 'on') { echo " checked"; } ?>>
			     <span class="label label-primary"><?php echo $tag ?></span> 
			     <span class="badge"><?php echo $tag_data['count'] ?></span>
			 </label>
<?php
		}
	} else {
		echo "(no tags)";
	}
?> 		   
		   </div>

 		   <div class="form-group">
			<label for="request_response">Response requested?</label>
			<input type="text" class="form-control" name="response" maxLength="1600" 
			       id="request_response" onKeyUp="showTextLength();" onKeyDown="showTextLength();"
			       placeholder="Reply yes if you can participate" 
			       value="<?php echo $request_response ?>">
			<p class="help-block">
			  If set, if they reply 'yes' they'll get future texts for this broadcast.  Make sure
			  to include the instruction to reply yes!
			</p>
 		   </div>
 		   <div class="checkbox">
			 <label>
			   <input type="checkbox" name="confirm"> Confirm sending this broadcast
			 </label>
		   </div>
		   <button class="btn btn-success" id="button-text">Send broadcast</button>
		  </form>
		  
<?php
// load the latest broadcast that requested a response
sms_getBroadcastResponse($broadcast_response, $error);

if ($broadcast_response) {
	// get the list of people who have responded 'yes'
	getBroadcastResponseConfirmed($broadcast_response['id'], $broadcast_response_confirmed, $error);
?>
		  <hr />
		  <h3>Send update</h3>
		  <p><b>For:</b> &quot;<?php echo $broadcast_response['body'] ?>&quot; (sent 
		   <?php echo date("m/d/y h:i a", strtotime($broadcast_response['communication_time'])); ?>)</p> 
		  <form id="broadcast-response" action="broadcast.php" method="POST">
		   <input type="hidden" name="action" value="broadcast_response">
		   <input type="hidden" name="id" value="<?php echo $broadcast_response['id'] ?>">
		   <div class="form-group">
			<label for="broadcast_text_entry"><?php echo count($broadcast_response_confirmed) ?> 
				<?php echo (count($broadcast_response_confirmed) == 1) ? 'person has' : 'people have' ?> replied.  
				Send them a text:</label>
			<input type="text" class="form-control" name="text" 
			       id="broadcast_text_entry"
			       placeholder="Text message" value="<?php echo $text ?>">
 		   </div>
		   <button class="btn btn-success" id="button-text">Send update</button>
		  </form>
		  
		  <br />
		  <h4>Responders:</h4>
		  <p><?php echo implode(', ', $broadcast_response_confirmed) ?></p>

		  <h3>Close broadcast</h3>
		  <form id="broadcast-close" action="broadcast.php" method="POST">
		   <input type="hidden" name="action" value="broadcast_close">
		   <div class="checkbox">
			 <label>
			   <input type="checkbox" name="confirm"> Confirm closing this broadcast
			 </label>
		   </div>
		   <button class="btn btn-danger" id="button-text">Close broadcast</button>
		  </form>
<?php
}

// display the footer
include 'footer.php';

?>
<script type="text/javascript">
// load the broadcast count - placed at the end after jquery is loaded
updateBroadcastCount();
</script>
<?php

/**
* Send a broadcast text.
*
* Sends a text to all active numbers in the broadcast list.
* 
* @param string $text
*   The text to send.
* @param string $request_response
*   The additional text requesting a response if they want to participate further.
* @param array $tags
*   The tags to limit the broadcast to, if any are set.
* @param string &$error
*   An error if one occurred.
* @param string &$message
*   An informational message if appropriate.
*   
* @return bool
*   True if sent.
*/

function sendBroadcastText($text, $request_response, $tags, &$error, &$message)
{
	global $BROADCAST_CALLER_IDS, $BROADCAST_PROGRESS_MARK_EVERY, $BROADCAST_TWILIO_NOTIFY_SERVICE;
	
	$error = '';
	$message = '';
	$tags_description = '';
	
	// is there a response request?
	if (trim($request_response)) {
		// trim it, and put a space between the text and the response request
		$request_response = ' ' . trim($request_response);
	} else {
		// it's empty, or just whitespace
		$request_response = '';
	}

	$text = trim($text) . $request_response;
	if (!$text) {
		$error = "No text was provided.";
		return false;
	}
	
	// limited to specific tags?
	if (count($tags)) {
		$tags_description = " (LIMITED TO TAGS: " . implode(', ', $tags) . ")";
	}
	
	// confirm that the text is not identical to the previous broadcast text sent
	$sql = "SELECT * FROM communications WHERE phone_to LIKE 'BROADCAST%' ".
		"ORDER BY communication_time DESC LIMIT 1";
	if (!db_db_getrow($sql, $last_broadcast, $error)) {
		return false;
	}
	if ($last_broadcast['body'] == ($text . $tags_description)) {
		// it is identical!
		$error = "This text is identical to the last one sent - aborting!";
		return false;
	}
	
	// create the SQL for the tags
	$sql_tags = '';
	foreach ($tags as $tag) {
		$sql_tags[] = "tag='".addslashes($tag)."'";
	}
	
	// load the numbers to broadcast to, limited by tags if any tags are set
	$sql = "SELECT DISTINCT phone FROM broadcast ".
		(count($tags)
		    ? ("LEFT JOIN broadcast_tags ON broadcast.id = broadcast_tags.broadcast_id ".
			   "WHERE status='active' AND (".implode(' OR ', $sql_tags).")")
			: "WHERE status='active'");
	if (!db_db_getcol($sql, $numbers, $error)) {
		return false;
	}
	
	if (count($numbers) == 0) {
		$error = "No numbers to send to.";
		return false;
	}

	// use the first caller id as the default
	$broadcast_from = reset($BROADCAST_CALLER_IDS);

	// send via Twilio notify?
	if ($BROADCAST_TWILIO_NOTIFY_SERVICE) {
		// yes
		if (!sms_sendViaNotify($numbers, $text, $error)) {
			return false;
		}
	} else {
		// no, send the texts one by one using the first broadcast caller_id
		if (!sms_send($numbers, $text, $error, $broadcast_from, 
					  $BROADCAST_PROGRESS_MARK_EVERY)) {
			return false;
		}
	}
	
	// store the text
	$data = array(
		'From' => $broadcast_from,
		'To' => ($request_response ? 'BROADCAST_RESPONSE' : 'BROADCAST'),
		'Body' => $text . $tags_description,
		'MessageSid' => 'text'
	);
	sms_storeCallData($data, $error);

	$message = "Text sent to " . count($numbers) . " numbers" .
		($error ? ' (except errors listed above).' : '.');
	return true;
}

/**
* Send a text to those who responded to a previous broadcast
*
* ...
* 
* @param string $text
*   The text to send.
* @param int $communications_id
*   The original broadcast text id
* @param string &$error
*   An error if one occurred.
* @param string &$message
*   An informational message if appropriate.
*   
* @return bool
*   True if sent.
*/

function sendBroadcastResponseText($text, $communications_id, &$error, &$message)
{
	global $BROADCAST_CALLER_IDS, $BROADCAST_TWILIO_NOTIFY_SERVICE, $BROADCAST_PROGRESS_MARK_EVERY;
	
	$error = '';
	$message = '';

	$text = trim($text);
	if (!$text) {
		$error = "No text was provided.";
		return false;
	}
	
	// confirm that the text is not identical to the previous broadcast text sent
	$sql = "SELECT * FROM communications WHERE phone_to LIKE 'BROADCAST%' ".
		"ORDER BY communication_time DESC LIMIT 1";
	if (!db_db_getrow($sql, $last_broadcast, $error)) {
		return false;
	}
	if ($last_broadcast['body'] == $text) {
		// it is identical!
		$error = "This text is identical to the last one sent - aborting!";
		return false;
	}
	
	// get the list of people who have responded 'yes'
	if (!getBroadcastResponseConfirmed($communications_id, $numbers, $error)) {
		return false;
	}
	
	if (count($numbers) == 0) {
		$error = "No numbers to send to.";
		return false;
	}
	
	// use the first caller id as the default
	$broadcast_from = reset($BROADCAST_CALLER_IDS);

	// send via Twilio notify?
	if ($BROADCAST_TWILIO_NOTIFY_SERVICE) {
		// yes
		if (!sms_sendViaNotify($numbers, $text, $error)) {
			return false;
		}
	} else {
		// no, send the texts one by one using the first broadcast caller_id
		if (!sms_send($numbers, $text, $error, $broadcast_from, 
					  $BROADCAST_PROGRESS_MARK_EVERY)) {
			return false;
		}
	}
	
	// store the text
	$data = array(
		'From' => $broadcast_from,
		'To' => 'BROADCAST_RESPONSE_UPDATE',
		'Body' => $text,
		'MessageSid' => 'text'
	);
	sms_storeCallData($data, $error);

	$message = "Text sent to " . count($numbers) . " numbers.";
	return true;
}

/**
* Get the list of people who have responded to a particular broadcast
*
* ...
* 
* @param int $communications_id
*   The broadcast people have responded to
* @param array &$numbers
*   Set to the list of numbers of people who have responded
* @param string &$error
*   An error if one occurred.
*   
* @return bool
*   True unless an error occurred.
*/

function getBroadcastResponseConfirmed($communications_id, &$numbers, &$error)
{
	// get the list of people who have responded 'yes'
	$sql = "SELECT broadcast.phone FROM broadcast_responses ".
		"LEFT JOIN broadcast ON broadcast.id = broadcast_responses.broadcast_id ".
		"WHERE communications_id='".addslashes($communications_id)."' AND ".
		" broadcast.status='active' ".
		"ORDER BY broadcast.phone";
	if (!db_db_getcol($sql, $numbers, $error)) {
		return false;
	}
	
	return true;
}

/**
* Load tags and the count of numbers for each
*
* ...
* 
* @param array &$broadcast_tags
*   Set to an array - keys are the tags, values are the count for each tag
* @param string &$error
*   An error if one occurred.
*   
* @return bool
*   True unless an error occurred.
*/

function loadBroadcastTags(&$broadcast_tags, &$error)
{
	$sql = "SELECT DISTINCT tag FROM broadcast_tags ORDER BY tag";
	if (!db_db_getcol($sql, $tags, $error)) {
		return false;
	}
	
	$tag_id = 0;
	$broadcast_tags = array();
	foreach ($tags as $tag) {
		$sql = "SELECT COUNT(*) FROM broadcast_tags ".
			"LEFT JOIN broadcast ON broadcast.id = broadcast_tags.broadcast_id ".
			"WHERE broadcast_tags.tag='".addslashes($tag)."' AND broadcast.status='active'";
		if (!db_db_getone($sql, $tag_count, $error)) {
			return false;
		}
		
		$broadcast_tags[$tag] = array(
			'id' => $tag_id++,
			'count' => $tag_count
		);
	}
	
	return true;
}


?>
