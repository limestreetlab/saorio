//js to call php script for username availability check and then display the received output in form
function checkUsername() {
    var user = $("#user").val();
    
    if (user) {
        //call URL, data, callback, datatype
        $.post("ajax/signup_ajax.php", {username: user}, 
            //callback receiving a string true/false, then display availability message and change signup button disable attr
            function(availability){ 
                if (availability == "true") { //username available
                 var msg = "&#x2714; The username " + user + " is available."; 
                 $("#signupBtn").attr("disabled", false); //enable the submit button for signup
                }
                else {
                 var msg = "&#x2718; The username " + user + " is already taken.";
                 $("#signupBtn").attr("disabled", true); //disable the submit button for signup
                } 
                $("#availability").html(msg);
            }
        , "text");
    
    }
} 
