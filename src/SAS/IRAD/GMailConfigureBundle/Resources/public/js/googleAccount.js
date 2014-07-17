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
    
    forwardAlertSetup();
    accountActionSetup();
    
});



accountActionSetup = (function() {
	
	self = {};
	
	function setup() {
		self.button = $("button#continueButton");
		self.meter  = $("div.password-meter-message");
		self.form   = $("form#account-form");
		self.container = $("div#content");
		self.loader = $("img#loader-img");
		
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
				if ( response.result == 'OK' ) {
					$("div.steps").hide();
					$("div#step-3").show();
					self.container.html(response.content);
				} else {
					showError(response.message);
					thawForm();
				}
			},
			'json'
		).fail(function() {
			showError("An error occurred while performing this action.");
			thawForm();
		});
	}
	
	function showError(message) {
		self.button.text('Error');
		alert(message);
	}
	
	function freezeForm() {
		self.button.prop('disabled', true)
			.text('Processing ')
			.append( self.loader.clone().show() );
		self.form.find("input").prop("readonly", true);
		$("div.steps").hide();
		$("div#step-2").show();
	}
	
	function thawForm() {
		self.button.prop('disabled', false).text('Continue');
		self.form.find("input").prop("readonly", false);
		$("div.steps").hide();
		$("div#step-1").show();		
	}	
	
	return setup;
	
})();



forwardAlertSetup = (function() {
	
	function setup() {
		$("input.mail-forwarding").on("change", forwardAlert);
	}
	
	function forwardAlert(event) {
		
		var checkbox = $(event.currentTarget);
		
		if ( checkbox.prop('checked') ) {
			alert('By checking this box, you are directing your SAS email\n' +
			  'to be delivered to your Google@SAS account.');
		} else {
			alert('By unchecking this box, you are creating a Google Apps only\n'    +
			  'account. You will be able to use all the Google Apps services,\n' + 
			  'but your SAS email will NOT be delivered to this account.');
		}
	}
	
	return setup;
})();




