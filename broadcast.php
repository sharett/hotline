<?php
/**
* @file
* Tools to send a mass text, and import, remove and list numbers.
* 
*/

require_once 'config.php';
require_once $LIB_BASE . 'lib_sms.php';

include 'header.php';

// URL parameters
$action = $_REQUEST['action'];
$text = trim($_REQUEST['text']);
$request_response = trim($_REQUEST['response']);
$confirmed = $_REQUEST['confirm'];
$communications_id = $_REQUEST['id'];

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
		if (sendBroadcastText($text, $request_response, $error, $success)) {
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

// get the count of the active numbers
$sql = "SELECT COUNT(*) FROM broadcast WHERE status='active'";
db_db_getone($sql, $broadcast_count, $error);

// any error message?
if ($error) {
?>
	      <div class="alert alert-danger" role="alert"><?php echo $error ?></div>
<?php
}

// any success message?
if ($success) {
?>
	      <div class="alert alert-success" role="alert"><?php echo $success ?></div>
<?php
}

// display the broadcast information
?>

	      <script type="text/javascript">
			// calculate the number of characters and cost to send this text
		    function showTextLength() {
				var count = $('#text_entry').val().trim().length;
				var response_count = $('#request_response').val().trim().length;
				if (response_count) {
					count += response_count + 1;
				}
				var sms_count = Math.floor(count / 160) + 1;
				var cost = sms_count * <?php echo $broadcast_count ?> * <?php echo $TWILIO_COST_PER_TEXT ?>;
				
				if (count == 0) {
					$('#text_entry_length').text('(0 characters)');
				} else {
					$('#text_entry_length').text('(' + count + ' characters, $' + cost.toFixed(2) + ')');
				}
			}
	      </script>
	      
          <h2 class="sub-header">Broadcast</h2>
   		  <ul class="nav nav-pills">
			<li role="presentation" class="active"><a href="broadcast.php">Send</a></li>
			<li role="presentation"><a href="broadcast_admin.php">Import &amp; Remove</a></li>
			<li role="presentation"><a href="broadcast_admin.php?action=list">List</a></li>
			<li role="presentation"><a href="contact.php?ph=<?php echo $BROADCAST_CALLER_ID ?>&hide=1">Log</a></li>
		  </ul>
		  <br />
		  
          <form id="text-controls" action="broadcast.php" method="POST">
		   <input type="hidden" name="action" value="broadcast">
		   <div class="form-group">
			<label for="text_entry">Send a new broadcast text message to <?php echo $broadcast_count ?> numbers:</label>
			<input type="text" class="form-control" name="text" 
			       id="text_entry" onKeyUp="showTextLength();" onKeyDown="showTextLength();"
			       placeholder="Text message" value="<?php echo $text ?>">
			<p class="help-block" id="text_entry_length"></p>
 		   </div>
 		   <div class="form-group">
			<label for="request_response">Response requested?</label>
			<input type="text" class="form-control" name="response" 
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
<?php
}

// display the footer
include 'footer.php';

/**
* Send a broadcast text.
*
* Sends a text to all active numbers in the broadcast list.
* 
* @param string $text
*   The text to send.
* @param string $request_response
*   The additional text requesting a response if they want to participate further.
* @param string &$error
*   An error if one occurred.
* @param string &$message
*   An informational message if appropriate.
*   
* @return bool
*   True if sent.
*/

function sendBroadcastText($text, $request_response, &$error, &$message)
{
	global $BROADCAST_CALLER_ID;
	
	$error = '';
	$message = '';

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
	
	//$message = "Disabled, would have sent '{$text}'.";
	//return true;
	
	// load the broadcast numbers
	$sql = "SELECT phone FROM broadcast WHERE status='active'";
	if (!db_db_getcol($sql, $numbers, $error)) {
		return false;
	}
	
	if (count($numbers) == 0) {
		$error = "No numbers to send to.";
		return false;
	}
	
	// send the texts
	if (!sms_send($numbers, $text, $error, $BROADCAST_CALLER_ID)) {
		return false;
	}
	
	// store the text
	$data = array(
		'From' => $BROADCAST_CALLER_ID,
		'To' => ($request_response ? 'BROADCAST_RESPONSE' : 'BROADCAST'),
		'Body' => $text,
		'MessageSid' => 'text'
	);
	sms_storeCallData($data, $error);

	$message = "Text sent to " . count($numbers) . " numbers.";
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
	global $BROADCAST_CALLER_ID;
	
	$error = '';
	$message = '';

	$text = trim($text);
	if (!$text) {
		$error = "No text was provided.";
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
	
	// send the texts
	if (!sms_send($numbers, $text, $error, $BROADCAST_CALLER_ID)) {
		return false;
	}
	
	// store the text
	$data = array(
		'From' => $BROADCAST_CALLER_ID,
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

?>
