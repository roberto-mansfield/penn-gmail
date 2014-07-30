$().ready(function(){
	mailForwardingForm();
});


mailForwardingForm = (function() {
	
	var self = {};
	
	function setup() {
		
		self.editForm     = $("form#edit-forwarding");
		self.updateForm   = $("form#update-forwarding");
		self.submitButton = $("button#update-forwarding-button");
		self.moreLink     = $("span#more_link");
		self.equivDomains = $.parseJSON(self.editForm.attr("data-equivalent-domains"));
		
		// get the initial forwarding type for the user
		var type = self.editForm.attr('data-forwarding-type');
		self.editForm.find("input[type=radio][value=" + type + "]").prop("checked", true);

		// set initial state of email fields
		toggleEmailFields();
		
		// setup events
		self.editForm.find("input[type=radio]").on("click", toggleEmailFields);
		self.editForm.find("div.field-label-set span.radio-label").on("click", labelClick);
		
		// show more email addresses
		self.moreLink.on("click", showMoreEmails);

		// do we have multiple email addresses?
		if ( type == 'other' && self.editForm.find("input.email[value!='']").length > 1 ) {
			self.moreLink.trigger("click");
		}
		
		
		self.submitButton.on("click", validateForm); 
	}
	
	
	function toggleEmailFields() {
		
		var type = self.editForm.find("input:checked[type=radio]").val();
		
		if ( type == "other" ) {
			self.editForm.find("input.email").prop("disabled", false);
		} else {
			self.editForm.find("input.email").prop("disabled", true);
		}
	};	
	
	
	function labelClick(event) {
		var div = $(event.currentTarget).closest("div.field-label-set");
		div.find("input[type=radio]").prop("checked", true);
		toggleEmailFields();
	}
	
	
	function showMoreEmails(event) {
		$(event.currentTarget).hide();
		self.editForm.find("div.email_field").show();
		self.editForm.find("span#address_text").show();
	}
	
	
	function validateForm() {

		// copy data to update form for ajax submission
		var type = self.editForm.find("input:checked[type=radio]").val();
		self.updateForm.find("input[type=radio][value=" + type + "]").prop("checked", true);
		
		if ( type == 'other' ) {
			// validate email addresses on form
			var fields = self.editForm.find("input.email");
			var count  = 0;
			fields.each(function(index, element) {
				var email = $(element).val();
				if ( isValidEmail(email) ) {
					count++;
				} else {
					email = '';
				}
				self.updateForm.find("input#MailForwarding_forwarding_address_" + index).val(email);
			});
			
			if ( count == 0 ) {
				alert("Please enter at least one valid email address");
				return;
			}
			
			// check if user is forwarding their mail to an equivalent domain
			var count = 0;
			fields.each(function(index, element) {
				var email = $(element).val();
				if ( inEquivalentDomain(email) ) {
					alert("Forwarding mail to " + email + " will create\na mail loop. Please try a different email address.");
					$(element).focus();
					count++;
				}
			});
			if ( count ) {
				return;
			}
		}
		
		freezeSubmitButton();
		
		// ajax submit
		$.post(self.updateForm.attr("action"), self.updateForm.serialize(), function(data) {
			if ( !data ) {
				alert("Error communicating with server");
			
			} else if ( data.result == 'ERROR' ) {
				alert(data.message);
				
			} else if ( data.result == 'OK' ) {
				$("div.body-content").html(data.content);
				
			} else {
				alert("Unrecognized response from server");
			}
			thawSubmitButton();
			
		}, 'json').fail(function() {
			thawSubmitButton();
			alert("Error communicating with server");
		});
	}

	
	function isValidEmail(email) {
		if ( email ) {
			return email.match(/^\s*(\w+)([\_\+\.\-]\w+)*\@(\w{2,}\.)+(\w{2,})\s*$/);
		} else {
			return false;
		}
	}
	
	/**
	 * Is an email in a domain we consider equivalent? Test for that
	 * to avoid mail loops.
	 */
	function inEquivalentDomain(email) {
		if ( email ) {
			var userDomain = email.match(/\@(.+)\s*$/);
			if ( userDomain[1] ) {
				for ( var index=0; index < self.equivDomains.length; index++ ) {
					if ( userDomain[1] == self.equivDomains[index] ) {
						return true;
					}
				}
			}
		}
		return false;
	}
	
	function freezeSubmitButton() {
		self.submitButton.prop("disabled", true);
		self.submitButton.text("Processing ").append( $("img#loader-img").clone().show() );
	}
	
	function thawSubmitButton() {
		self.submitButton.prop("disabled", false);
		self.submitButton.text("Update mail delivery options");
	}
	return setup;
})(jQuery);