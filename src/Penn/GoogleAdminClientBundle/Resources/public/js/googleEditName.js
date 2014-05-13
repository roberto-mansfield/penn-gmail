$(function() {
	$("input#updateNameButton").on("click", validateName);
});


function validateName() {

	var form      = $("form#updateName");
    var firstName = form.find("input.firstNameField").val();
    var lastName  = form.find("input.lastNameField").val();
    var button    = form.find("input#updateNameButton");
	
	if ( !firstName ) {
		alert("Please enter a first name.");
		return;
	}

    var invalidChars = /[\<\>\"\&\%]/;

    if ( invalidChars.test(firstName) ) {
        alert("Your first name contains invalid characters.");
        return;
    }
	
	if ( !lastName ) {
		alert("Please enter a last name.");
		return;
	}

    if ( invalidChars.test(lastName) ) {
        alert("Your last name contains invalid characters.");
        return;
    }
	
    button.val("Updating...").prop('disabled', true);
    
	form.submit();
}
