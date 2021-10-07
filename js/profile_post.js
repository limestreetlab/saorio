$("document").ready(function(){

  //set input text to focus on load
  let modal = document.getElementById('new-post-modal')
  let text = document.getElementById('new-post-text')
  modal.addEventListener('shown.bs.modal', function () {
    text.focus()
  });

  //disable/enable post submit btn on input fields change
  $("#post-content").on("input DOMSubtreeModified", enablePostBtn);
  $("#new-post-submit").on("click", post);

});

/*
function to send post data
*/
function post() {

  let text = $("#new-post-text").val().trim(); //get the text to send
  $("#new-post-text").val(''); //empty out the text input
  $("#new-post-modal").modal('hide'); //hide the modal

  let dataSend = {action: "send", text: text}; //data to send to backend

  $.post("ajax/posts_ajax.php", dataSend, function(data) {
    
    if(!data.success) {//post failed, error handling

      let errorCode = data.errors[0]; //array of errors from backend, error code defined in Post class
      switch (errorCode) {
        case -1:  //system error
          break;
        case 1: //input size over
          break;
      }

    } else { //post succeeded, ajax render the post

      let post = data.postView; //data received back from backend for rendering
      $(post).hide().prependTo("#posts").fadeIn(2000); //add the received render-ready post view

    }

  }, "json");


}

/*
function to enable and disable submit btn depending on input field emptiness
*/
function enablePostBtn() {

  if ($("#new-post-text").val().trim() != '' || $("#post-attachment").html() != '') { //if text field or attachment div isn't empty
    
    $("#new-post-submit").removeClass("disabled"); //enable btn
    
  } else {  
    
    $("#new-post-submit").addClass("disabled"); //disable btn
    
  }

};