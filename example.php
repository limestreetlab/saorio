<?php
//landing page for the app, responsible for loading requested pages

require_once "./includes/ini.php";

session_start();

$post = new PostOfImage(null, "tony11633190390");
$post->update(1,[[0,"guck off"]]);
$post->delete();


?>

<form method="post" action="example.php" enctype="multipart/form-data">
<div>
  <label for="file">Choose file to upload</label>
  <input type="file" id="file" name="file" accept='.jpg, .jpeg, .png' multiple>
</div>
<div>
  <button>Submit</button>
</div>
</form>