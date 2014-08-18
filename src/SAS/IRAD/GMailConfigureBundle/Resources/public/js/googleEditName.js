$(function() {
	updateNameSetup();
});


updateNameSetup = (function() {

	var self = {};
	
	function setup() {
		self.form      = $("form#updateName");
	    self.firstName = self.form.find("input.firstNameField");
	    self.lastName  = self.form.find("input.lastNameField");
	    self.button    = self.form.find("button#updateNameButton");
	    self.progress  = $("img#progress-img");
		
	    self.button.on("click", validateName);
	}
	

	function validateName() {
	
	    var invalidChars = /[\<\>\"\&\%]/;

	    if ( !self.firstName.val() ) {
			alert("Please enter a first name.");
			return;
		}
	
	    if ( invalidChars.test(self.firstName.val()) ) {
	        alert("Your first name contains invalid characters.");
	        return;
	    }
		
		if ( !self.lastName.val() ) {
			alert("Please enter a last name.");
			return;
		}
	
	    if ( invalidChars.test(self.lastName.val()) ) {
	        alert("Your last name contains invalid characters.");
	        return;
	    }
		
	    freezeForm();
		self.form.submit();
	}
	
	function freezeForm() {
		self.button.prop('disabled', true)
			.text('Processing ')
			.append( self.progress.clone().show() );
		self.form.find("input").prop("readonly", true);
	}
	
	function thawForm() {
		self.button.prop('disabled', false).text('Update');
		self.form.find("input").prop("readonly", false);
	}
	
	return setup;
})();