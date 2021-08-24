<?php //php to dynamically echo out each navbar menu (nav-item) dependent on login status 

//array to contain nav-items for logged-in / unlogged-in users, keys/values are navbar title and php requested pagename
$loggedInMenu = ["Home" => "feeds", "Profile" => "profile", "Posts" => "posts", "Messages" => "messages", "Friends" => "friends", "Members" => "members", "Log Out" => "logout"];
$unloggedInMenu = ["Home" => null, "Log In" => "login", "Sign Up" => "signup"]; 

//array to map requested pagename with BS icons
$icons = [null => "bi bi-house", "login" => "bi bi-box-arrow-in-right", "signup" => "bi bi-door-open", "feeds" => "bi bi-house-door", "posts" => "bi bi-chat-text", "members" => "bi bi-list-task", "friends" => "bi bi-people", "messages" => "bi-chat-dots", "profile" => "bi bi-person", "logout" => "bi bi-box-arrow-right"];

//produce either Logged-in menus or Unlogged-in menus
if ($isLoggedIn) { 
    produceNavMenu($loggedInMenu);
} else { 
    produceNavMenu($unloggedInMenu);
}

//helper function to echo out the nav-menu by nav-items one by one
//@param $menu must be associative array having Title => reqPage
//@return void
function produceNavMenu(array $menu) {
    
    foreach($menu as $title => $page) {

        //asign .active to requested page
        $active = ""; //default empty
        global $reqPage; //a var in index.php
        if ($reqPage == $page) { //if requested page equal this page
            $active = "active"; //mark it active
        }

        //assign icons for this requested page
        global $icons;
        $icon = $icons[$page];

        //producing the nav-item
        echo "<li class='nav-item'>  
            <a class='nav-link $active' href='index.php?reqPage=$page'><i class='$icon'></i> $title</a>
            </li>";
    }
}

?>



