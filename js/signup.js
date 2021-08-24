$("document").ready( function() {

    $("#user").on("blur", checkUsername);
    $("#passwordRepeat").on("blur", checkPasswordMatch);
    $("#email").on("blur", checkEmail);
});  



//function to call php script for username availability check and then display the received output in form
function checkUsername() {

    var user = $("#user").val();
    
    if (user) {
        //call URL, data, callback, datatype
        $.post("ajax/signup_ajax.php", {username: user}, 
            //callback receiving a string true/false, then display availability message and change signup button disable attr
            function(result){ 
                if (result.availability == true) { //username available
                 var msg = "&#x2714; The username " + user + " is available."; 
                 $("#signupBtn").attr("disabled", false); //enable the submit button for signup
                }
                else {
                 var msg = "&#x2718; The username " + user + " is already taken.";
                 $("#signupBtn").attr("disabled", true); //disable the submit button for signup
                } 
                
                $("#availability").html(msg);
            }
        , "json");
    
    }
} 

//function to check if entered password and confirmed password match
function checkPasswordMatch() {

    var password = $("#password").val();
    var passwordRepeat = $("#passwordRepeat").val();

    if (password.length > 0 && passwordRepeat.length > 0) { //password fields are entered

        if (password != passwordRepeat) { //don't match
            var msg = "&#x2718; The passwords don't match. ";
            $("#passwordMatched").html(msg); //display the message
            $("#signupBtn").attr("disabled", true); //disable the submit button for signup
        } else { //match
            $("#passwordMatched").html(""); //clear message
            $("#signupBtn").attr("disabled", false); //enable the submit button for signup
        }

    }

}

//function to check if email already exists
function checkEmail() {

    var email = $("#email").val();

    if (email) {
        //call URL, data, callback, datatype
        $.post("ajax/signup_ajax.php", {email: email}, 
            //callback receiving a string true/false, then display availability message and change signup button disable attr
            function(result){ 
                
                if (result.emailExists == true) { //email already exists
                    var msg = "&#x2718; This email is already used."; 
                    $("#signupBtn").attr("disabled", true); //enable the submit button for signup
                    $("#emailValidation").html(msg);
                } else { //email doesn't already exist
                    $("#emailValidation").html(""); //clear message
                    $("#signupBtn").attr("disabled", false); //enable the submit button for signup
                }
            }
        , "json");

    }
}
