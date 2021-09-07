$("document").ready( function() {

  //register click handlers for all .edit-inline elements
  for (const child of document.querySelectorAll(".edit-inline")) {
    child.onclick = editData;
  }
  //register click handlers for all .edit-inform elements
  for (const child of document.querySelectorAll(".edit-inform")) {
    child.onclick = showForm;
  }

});

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
      
      let data = {};
      data[btn.data("for")] = newContents; //object storing changed data field
      //ajax-send data to backend for persistence
      $.post("ajax/profile_edit_ajax.php", data, function(result) {
        if (!result.success) {

          $(".toast").show(); //show the toast

        } 
      }, "json");

    }

  };
  
  $(editEl).off("blur"); //unregister any old blur handlers
  $(editEl).blur(save); //register save handler on blue
  
} //end function

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
    let data = new Object; //obj to store key value pairs
    inputs.each(function() { //for each jq's object
      let hyphenIndex = $(this).attr("id").indexOf("-"); //locate the index of hyphen in this's input id, which has form <fieldname>-input
      let field = $(this).attr("id").substring(0, hyphenIndex); //parse the part until hyphen as fieldname
      //most field data are as-is strings, but some like as date of birth need manipulation (from date string to epoch)
      if (field == "dob") { //date of birth field, special treatment
        let dateString = $(this).val().trim(); //mm/dd/yyyy string
        let dateObj = new Date(dateString); //JS Date obj
        let dob = [dateObj.getFullYear(), dateObj.getMonth() + 1 ,dateObj.getDate()]; //[year, month, day] , JS month index from 0
        date[field] = dob;
      } else {
        data[field] = $(this).val().trim(); //assign value of this input to the field
      }
    });

    //check if values are all empty, if so exit
    //let inputLengths = Object.values(data).map(function(v){return v.length;}); //array of each input's length
    //let lengthSum = inputLengths.reduce(function(a, b) {return a + b;}); //sum of each input's length, if 0 implies all inputs are empty
    //nonempty values
    //if (lengthSum) {

      //apply these input values on profile
      for (const [field, value] of Object.entries(data)) {

        let id = "#" + field; //id tag of the field
        $(id).text(value); //display new values in the field
        $(id).siblings(".empty").text(""); //empty out any .empty texts in the section

      }
      //ajax-send data to backend for persistence 
      $.post("ajax/profile_edit_ajax.php", data, function(result) {
        if (!result.success) {

          $(".toast").show(); //show the toast

        }
      }, "json");

    //}

    revert();
    
  }; //end save function

  //register click handers for buttons
  $(cancelBtn).click(cancel);
  $(saveBtn).click(save);
  
} //end showForm function


/*
datepicker settings
*/
$("document").ready( function() {
  var now = new Date();
  var lastYear = now.getFullYear() - 1; //a year ago
  var genesisYear = lastYear - 60; //when the world begins
      
  const picker = new tempusDominus.TempusDominus(document.querySelector('#datepicker'), {
    display: {
      viewMode: 'years',
      components: {
        decades: false,
        year: true,
        month: true,
        date: true,
        hours: false,
        minutes: false,
        seconds: false,
      }
    }, //end display options
    restrictions: {
      minDate: new Date(genesisYear, 0, 1),
      maxDate: new Date(lastYear, 11, 31), 
    }, //end restrictions options
    useCurrent: false,  
    //defaultDate: new Date("2019-12-31"),
  });

});


/*
Codes for the Boostrap Toast element for error message
*/
$("#toast-close").click(function(){
  $(".toast").hide()
});


