/**
* @file
* Javascript functions to place an in-browser call using Twilio
*
* Adapted from the quickstart demo on twilio.com
* 
*/

$(function () {
  // are the call controls visible?
  if (!document.getElementById('call-controls')) {
	  // no, exit
	  return;
  }
  
  // get the token from the twilio server
  $.getJSON('call/token.php')
    .done(function (data) {
      // token retrieved, setup Twilio.Device
      Twilio.Device.setup(data.token);

      Twilio.Device.ready(function (device) {
		// device is ready - show the controls
        log('Ready');
        document.getElementById('button-call').style.display = 'inline';
        document.getElementById('button-hangup').style.display = 'none';
        document.getElementById('call-controls').style.display = 'block';
      });

      Twilio.Device.error(function (error) {
		// log a device error
        log('Twilio.Device Error: ' + error.message);
      });

      Twilio.Device.connect(function (conn) {
		// call in progress, show the hangup button instead of call
        log('Call established');
        document.getElementById('button-call').style.display = 'none';
        document.getElementById('button-hangup').style.display = 'inline';
      });

      Twilio.Device.disconnect(function (conn) {
		// call ended, show the call button instead of hangup
        log('Call ended');
        document.getElementById('button-call').style.display = 'inline';
        document.getElementById('button-hangup').style.display = 'none';
      });
    })
    .fail(function () {
	  // failed to get a token!
      log('Failed to set up for calling');
    });

  // bind the "Call" button
  document.getElementById('button-call').onclick = function () {
    // get the phone number to connect the call to
    var params = {
      To: document.getElementById('phone-number').value
    };

    // write this call to the database
    $.getJSON('call/store.php', params)
    .fail(function() {
		log('Could not write a record of this call to the database.');
	});
    
    // connect!
    Twilio.Device.connect(params);
  };

  // bind the "Hangup" button
  document.getElementById('button-hangup').onclick = function () {
	// hang up the call
    log('Hanging up...');
    Twilio.Device.disconnectAll();
  };
});

// show the lastest log message
function log(message) {
  var logDiv = document.getElementById('log');
  logDiv.innerHTML = '(' + message + ')';
}
