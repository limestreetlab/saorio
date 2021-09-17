$("document").ready(function() {

  $(".unfriendBtn").on("click", unfriend);
  
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
1 for removing a friend's row after unfriending
*/
function updateFriendship(username, action) {

  switch(action) {

    case 1:
      let row = ".card[data-username='" + username + "']"; //selector
      $(row).remove(); 
      $("#numberOfFriends").text( $("#numberOfFriends").text() - 1 ); //decrement number of friends
      break;

  }

}