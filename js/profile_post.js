
//global variables
var files = [];

$("document").ready(function(){
  
  //initialization block, put input text to focus
  let modal = document.getElementById('new-post-modal')
  let text = document.getElementById('new-post-text')
  modal.addEventListener('shown.bs.modal', function () {
    text.focus()
  });

  //disable or enable post submit btn on input fields change
  $("#post-content").on("input DOMSubtreeModified", enablePostBtn);
  //send the form data to backend
  $("#new-post-submit").on("click", post);
  //upload photo and display img previews before submission  
  $("#new-post-photo").on("click", upload);
  //show a modal 
  $("#new-post-poll").on("click", () => { new bootstrap.Modal($("#feature-unavailable-modal")).show()} );
  //frontend pagination post load, click event delegated to parent of pagination section which can be dynamically created and erased 
  $("#main-menu").on("click", "#pagination .pagination .page-item .page-link", paginate);
  //users liking or disliking (voting) on a post
  $("#main-menu").on("click", ".vote-btn", postReact);

});

/*

*/
function postReact(event) {

  event.preventDefault(); 
  event.stopPropagation();
  
  let vote = $(this).hasClass("up-btn") ? 1 : -1; //specify if the vote is for up or down, each .vote-btn also has .up-btn or .down-btn
  let id = $(this).closest("[data-id]").data("id"); //get the data-id embedded in each post
  
  let dataSend = {id: id, vote: vote};

  $.get("ajax/posts_ajax.php", dataSend, function(result) {
    
    if (!result.success) {

      let reason = result.message;
      if (reason == 1) {
        showToast("Self-voting is disabled", "We do not let you vote on your own posts.");
      } else {
        showToast();
      }

    } else {
      //Jquery selector of the clicked post
      let postElement = $('.post[data-id=' + id + ']');
      //button icons used in frontend, direct mirroring
      let likeBtn = "<i class='bi bi-hand-thumbs-up'></i>";
      let likedBtn = "<i class='bi bi-hand-thumbs-up-fill text-primary'></i>";
      let dislikeBtn = "<i class='bi bi-hand-thumbs-down'></i>";
      let dislikedBtn = "<i class='bi bi-hand-thumbs-down-fill text-primary'></i>";
      //current like/dislike numbers
      let numberOfLiked = parseInt( postElement.find(".up-stat").text() ); //read the current liked number (casted to int)
      let numberOfDisliked = parseInt( postElement.find(".down-stat").text() ); //read the current disliked number (casted to int)
      
      //defined in like/dislike function, 2-7 for what liking/disliking took place, so can be reflected in frontend
      switch (result.message) {

        case 2: //liking a post
          postElement.find(".up-btn").empty().html(likedBtn); //change the btn from like to liked
          postElement.find(".up-stat").text(numberOfLiked + 1); //replace with incremented liked number
          break;

        case 3: //disliking a post
          postElement.find(".down-btn").empty().html(dislikedBtn); //change the btn from dislike to disliked
          postElement.find(".down-stat").text(numberOfDisliked + 1); //replace with incremented disliked number
          break;

        case 4: //unliking a liked post
          postElement.find(".up-btn").empty().html(likeBtn); //change the btn from liked to like
          postElement.find(".up-stat").text(numberOfLiked - 1); //replace with decremented liked number
          break;

        case 5: //undisliking a disliked post
          postElement.find(".down-btn").empty().html(dislikeBtn); //change the btn from disliked to dislike
          postElement.find(".down-stat").text(numberOfDisliked - 1); //replace with decremented disliked number
          break;

        case 6: //liking a disliked post
          postElement.find(".down-btn").empty().html(dislikeBtn); //change the btn from disliked to dislike
          postElement.find(".down-stat").text(numberOfDisliked - 1); //replace with decremented disliked number
          postElement.find(".up-btn").empty().html(likedBtn); //change the btn from like to liked
          postElement.find(".up-stat").text(numberOfLiked + 1); //replace with incremented liked number
          break;

        case 7: //disliking a liked post
          postElement.find(".up-btn").empty().html(likeBtn); //change the btn from liked to like
          postElement.find(".up-stat").text(numberOfLiked - 1); //replace with decremented liked number
          postElement.find(".down-btn").empty().html(dislikedBtn); //change the btn from dislike to disliked
          postElement.find(".down-stat").text(numberOfDisliked + 1); //replace with incremented disliked number
          break;

      }

    }

  } //close callback
  , "json");

}


/*
function for pagination, which loads post pages
*/
function paginate(event) {

  event.preventDefault(); //prevent defaulted backend pagination re-load, do it frontend instead
  event.stopPropagation();

  let page = $(this).text(); //page number
  let dataSend = {action: "pagination", page: page};

  $.get("ajax/posts_ajax.php", dataSend, function(data) {

    if(!data.success) {

      callbackError(data.errors);

    } else { //receiving a postView for posts and a paginationView for pagination

      $("#posts").empty(); //clear all existing posts
      $("#pagination").remove(); //deleting current pagination      
      $("#posts").prepend(data.postView); //add posts received
      $("#posts").after(data.paginationView); //add pagination received
      $("html, body").animate({scrollTop: 0}); //scroll to top

    }

  }, "json");

}

/*
function to send post data
*/
function post() {

  let text = $("#new-post-text").val().trim(); //get text data
  let img = $("#post-attachment img"); //get image data
  
  $("#new-post-modal").modal('hide'); //hide the modal
    
  //switch JSON data depending on input
  if (img.length == 0 && text.length > 0) { //text post
    
    //send data as an object when no files involved
    let dataSend = {action: "send", type: "text", text: text};
    
    //ajax call to send post data to backend
    $.post("ajax/posts_ajax.php", dataSend, function(data) {
      
      if(!data.success) {

        callbackError(data.errors);

      } else {

        callbackSuccess(data.postView);

      }

    }, "json");
  
  } else if (img.length > 0) { //image post
    
    var captions = []; //to store photo captions
    $(img).each( (index, el) => captions.push($(el).data("caption")) ); //retrieve captions embedded in data-caption 
    
    //send data using FormData when files are involved, where files are assigned to an array
    var formData = new FormData();
    formData.set("type", "image");
    formData.set("action", "send");
    formData.set("text", text);
    //can't directly add an array as value in FormData, must suffix varname with [] so PHP will see as array and pick up all values assigned to it instead of only last one (JS treats both x[] and x as strings)
    $.each(files.reverse(), (index, file) => formData.append("images[]", file) ); //files arr in first-to-last order but attached images (which contain captions) in last-to-first order due to prepending, reverse one to match the other
    $.each(captions, (index, caption) => formData.append("captions[]", caption) );    

    //ajax call to send post data to backend
    $.ajax({
      url: "ajax/posts_ajax.php",
      method: "POST",
      data: formData,
      processData: false,
      contentType: false,
      cache: false,
      dataType: "json",
      success: function(data) {
        
        if(!data.success) { //upload failed
          
          callbackError(data.errors);

        } else { //upload succeeded
          callbackSuccess(data.postView);
          //alert(JSON.stringify(data));
          
        }

      }//end callback
    }); //end ajax
    
  } 
  
  /*
  inner function to help display post view
  @param string view back backend return data
  */
  function callbackSuccess(post) {

    $(post).hide().prependTo("#posts").fadeIn(2000, "linear"); //add the received render-ready post view
    
    //when there is pagination (1+ pages), and page 1 not currently active, move back to the first page
    if ( $("#main-menu").children("#pagination") && !$("#main-menu").find("#pagination .page-item").eq(0).hasClass("active") ) {

      $(".pagination .page-item .page-link").eq(0).trigger("click"); //click on the first pagination item to go there

    }

  }

  /*inner function to help display errors
  @param array errors from backend return data
  */
  function callbackError(errors) {

    let err = errors[0]; //read first element of the error array

    let title = "";
    let msg = "";
    if (err == 2) {
      title = "File too large";
      msg = "Hey, the uploaded file is too big!";
    } else if (err == 3) {
      title = "Format file issue";
      msg = "Sorry, we do not support the uploaded file format.";
    } else if (err == 4) {
      title = "Too many files";
      msg = "Slow down, too many files were attempted per upload."; 
    } else { //-1 system err
      title = "Our fault";
      msg = "Opps. An error occurred on our side. Sorry about that.";
    }
    showToast(title, msg); 

  }

  //clear all inputs
  $("#new-post-text").val(""); //empty out text input
  hideAttachment(); //empty out any attachments

  
} //end post function

/*
function to upload a photo
*/
function upload() {
  
  //check for upload limit
  const MAX_IMAGES = 5;
  if ($("#post-attachment img").length >= MAX_IMAGES) {
    showToast("Too many uploads", "Slow down buddy, please keep it up to " + MAX_IMAGES + " images per post."); //show error
    return;
  }
  //open the file upload window
  $("#photo-upload").trigger("click");
  $("#photo-upload").off("change").on("change", function(event){
    
    const file = event.target.files[0]; //get file obj, 1st element only as multiple no allowed
    files.push(file);
    if (!checkPhoto(file)) {
      return;
    }
  
    //display attachment
    let added = showAttachment(file);
    added.then(manageAttachment);
    //attachment cancel btn click handler
    $("#upload-cancel-btn-container").on("click", hideAttachment);

  }); //end change handler

} //end function

function hideAttachment() {
  
  $("#post-attachment img").each( () => {URL.revokeObjectURL($(this).attr("src"));}); //revoke url
  $("#post-attachment").addClass("d-none").children("img").remove(); //clear all images and re-hide attachment area
  $(".modal-body button.disabled").removeClass("disabled"); //clear any disabled buttons
  files = []; //clear the files array variable

}

/*
function to display the attachment
*/
function showAttachment(file) {
  
  let src = URL.createObjectURL(file); //create a temporary img src
  var img = document.createElement('img'); //create a img element
  $("#post-attachment").prepend(img).removeClass("d-none");  
  img.src = src; //set temp src to the new img element
  
  //use promise because onload is asynchronous and next operation needs img to be completely loaded
  let promise = new Promise(function(resolve, reject) {
     img.onload = function() {
       if (img.width >= img.height) {
         $(img).addClass("landscape");
       } else {
         $(img).addClass("portrait");
       } 
       resolve();
     };
  });
  
  return promise;
  
}

/*
function to organize attachments in the attachment div
*/
function manageAttachment() { 
 
 //disable other attachment buttons
 if ($("#post-attachment img").length > 0){
   $("#new-post-link button, #new-post-poll button").addClass("disabled");
 } 
 
 //store img orientations in array
 let orientation = [];
 $("#post-attachment img").each(function() {
  $(this).hasClass("portrait") ? orientation.push("portrait") : orientation.push("landscape"); 
 });
 
 //get number of images, how many are portrait vs landscape
 let numberOfImg = $("#post-attachment img").length;
 let numberOfPortrait = 0;
 let numberOfLandscape = 0;
 orientation.forEach(function(img) {
   img == "portrait" ? numberOfPortrait++ : numberOfLandscape++;
 });
 
 //array to assign style classes to each img, from most recently added to oldest
 let config = [];
 switch (numberOfImg) {
     
   case 1:
     numberOfPortrait > 0 ? config.push("portrait-1-in-1-portrait") : config.push("landscape-1-in-1-landscape");
     break;
     
   case 2:
     if (numberOfPortrait == 2) { //both portraits
      config.push("portrait-1-in-2-portrait", "portrait-2-in-2-portrait");
     } else if (numberOfLandscape == 2) { //both landscape
      config.push("landscape-1-in-2-landscape", "landscape-2-in-2-landscape");
     } else { //1 landscape, 2 portrait
      config.push("landscape-1-in-2-mixed", "landscape-2-in-2-mixed"); 
     }
     break;
     
   case 3:
    if (orientation[0] == "portrait") { //most recent image is a portrait 
      config.push("portrait-1-in-3-portrait", "portrait-2-in-3-portrait", "portrait-3-in-3-portrait");
     } else {
      config.push("landscape-1-in-3-landscape", "landscape-2-in-3-landscape", "landscape-3-in-3-landscape");
     }
     break;

   case 4:
    if (orientation[0] == "portrait") { //most recent image is a portrait
      config.push("portrait-1-in-4-portrait", "portrait-2-in-4-portrait", "portrait-3-in-4-portrait", "portrait-4-in-4-portrait");
    } else {
      config.push("landscape-1-in-4-landscape", "landscape-2-in-4-landscape", "landscape-3-in-4-landscape", "landscape-4-in-4-landscape");
    } 
    break;

   case 5:
     if (orientation[0] == "portrait") { //most recent image is a portrait
       config.push("portrait-1-in-5-portrait", "portrait-2-in-5-portrait", "portrait-3-in-5-portrait", "portrait-4-in-5-portrait", "portrait-5-in-5-portrait");
     } else {
       config.push("landscape-1-in-5-landscape", "landscape-2-in-5-landscape", "landscape-3-in-5-landscape", "landscape-4-in-5-landscape", "landscape-5-in-5-landscape");
     }
     
 } 
 
 //add the declared css classes to each img
 $("#post-attachment img").each( function(){
   let cls = config.shift(); //get the first img configuration element off array
   $(this).addClass(cls); //assign config class
   //add BS tooltip and a data-item to newly added img, the tooltip to display caption and data-item to store the caption
   if ($(this).data("caption") == undefined || $(this).data("caption") == false) { //no data-item caption yet, so a new img
    $(this).attr({"data-bs-toggle": "tooltip", "title": "click to add a caption"}).data("caption", ""); //add BS tooltip to img
    new bootstrap.Tooltip($(this)); //initialize the tooltip
   }
 });

$("#post-attachment img").off("click").on("click", addCaption); //add a click handler for adding photo captions

}//end function

/*
function for adding photo captions
*/
function addCaption(clickEvt) {
  
 let index = Array.from(document.querySelectorAll('#post-attachment img')).indexOf(clickEvt.target); //index of img clicked on
  
 let imgEl = $("#post-attachment img").eq(index); //get the img element clicked on
 let tooltip = bootstrap.Tooltip.getInstance(imgEl); //get the BS tooltip instance of this img element
 tooltip.hide(); //hide tooltip
 let captionModal = new bootstrap.Modal(document.getElementById('photo-caption-modal'));
 captionModal.show();
  
 $("#photo-caption-save-btn").off("click").on("click", function() {
   let caption = $("#photo-caption").val().trim(); //get caption value from input
   caption = caption != "" ? caption : "click to add a caption"; //set caption to itself or some default string if empty
   $(imgEl).attr("title", caption).data("caption", caption); //change the tooltip title and data-item to entered caption 
   tooltip.dispose(); //caption entered, destroy tooltip for a new one having a new title
   new bootstrap.Tooltip($(imgEl)).show(); //initialize this updated tooltip
 });
  
  $("#photo-caption").val(""); //reset the input area
  
}

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

  if ($("#new-post-text").val().trim() != '' || $("#post-attachment img").length > 0) { //if text field or attachment div isn't empty
    
    $("#new-post-submit").removeClass("disabled"); //enable btn
    
  } else {  
    
    $("#new-post-submit").addClass("disabled"); //disable btn
    
  }

};
