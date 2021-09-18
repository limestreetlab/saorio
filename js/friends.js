$("document").ready(function() {

  $(".unfriendBtn").on("click", unfriend);
  $(".addNotesBtn").on("click", addNotes);
  $(".followToggle").on("click", toggleFollowing);
  
});


/*
function to remove a friend
*/
function unfriend() {
  
  let unfriend = $(this).parents(".card").data("username");
  dataSend = {unfriend: unfriend};

  $.post("ajax/friends_ajax.php", dataSend, function(result) {
    result.success ? updateFriendship(unfriend, 1) : $("#toast-failure").toast('show');
  }, "json");
  
}

/*
function to add notes about a friend
*/
function addNotes() {
  
  var notesAbout = $(this).parents(".card").data("username");
  var form = $(this).parents(".buttons").siblings(".notes-edit-form");

  form.removeClass("d-none");

  $(".notesCancelBtn").off("click").on("click", function() {
    form.addClass("d-none");
  });

  $(".notesSaveBtn").off("click").on("click", function() {
    let notes = $(this).parent().siblings(".notes").val();
    dataSend = {notesAbout: notesAbout, notes: notes};

    $.post("ajax/friends_ajax.php", dataSend, function(result) {
      result.success ? updateFriendship(notesAbout, 2, notes) : $("#toast-failure").toast('show');
    }, "json");

    form.addClass("d-none");

  });
  
}

/*
function to toggle following a friend
*/
function toggleFollowing() {

  let follow = $(this).parents(".card").data("username");
  dataSend = {follow: follow};

  $.post("ajax/friends_ajax.php", dataSend, function(result) {
    result.success ? updateFriendship(follow, 3) : $("#toast-failure").toast('show');
  }, "json");

}

/*
1 for removing a friend's row after unfriending
2 for adding notes about a friend
3 for toggling following
*/
function updateFriendship(username, action, data=null) {

  let row = ".card[data-username='" + username + "']"; //selector

  switch(action) {

    case 1:
      $(row).remove(); 
      $("#numberOfFriends").text( $("#numberOfFriends").text() - 1 ); //decrement number of friends
      break;

    case 2:
      $(row).find(".notesArea").text(data);
      break;

    case 3:
      let following = $(row).find(".followToggle").text().toLowerCase();
      
      if (following == "follow") {
        $(row).find(".followToggle").text("Unfollow");
      } else {
        $(row).find(".followToggle").text("Follow");
      }
      break;

  }

}