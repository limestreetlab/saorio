<?php

require_once "./includes/ini.php"; //SITE_ROOT, INCLUDE_DIR are defined in config.php and imported through ini.php, so cannot be used before including ini.php

$id = 'tony11636556559';
$post = new PostOfImage(null, $id);

$params = [[2,"new hellaaao la"]];
$post->update(1, $params);
echo "done";