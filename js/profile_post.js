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
  $("#new-post-photo").on("click", upload);

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
function to upload a photo
*/
function upload() {

  $("#photo-upload").trigger("click");
  $("#photo-upload").off("change").on("change", function(event){
    
    const file = event.target.files[0]; //get file obj, 1st element only as multiple no allowed
    if (!checkPhoto(file)) {
      return;
    }

    let form = document.querySelector("#photo-upload-form"); //select the form element
    let data = new FormData(form); //into a JS Form object
    data.set("photo", ""); //put a key in the request so it can be identified in the handling script as the form doesn't have any keys (only file input)
    

  }); //end change handler

} //end function

/*
function to check upload photo file on type and size
@return boolean indicating checkek result
*/
function checkPhoto(file) {

  const type = file.type ? file.type : "NA"; //mime type
  const size = file.size; //in bytes

  //validate type
  const mimeTypes = ["image/jpeg", "image/png", "image/gif", "image/svg+xml", "image/webp"]; //allowed mime
  if (!mimeTypes.includes(type)) {
    showToast("Unsupported file type", "Hey, the uploaded file type " + type + " is not supported."); //show error
    $("#new-post-modal").modal('hide'); //hide the modal
    return false;
  }
  
  //validate size
  const maxSize = 2500000; //set max size at 2.5mb
  if (size > maxSize) {
    showToast("File too large", "Hey, the uploaded file size is " + (size/1000000).toPrecision(3) + "MB, exceeding our limit of " + (maxSize/1000000).toPrecision(2) + "MB.");
    
    return false; 
  }

  return true;

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