<!--HTML template for the header
menus shown differ dependent on logged in status-->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?php echo "$user in $appName" ?></title>

    <!--CDNs, Bootstrap, Bootstrap Icons, JQuery-->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-EVSTQN3/azprG1Anm3QDgpJLIm9Nao0Yz1ztcQTwFspd3yD65VohhpuuCOmLASjC" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css">

    <link rel="stylesheet" type="text/css" href=""> 
    <style>
        #landing-img {
          min-width: 80%;
          min-height: 300px;
          width: auto;
          height: auto;
          background-image: url("img/landing-hero.jpg");
          background-repeat: no-repeat;
          background-position: center;
          background-size: cover;  
        }
    </style>
</head> 

<body>
  
    <!--Bootstrap navbar from here-->
    <!--the navbar frame is unchanged, so is hardcoded; but items depend on log-in status, so are echoed by php-->
    <nav class='navbar navbar-expand-lg navbar-dark bg-primary'>
        <div class='container'>
        <a class='navbar-brand'>Saorio</a>
        <button class='navbar-toggler' type='button' data-bs-toggle='collapse' data-bs-target='#menus'>
        <span class='navbar-toggler-icon'></span>
        </button>
        <div class='collapse navbar-collapse' id='menus'>
        <ul class='navbar-nav'>

<?php //php to dynamically echo out each navbar menu (nav-item) dependent on login status 

ob_start(); //turn on the output buffer

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

        </ul>
        </div>
        </div>
    </nav><!--end of navbar-->


