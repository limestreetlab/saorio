<?php

//for members.php
//buttons representing existing relationship between $user and a particular member
//#0 for strangers, #1 for existing friends, #2 for requesting frienship, #3 for requested friendship, #4 for rejected
$stranger = "<a href='index.php?reqPage=members&addFriend=$mUser'><button type='button' class='mt-3 btn btn-primary btn-sm'>Add Friend</button></a>"; //no relationship yet, #0
$alreadyFriend = "<button type='button' class='mt-3 btn btn-outline-primary btn-sm' disabled>You're Friends</button>"; //existing friend relationship, #1
$requesting = "<button type='button' class='mt-3 btn btn-primary btn-sm disabled'>Friend Request Sent</button>"; //a friend request sent, #2
$requested = "<button type='button' class='mt-3 btn btn-primary btn-sm friendRequestConfirmationBtn' data-bs-toggle='modal' data-bs-target='#friendRequestConfirmationModal' data-user='$user' data-user-firstname='$firstname' data-request-from='$mUser' data-request-from-firstname='$mFirstname' data-request-from-lastname='$mLastname'>Friend Request Received</button>"; //a friend request received, #3
$rejected = "<button type='button' class='mt-3 btn btn-primary btn-sm' disabled>Rejected</button>"; //a friend request received but rejected, #4


?>