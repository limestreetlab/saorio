<?php

//for account logging
$loginPasswordQuery = "SELECT password FROM members WHERE user = :user";
$loginQuery = "SELECT * FROM members WHERE user = :user";
$signupQuery = "INSERT INTO members (user, password, email) VALUES (:user, :password, :email)"; //create a new record of members
$initializeProfileQuery = "INSERT INTO profiles (user, firstname, lastname) VALUES (:user, :firstname, :lastname)"; //create a default profile


//for profile
$profileUpdateQuery = "UPDATE profiles SET about = :about, gender = :gender, ageGroup = :ageGroup, location = :location, job = :job, company = :company, major = :major, school = :school, interests = :interests, quote = :quote WHERE user = :user"; 
$pictureUpdateQuery = "UPDATE profiles SET profilePictureURL = :url, profilePictureMIME = :mime WHERE user = :user"; 
$getProfileQuery = "SELECT * FROM profiles WHERE user = :user";
$getNameAndPictureQuery = "SELECT firstname, lastname, profilePictureURL FROM profiles WHERE user = :user";


//for friends
$getAllFriendsQuery = "SELECT f.user, profiles.firstname, profiles.lastname, profiles.profilePictureURL FROM (SELECT user2 AS user FROM friends WHERE user1 = :user AND status = 'confirmed' UNION SELECT user1 AS user FROM friends WHERE user2 = :user AND status = 'confirmed') AS f INNER JOIN profiles ON f.user = profiles.user";
$checkIfFriendsQuery = "SELECT * FROM friends WHERE user1 = :a AND user2 = :b AND status = 'confirmed' UNION select * FROM friends WHERE user1 = :b AND user2 = :a AND status = 'confirmed'";
$confirmFriendRequestQuery = "UPDATE friends SET status = 'confirmed' WHERE user1 = :requestSender AND user2 = :requestRecipient";
$rejectFriendRequestQuery = "UPDATE friends SET status = 'rejected' WHERE user1 = :requestSender AND user2 = :requestRecipient";
$getFriendRequestQuery = "SELECT user1 FROM friends WHERE user2 = :requestRecipient AND status = 'request'";
$getMutualFriendsQuery = "";
$addAFriendQuery = "INSERT INTO friends (user1, user2, status) VALUES (:a, :b, 'requested')";
$removeAFriendQuery = "DELETE FROM friends WHERE (user1 = :a AND user2 = :b) OR (user1 = :b AND user2 = :a)";
$getAllMembersQuery = "SELECT members.user, profiles.firstname, profiles.lastname, profiles.profilePictureURL FROM members INNER JOIN profiles ON members.user = profiles.user";
$getFriendRequestQuery = "SELECT user1 AS requester FROM friends WHERE user2 = :user AND status = 'requested'";

//for messages
$getMyConversationWithQuery = "SELECT timestamp, sender, recipient, message FROM (SELECT * FROM messages WHERE sender = :me AND recipient = :who UNION SELECT * FROM messages WHERE sender = :who AND recipient = :me) AS conversation ORDER BY timestamp ASC";
$getPeopleIHaveConversationsWithQuery = "SELECT MAX(timestamp) AS lastTime, who FROM ( SELECT sender AS who, timestamp FROM messages WHERE recipient = :me UNION SELECT recipient AS who, timestamp FROM messages WHERE sender = :me) AS m GROUP BY who ORDER BY lastTime DESC";
$sendMessageQuery = "INSERT INTO messages VALUES (NULL, :time, :from, :to, :message)";
$getMyConversationWithSinceQuery = "SELECT timestamp, sender, recipient, message FROM (SELECT * FROM messages WHERE sender = :me AND recipient = :who AND timestamp > :since UNION SELECT * FROM messages WHERE sender = :who AND recipient = :me AND timestamp > :since) AS conversation ORDER BY timestamp ASC";



?>