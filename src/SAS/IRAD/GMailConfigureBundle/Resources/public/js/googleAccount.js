$(function() {

    var validator = $("form#account-form").validate(
        {   rules: {
        		// password validator uses "name" attribute to identify password fields
                "AccountForm[password1]": { password: 'input.username' }, 
                "AccountForm[password2]": { required: true, equalTo: 'input.password1' } 
            },
            messages: {
            	password2: {
                    required: 'Please confirm password',
            		equalTo:  'Password mismatch'
            	}
            },
            submitHandler: 
                function(form) {
                    form.submit();
                },
        	debug: true
        }
    );

    $("input.password1").valid();
    
    passwordResetSetup();
    
});



passwordResetSetup = (function() {
	
	self = {};
	
	function setup() {
		self.button = $("input#resetPasswordButton");
		self.meter  = $("div.password-meter-message");
		self.form   = $("form#account-form");
		self.container = $("div#content");
		
		self.password  = false;
		self.password1 = self.form.find("input.password1");
		self.password2 = self.form.find("input.password2");
		
		self.button.on("click", validatePassword);
	}

	function validatePassword() {
		
		// has our password passed the validator? check the validator messages
		if ( self.meter.text() != 'Strong' ) {
			alert("Please select a strong password for your account.");
			return;
		}
		
		// has the user confirmed the password?
		if ( !self.password2.val() ) {
			alert("Please confirm your password entry.");
			return;
		}
		
		// does the confirmed password match?
		if ( self.password1.val() != self.password2.val() ) {
			alert("Your password entries do not match.");
			return;
		}
		
		// set password global since we are wiping out contents of page
		self.password = self.password1.val();
		
		// set token_hash global before we wipe away our form
		token_hash = $("input#token_hash").val();

		// disable our button
		freezeForm();
		
		// replace DIV body (instructions) with account creation progress
		jQuery.post(
			self.form.attr("action"),
			self.form.serialize(),
			function (response, textStatus) {
				console.log(response);
				if ( response.result == 'OK' ) {
					$("div.steps").hide();
					$("div#step-login").show();
					self.container.html(response.content);
				} else {
					alert(response.message);
					thawForm();
				}
			},
			'json'
		).fail(function() {
			alert("An error occurred while performing this action.");
			thawForm();
		});
	}
	
	
	function freezeForm() {
		self.button.prop('disabled', true).val('Processing...');
		self.form.find("input").prop("readonly", true);
		$("div.steps").hide();
		$("div#step-reset-password").show();
	}
	
	function thawForm() {
		self.button.prop('disabled', false).val('Continue');
		self.form.find("input").prop("readonly", false);
		$("div.steps").hide();
		$("div#step-instructions").show();		
	}	
	
	return setup;
	
})();



// builtin delay before redirects
var global = {};
delayValue = 2000;

function resetPasswordSteps() {
	
	
}


function resetAccountPassword() {
	
	// show provisioning progress
	$('tr#reset_password').show();

	jQuery.post('provision.php',
			    {"action":"reset_google_pw",
		         "password":password,
		         "token_hash":token_hash},
		        function (response, textStatus) {

		        	 // we don't need the password any more
		        	 password = "********";
		        	 
		        	 // hide our "creating" message
		        	 $('tr#reset_password').hide();


		        	 if ( textStatus != 'success' ) {
		        		 $('tr#reset_password_error').show();
		        		 return;
		        	 }
	        		 
	        		 if ( response.result == 'account_password_reset' )  {
		        		 // show account creation success message
		        		 $('tr#reset_password_ok').show();
		        		 $('tr#next_step').show();
		        		 // next step: User must initialize account
		        		 setTimeout("initializeAccount()", delayValue)

	        		 } else {
	        			 // some other error occurred
		        		 $('tr#reset_password_error').show();
		        		 alert(response.error);
		        	 }
		         },
		        'json');

}


function redirect(action) {
	document.location = "provision.php?action=" + action;
}

/**
 * Ajax load page contents for initializing an account
 * @return
 */
function initializeAccount() {

	// replace DIV body (instructions) with account creation progress
	jQuery.post(
		"provision.php",
		{"action":"reset_password_success",
		 "token_hash":token_hash},
		function (response, textStatus) {
			 $("div#body").html(response);
		}
	);
}

//globals for token_hash, password, and mail_forwarding (checkbox) values
token_hash = '';
password   = '';
mail_forwarding = '';

// builtin delay before redirects
delayValue = 2000;

function createAccountSteps() {
	
	// has our password passed the validator? check the validator messages
	if ( $("div.password-meter-message").text() != 'Strong' ) {
		alert("Please select a strong password for your account.");
		return;
	}
	
	// has the user confirmed the password?
	if ( !$("input#password2").val() ) {
		alert("Please confirm your password entry.")
		return;
	}
	
	// does the confirmed password match?
	if ( $("input#password1").val() != $("input#password2").val() ) {
		alert("Your password entries do not match.")
		return;
	}
	
	// set password global since we are wiping out contents of page
	password = $("input#password1").val();
	
	// set token_hash global before we wipe away our form
	token_hash = $("input#token_hash").val();

	// set mail_forwarding global before we wipe away our form
	mail_forwarding = $("input#mail_forwarding:checked").val();

	// disable our button
	$("input#createAccountButton").attr('disabled', 'disabled').val('Processing...');
	
	// replace DIV body (instructions) with account creation progress
	jQuery.post(
		"provision.php",
		{"action":"create_account",
		 "token_hash":token_hash},
		function (response, textStatus) {
			 $("div#body").html(response);
			 checkEligibility();
		}
	);
	
}


function provisionAccount() {
	
	// show provisioning progress
	$('tr#create_account').show();

	jQuery.post('provision.php',
			    {"action":"provision_account",
		         "password":password,
		         "token_hash":token_hash},
		        function (response, textStatus) {

		        	 // we don't need the password any more
		        	 password = "********";
		        	 
		        	 // hide our "creating" message
		        	 $('tr#create_account').hide();


		        	 if ( textStatus != 'success' ) {
		        		 $('tr#create_account_error').show();
		        		 return;
		        	 }
	        		 
	        		 if ( response.result == 'account_exists' ) {
	        			 // show account exists error
		        		 $('tr#account_exists').show();
		        		 return;

	        		 } else if ( response.result == 'account_activated' )  {
		        		 // show account creation success message
		        		 $('tr#create_account_ok').show();
		        		 // next step: configure mail forwarding
		        		 setMailForwarding();

	        		 } else {
	        			 // some other error occurred
		        		 $('tr#create_account_error').show();
		        		 alert(response.error);
		        	 }
		         },
		        'json');

}


function setMailForwarding() {

	if ( mail_forwarding == 'yes' ) {
	
		// show provisioning progress
		$('tr#mail_forwarding').show();

		jQuery.post('provision.php',
				    {"action":"mail_forwarding",
			         "token_hash":token_hash},
			        function (response, textStatus) {
	
			        	 // hide our "creating" message
			        	 $('tr#mail_forwarding').hide();
	
	
		        		 if ( textStatus == 'success' && response.result == 'mail_forwarding_set' )  {
			        		 // show account creation success message
			        		 $('tr#mail_forwarding_ok').show();
			        		 $('tr#next_step').show();
	
			        		 // next step: User must initialize account
			        		 setTimeout("initializeAccount()", delayValue)
	
		        		 } else {
		        			 
		        			 // some other error occurred
			        		 $('tr#mail_forwarding_error').show();
			        		 $('tr#next_step').show();

		        		 	 // show the specific error
		        			 alert(response.result);

			        		 // next step: User must initialize account
			        		 setTimeout("redirect('create_error')", delayValue)
			        	 }
			         },
			        'json');
	} else {

		// user is creating an apps-only account, so just update display without setting any mail forwarding.
		 $('tr#next_step').show();

		 // next step: User must initialize account
		 setTimeout("initializeAccount()", delayValue)
	}
}


/**
 * Ajax load page contents for initializing an account
 * @return
 */
function initializeAccount() {

	// replace DIV body (instructions) with account creation progress
	jQuery.post(
		"provision.php",
		{"action":"initialize",
	     "mail_forwarding":mail_forwarding,
		 "token_hash":token_hash},
		function (response, textStatus) {
			 $("div#body").html(response);
		}
	);
}


function forwardingAlert() {
	
	if ( $("input#mail_forwarding:checked").length ) {
		alert('By checking this box, you are directing your SAS email\n' +
			  'to be delivered to your Google@SAS account.');
	} else {
		alert('By unchecking this box, you are creating a Google Apps only\n'    +
			  'account. You will be able to use all the Google Apps services,\n' + 
			  'but your SAS email will NOT be delivered to this account.');
	}
	
}




