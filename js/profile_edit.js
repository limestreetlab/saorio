$("document").ready( function() {

  //register click handlers for all .edit-inline elements
  for (let data of document.querySelectorAll(".edit-inline")) {
    data.addEventListener("click", editData);
  }
  //register click handlers for all .edit-inform elements
  for (let data of document.querySelectorAll(".edit-inform")) {
    data.addEventListener("click", showForm);
  }

  //register click handler to photo upload btn
  $("#photo-upload-btn").on("click", uploadPhoto);

});

/*
function for profile photo upload
*/
function uploadPhoto() {

  //imitate a click on file button to prompt for file selection
  $("#file-upload").trigger("click"); 
  //onchange (when a file is selected) handler, js-validate size and type, then submit to server
  $('#file-upload').off("change").change( function(event) { 
    //files property of the file input element gives access to the FileList array containing File obj
    const file = event.target.files[0]; //get 1st element as no multiple files allowed
    //read file meta
    const type = file.type ? file.type : "NA"; //mime type
    const size = file.size; //in bytes

    //validate type
    const mimeTypes = ["image/jpeg", "image/png", "image/gif", "image/svg+xml", "image/webp"]; //allowed mime
    if (!mimeTypes.includes(type)) {
      showToast("Unsupported file type", "The uploaded file type is not supported.");
      return; //exit function
    }

    //validate size
    const maxSize = 2500000; //set max size at 2.5mb
    if (size > maxSize) {
      showToast("File too large", "The uploaded file size is " + (size/1000000).toPrecision(3) + "MB, exceeding our limit of " + (maxSize/1000000).toPrecision(2) + "MB.");
      return; //exit function
    }
    
    let form = document.querySelector("#photo-upload-form");
    let data = new FormData(form);    
    data.set("photo", "");
    
    $.ajax({
      url: "ajax/profile_edit_ajax.php",
      method: "POST",
      data: data,
      processData: false,
      contentType: false,
      cache: false,
      dataType: "json",
      success: function(result){
        
        if(!result.success) { //upload failed

          let err = result.errors[0]; //read first element of the error array
          let title = "";
          let msg = "";
          if (err == 1) {
            title = "File too large";
            msg = "The uploaded file size is " + (size/1000000).toPrecision(3) + "MB, exceeding our limit of " + (maxSize/1000000).toPrecision(2) + "MB.";
          } else if (err == 2) {
            title = "Unsupported file type";
            msg = "The uploaded file type is not supported.";
          } else if (err == 3) {
            title = "Unknown orientation";
            msg = "The uploaded file has an unknown or unsupported orientation.";
          } else {
            title = "Update failure";
            msg = "An error occurred during the update. Sorry about that.";
          }
          showToast(title, msg); 

        } else { //upload succeeded
          
          let newPhoto = result.newData[0]; //new rel path
          $("#profile-picture").attr("src", newPhoto);

        }

      }//end ajax callback
    }); //end ajax

  }); //end onchange handler

} //end uploadPhoto function

/*
function for data to be edited inline
*/
function editData(event) {

  const btn = $(event.target);
  const editEl = "#" + btn.data("for"); //the data element ID
  const oldContents = $(editEl).val(); //original contents

  $(editEl).removeAttr("readonly").addClass("form-control-focus").focus(); //make data element editable and gain focus
  
  //save function to revert line back to original state and persist data
  let save = function(){

    $(editEl).attr("readonly", "").removeClass("form-control-focus"); //undo in-focus changes
    let newContents = $(editEl).val(); //present contents
    if (newContents != oldContents) { //when contents change
      
      let data = new Object();
      data[btn.data("for")] = newContents; //object storing changed data field
      //ajax-send data to backend for persistence
      $.post("ajax/profile_edit_ajax.php", data, function(result) {
        if (!result.success) {

          showToast("Update failure", "An error occurred during the update. Sorry about that."); //show the toast

        } 
      }, "json");

    }

  };
  
  $(editEl).off("blur").on("blur", save); //register one on-blur handler
  
} //end editData function

/*
function for data to be edited in a form
*/
function showForm(event) {
  //create variables
  const btn = $(event.target);
  const tagClicked = btn.prop("tagName").toLowerCase();
  const editFor = "#" + btn.data("for");
  const editForm = "#" + btn.data("form");
  const cancelBtn = editForm + "-cancel"; //cancel button has id = edit form name<-cancel>
  const saveBtn = editForm + "-save";
  
  //display edit form, hide the button and present data
  $(editForm).removeClass("d-none"); //show form
  $(editFor).addClass("d-none"); //hide all existing data 
  //the edit btn can be clicked on <span> or inner <i>, element to hide depends on which tag is clicked
  if (tagClicked == "i") { //<i> is clicked
    btn.parent().addClass("d-none"); //hide its parent which is <span>
  } else { //<span> is clicked
    btn.addClass("d-none"); //hide itself
  }
  
  //un-do everything when showing form
  let revert = function() {
    $(editForm).addClass("d-none"); //hide form
    $(editFor).removeClass("d-none"); //unhide data
    btn.parent().removeClass("d-none"); //unhide <span>
    btn.removeClass("d-none"); //unhide <i>
  };

  //helper to add conditional texts to a section such as 'add a job' when fields are all empty
  let addTextWhenEmpty = function() {
    let txt = "";
    switch(editFor) {

      case "#location":
        txt = "Add a location";
        break;
      case "#particulars":
        txt = "Add gender and birth date";
        break;
      case "#work":
        txt = "Add a job";
        break;
      case "#study":
        txt = "Add a school";
        break;
      default:
        txt = "";

    }

    $(editFor).find(".empty").text(txt);

  };

  //helper to add conditional texts to a section such as 'at' when fields are all full
  let addTextWhenFull = function() {
    let txt = "";
    switch(editFor) {

      case "#location":
        txt = ", ";
        break;
      case "#work":
        txt = " at ";
        break;
      case "#study":
        txt = " at ";
        break;
      default:
        txt = "";

    }

    $(editFor).find(".full").text(txt);

  };

  //helper to check if all values are empty
  let isEmpty = function(data) {
    let nonemptyValues = Object.values(data).filter( function(v) { return v && v.length > 0 ;} ); //filter out empty elements in the values of input param
    return (nonemptyValues.length == 0 ? true : false); 
  };

  //helper to check if all values are nonempty
  let isFull = function(data) {
    let nonemptyValues = Object.values(data).filter( function(v) { return v && v.length > 0 ;} ); //filter out empty elements in the values of input param
    return (Object.keys(data).length == nonemptyValues.length ? true : false);
  };
  
  //callback function for cancel btn
  let cancel = function() {
    revert();
  };

  /*
  key callback function to persist data when save btn clicked
  it retrieves data changed, send them to database, and present them in view
  */
  let save = function() {

    //create object to store changed data
    const inputs = $(editForm).find(".input"); //identify all .input within its target edit form and assign as jq objects
    let data = new Object(); //obj to store key value pairs
    
    inputs.each(function() { //for each jq's object

      let hyphenIndex = $(this).attr("id").indexOf("-"); //locate the index of hyphen in this's input id, which has form <fieldname>-input
      let field = $(this).attr("id").substring(0, hyphenIndex); //parse the part until hyphen as fieldname
      //most field data are as-is strings, but some like as date of birth need manipulation (from date string to epoch)
      if (field == "dob") { //date of birth field, convert present value from date string to [yyyy, mm, dd]

        let dateString = $(this).val().trim(); //mm/dd/yyyy string
        let dateObj = new Date(dateString); //JS Date obj
        let dob = dateString ? [dateObj.getFullYear(), dateObj.getMonth() + 1, dateObj.getDate()] : null; //[year, month, day] , JS month index from 0
        data[field] = dob;

      } else {

        data[field] = $(this).val().trim(); //assign value of this input to the field

      }

    }); //end each

    //apply these input values on profile, most fields are simple as-is texts without display logic (except .empty, .full)
    //gender and dob need special treatment, as the displays aren't equal to their input values, one is an icon and other is age
    for (const [field, value] of Object.entries(data)) {
      
      let id = "#" + field; //id tag of the field
      
      //display updated data, depending on field
      if (field == "gender") {//gender field, display icon based on input string value
        
        let genderIcon = "";
        switch(value) {
          //icons directly mirroring those in view
          case "male": 
            genderIcon = "<i class='bi bi-gender-male'></i>";
            break;
          case "female":
            genderIcon = "<i class='bi bi-gender-female'></i>";
            break;
          case "intersex":
            genderIcon = "<i class='bi bi-gender-ambiguous'></i>";
            break;
          default:

        }
        $(id).html(genderIcon);

      } else if (field == "dob") { //date of birth, calc and display age from input value
        
        let age;
        if (value) {
          let dob = new Date(value[0], value[1] - 1 , value[2]); //JS date obj month indexes from 0
          let today = new Date();
          let differenceInMilliSec = today.getTime() - dob.getTime(); //difference between two dates in milliseconds
          age = Math.floor( differenceInMilliSec / (1000*60*60*24*365) ); //calc age from milliseconds
        } else {
          age = null;
        }
        $("#age").text(age); //display age

      } else { //display values = input values
        
        $(id).text(value); //display input value in the field

      }

      $(id).siblings(".empty").text(""); //empty out any conditional .empty texts in the section
      $(id).siblings(".full").text(""); //empty out any conditional .full texts in the section

    } //end data for-loop
    
    //check condition and apply conditional text when met
    if (isEmpty(data)) {
      addTextWhenEmpty();
    }

    //check condition and apply conditional text when met
    if (isFull(data)) {
      addTextWhenFull();
    }
    
    //ajax-send data to backend for persistence 
    $.post("ajax/profile_edit_ajax.php", data, function(result) {
      if (!result.success) {
        showToast("Update failure", "An error occurred during the update. Sorry about that."); //show the toast
      }
    }, "json");


    revert();
    
  }; //end save function

  //register click handers for buttons
  $(cancelBtn).click(cancel);
  $(saveBtn).click(save);
  
} //end showForm function

/*
function to assign messages and pop up a Boostrap toast alert
*/
function showToast(title = "Oops!", message = "Sorry, something went wrong on our side. Please try again later.") {

  $("#toast-title").text(title); //assign title
  $("#toast-message").text(message); //assign message 
  $("#toast-failure").toast('show'); //show the hidden toast

}



