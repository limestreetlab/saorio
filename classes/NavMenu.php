<?php

/*
class to create a Boostrap-based navmenu
this class directly contains HTML codes 
*/
class NavMenu {

  private $loggedInMenu = ["Home" => "home", "Profile" => "profile", "Posts" => "posts", "Messages" => "messages", "Friends" => "friends", "Members" => "members", "Log Out" => "logout"];
  private $unLoggedInMenu = ["Home" => null, "Log In" => "login", "Sign Up" => "signup"]; 
  private $icons = [null => "bi bi-house", "login" => "bi bi-box-arrow-in-right", "signup" => "bi bi-door-open", "home" => "bi bi-house-door", "posts" => "bi bi-chat-text", "members" => "bi bi-list-task", "friends" => "bi bi-people", "messages" => "bi-chat-dots", "profile" => "bi bi-person", "logout" => "bi bi-box-arrow-right"];
  private $html;

  
  //constructor
  public function __construct() {
  }

  /*
  instance method to get the nav-menu (comprises of nav-items) html codes
  */
  public function getNavMenu(bool $logged): string {

      $menu = $logged ? $this->loggedInMenu : $this->unLoggedInMenu;

      foreach ($menu as $title => $page) {

          //asign .active to requested page
          $active = ""; //default empty
          global $reqPage; //a var alive in index.php
          if ($reqPage == $page) { //if requested page equal this page
              $active = "active"; //mark it active
          }

          //assign icons for this requested page
          $icon = $this->icons[$page];

          //appending this nav-item html to the menu
          $this->html .= "<li class='nav-item'>  
                          <a class='nav-link $active' href='index.php?reqPage=$page'><i class='$icon'></i> $title</a>
                          </li>  ";

      }

      return $this->html;

  }


}

?>