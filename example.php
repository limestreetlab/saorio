<?php

require_once "./includes/ini.php";

$pm = new PostManager('tony1');
$posts = $pm->getPage(1);
print_r($posts);