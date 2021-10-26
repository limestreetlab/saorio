<?php
//used to aggregate data across types of posts and handle post liking/disliking and commenting
class PostManager {

  protected $mysql;
  protected $user; //user of the post
  protected $numberOfPosts; //total number of posts by user
  protected $numberOfPages; //number of pages 
  protected const POSTS_PER_PAGE = 5; //number of posts to show per page in pagination
  protected const PAGES_TO_SHOW = 7; //total number of explicit pages to display in pagination, 2 for start and end pages, x for left and right pages (wings) from active page, oldd number for balanced wings


  public function __construct(string $user) {

    $this->mysql = MySQL::getInstance(); //database accessor instance

    //check username entered exists
    $usernameExists = $this->mysql->request($this->mysql->readMembersTableQuery, [":user" => $user]);
    if (!$usernameExists) {
      throw new Exception("username is invalid");
    } else {
      $this->user = $user;
    }

    $this->numberOfPosts = $this->mysql->request($this->mysql->readPostNumberQuery, [":user" => $this->user])[0]["number"];
    $this->numberOfPages = MAX(ceil( $this->numberOfPosts / self::POSTS_PER_PAGE ), 1); //total post number divided by posts per page, rounded up

  }

  /*
  get posts created by user, from most recent
  @param int number, number of posts to retrieve
  @param int skip, number of posts to skip from 1 being most recent 
  @return array of post data [id, type, timestamp, text, [image rel paths], [image descriptions]]
  */
  protected function getPosts(int $number = null, int $skip = null): ?array {

    //switch SQL query on input params
    if ( !is_null($skip) && !is_null($number) ) { //from a given number for a certain number of posts

      $posts = $this->mysql->request($this->mysql->readPostsQuery, [":user" => $this->user, ":offset" => $skip, ":count" => $number]);

    } elseif ( !is_null($skip) && is_null($number)  ) { //from a given number til the end

      $posts = $this->mysql->request($this->mysql->readPostsQuery, [":user" => $this->user, ":offset" => $skip, ":count" => 99]);

    } elseif ( is_null($skip) && !is_null($number) ) { //for a certain number of posts from most recent
      
      $posts = $this->mysql->request($this->mysql->readPostsQuery, [":user" => $this->user, ":offset" => 0, ":count" => $number]);
      
    } else { //get all posts

      $posts = $this->mysql->request($this->mysql->readPostsQuery, [":user" => $this->user, ":offset" => 0, ":count" => 99]);

    }

    $data = [];
    foreach ($posts as $post) {

      $type = $post["type"];
      $id = $post["id"];

      if ($type == 1) {

        $textPost = new PostOfText(null, $id);
        extract( $textPost->getData() );
        $haveAlreadyLiked = $textPost->haveAlreadyLiked($_SESSION["user"]);
        $haveAlreadyDisliked = $textPost->haveAlreadyDisliked($_SESSION["user"]);
        array_push($data, [ "id" => $id, "type" => $type, "timestamp" => $timestamp, "text" => $content, "images" => null, "descriptions" => null, "likes" => $likes, "dislikes" => $dislikes, "haveAlreadyLiked" => $haveAlreadyLiked, "haveAlreadyDisliked" => $haveAlreadyDisliked, "comments" => $comments]);

      } elseif ($type == 2) {

        $imagePost = new PostOfImage(null, $id); 
        extract( $imagePost->getData() );
        $haveAlreadyLiked = $imagePost->haveAlreadyLiked($_SESSION["user"]);
        $haveAlreadyDisliked = $imagePost->haveAlreadyDisliked($_SESSION["user"]);
        $images = []; //rel path
        $descriptions = []; //caption
        foreach ($content as $row) {
          array_push($images, ($row[0])->getFileRelativePath());
          array_push($descriptions, $row[1]);
        }
        array_push($data, [ "id" => $id, "type" => $type, "timestamp" => $timestamp, "text" => $text->getContent(), "images" => $images, "descriptions" => $descriptions, "likes" => $likes, "dislikes" => $dislikes, "haveAlreadyLiked" => $haveAlreadyLiked, "haveAlreadyDisliked" => $haveAlreadyDisliked, "comments" => $comments ]);

      } else {

        throw new Exception("invalid post type code.");

      }

    }

    return $data;

  }

  /*
  get post data for a certain paginated page
  @param page, pagination number
  @return array of post data [id, type, timestamp, text, [image rel paths], [image descriptions]]
  */
  public function getPage(int $page): ?array {

    if ($page < 1 || $page > $this->numberOfPages) {
      throw new Exception("input page is beyond available pagination.");
    }

    $skip = ($page - 1) * self::POSTS_PER_PAGE; //number of posts to skip before retrieving
    return $this->getPosts(self::POSTS_PER_PAGE, $skip); 

  }
  
  /*
  get image posts created by user
  @param int number, number of posts to retrieve
  @param int skip, number of posts to skip from 1 being most recent
  @return array whose elements are arrays of post data [id, timestamp, text, image rel path, image description]
  */
  public function getImagePosts(int $number = null, int $skip = null): ?array {

    //switch SQL query on input params
    if ( !is_null($skip) && !is_null($number) ) { //from a given number for a certain number of posts

      $rows = $this->mysql->request($this->mysql->readImagePostsQuery, [":user" => $this->user, ":offset" => $skip, ":count" => $number]);

    } elseif ( !is_null($skip) && is_null($number)  ) { //from a given number til the end

      $rows = $this->mysql->request($this->mysql->readImagePostsQuery, [":user" => $this->user, ":offset" => $skip, ":count" => 99]);

    } elseif ( is_null($skip) && !is_null($number) ) { //for a certain number of posts from most recent
      
      $rows = $this->mysql->request($this->mysql->readImagePostsQuery, [":user" => $this->user, ":offset" => 0, ":count" => $number]);
      
    } else { //get all posts

      $rows = $this->mysql->request($this->mysql->readImagePostsQuery, [":user" => $this->user, ":offset" => 0, ":count" => 99]);

    }

    $data = [];
    foreach ($rows as $row) {

      $relPath = UploadedPostImageFile::convertFileRelativePath($row["image"]); //abs to rel path
      array_push( $data, array_replace($row, ["image" => $relPath]) ); //append array after replacing abs path to rel path

    } 

    return $data;

  }

  /*
  get text posts created by user
  @param int number, number of posts to retrieve
  @param int skip, number of posts to skip from 1 being most recent
  @return array whose elements are arrays of post data [id, timestamp, post]
  */
  public function getTextPosts(int $number = null, int $skip = null): ?array {

    //switch SQL query on input params
    if ( !is_null($skip) && !is_null($number) ) { //from a given number for a certain number of posts

      return $this->mysql->request($this->mysql->readTextPostsQuery, [":user" => $this->user, ":offset" => $skip, ":count" => $number]);

    } elseif ( !is_null($skip) && is_null($number)  ) { //from a given number til the end

      return $this->mysql->request($this->mysql->readTextPostsQuery, [":user" => $this->user, ":offset" => $skip, ":count" => 99]);

    } elseif ( is_null($skip) && !is_null($number) ) { //for a certain number of posts from most recent
      
      return $this->mysql->request($this->mysql->readTextPostsQuery, [":user" => $this->user, ":offset" => 0, ":count" => $number]);
      
    } else { //get all posts

      return $this->mysql->request($this->mysql->readTextPostsQuery, [":user" => $this->user, ":offset" => 0, ":count" => 99]);

    }

  }

  /*
  get a number of images posted by user
  @param number of images to get
  @return relative paths of images, most recent first (timestamp desending order)
  */
  public function getPostedImages(int $number = null): ?array {

    $number = is_null($number) ? 99 : $number; //set number to all if isn't already set
    $id_rows = $this->mysql->request($this->mysql->readImagePostIdQuery, [":user" => $this->user, ":count" => $number]);
    
    $paths = [];
    foreach($id_rows as $id_row) {

      $img_rows = $this->mysql->request($this->mysql->readImagePostImageQuery, [":id" => $id_row[0]]);

      foreach($img_rows as $img_row) {
        array_push($paths, UploadedPostImageFile::convertFileRelativePath( $img_row["image"] ) );
      }

    }

    return $paths;

  }

  /*
  get the number of posts made by user
  @return number of posts
  */
  public function getNumberOfPosts(): int {

    return $this->numberOfPosts;

  }

  /*
  function to create a range of page numbers for pagination use
  @param int activePage, a number indicating which page is currently active
  @return array of paginated numbers or null if there is only 1 page (so none needed)
  */
  public function paginate(int $activePage = 1): ?array {

    $totalPages = max(ceil( $this->numberOfPosts / self::POSTS_PER_PAGE ), 1); //make it 1 when psot numebr zero

    if ($totalPages <= SELF::PAGES_TO_SHOW) { //if available pages are less than total pages to show, just show all

      $pagination = range(1, $totalPages); //

    } else { //there are more available pages than pages to show, truncate them

      $pageRangeSize = self::PAGES_TO_SHOW - 2; //minus 2 for the start and end pages
      $pageWingSize = ($pageRangeSize - 1) / 2; //number of pages to show next to active page
      $startActivePageBound = 1 + $pageRangeSize - 2; //if active page below this number, the pagination range contains or right next to page 1, else ellipsis used between 1
      $endActivePageBound = $totalPages - $pageRangeSize + 2; //if active page above this number, the pagination range contains or right next to max page, else ellipsis used between max page
      $ellipsis = ["..."]; 
      $ellipsis2 = ["......"];
      $midpoint = ceil($totalPages / 2);

      if ($activePage <= $startActivePageBound) {

        $pages = range($startActivePageBound - $pageWingSize, $startActivePageBound + $pageWingSize); 
        $startEllipsis = []; //ellipsis to the left of pagination
        $endEllipsis = $ellipsis2; //ellipsis to the right of pagination

      } elseif ($activePage >= $endActivePageBound) {
        
        $pages = range($endActivePageBound - $pageWingSize, $endActivePageBound + $pageWingSize);
        $startEllipsis = $ellipsis2;
        $endEllipsis = []; 

      } else {

        $pages = range($activePage - $pageWingSize, $activePage + $pageWingSize);
        
        if ($activePage < $midpoint) {

          $startEllipsis = $ellipsis;
          $endEllipsis = $ellipsis2;

        } elseif ($activePage > $midpoint) {

          $startEllipsis = $ellipsis2;
          $endEllipsis = $ellipsis;

        } else {

          $startEllipsis = $ellipsis;
          $endEllipsis = $ellipsis;

        }

      }

      $pagination = array_merge( [1], $startEllipsis, $pages, $endEllipsis, [$totalPages] );

    }

    return $totalPages > 1 ? $pagination: null;

  }

  /*
  get the number of image posts made by user
  @return number of images posts
  */
  public function getNumberOfImagePosts(): int {

    return $this->mysql->request($this->mysql->readImagePostNumberQuery, [":user" => $this->user]);

  }

  /*
  get number of images posted by user
  @return number of images
  */
  public function getNumberOfPostedImages(): int {

    return $this->mysql->request($this->mysql->readImagesNumber, [":user" => $this->user]);

  }

  /*
  function to record voting (liking/disliking) of a post
  @param id, the id of the post to cast a vote 
  @vote, whether the vote is up or down, used position int for up, negative for down
  @return array, [bool success, int message] where message is a number indicating why vote not recorded or what vote actually took place
    -1 system err, 1 user on own post, 2 like, 3 dislike, 4 unlike, 5 undislike, 6 undislike then like, 7 unlike then dislike
  */
  public function vote(string $id, int $vote): array {
    
    $posting = $this->mysql->request($this->mysql->readPostQuery, [":id" => $id]);
    $poster = $posting[0]["user"];
    $type = $posting[0]["type"];
    switch ($type) {
      case 1: 
        $post = new PostOfText(null, $id);
        break;
      case 2:
        $post = new PostOfImage(null, $id);
        break;
    }
    
    if (!$posting) {

      throw new Exception("the provided post id " . $id . "cannot be found.");

    } elseif ($this->user == $poster) { //disabling user from voting on own posts

      return [false, 1];

    } else {
      
      try { 

        $liked = $post->haveAlreadyLiked($this->user);
        $disliked = $post->haveAlreadyDisliked($this->user);

        if ($vote > 0) { //positive number for liking

          if ($liked) { //clicking on like when he has already liked, so unliking

            $post->unlike($this->user);
            $message = 4;

          } elseif ($disliked) { //clicking on like when has already disliked, so undislike then like
            
            $post->undislike($this->user)->like($this->user);
            $message = 6;
            
          } else { //clicking on like when he has not liked ir disliked, just like

            $post->like($this->user);
            $message = 2;

          }

        } else { //disliking

          if ($disliked) { //clicking on dislike when he has already disliked, so undisliking

            $post->undislike($this->user);
            $message = 5;

          } elseif ($liked) { //clicking on dislike when he has already liked, so unlike then dislike
            
            $post->unlike($this->user)->dislike($this->user);
            $message = 7;
            
          } else { //clicking on like when he has not yet liked

            $post->dislike($this->user);
            $message = 3;

          }

          return [true, $message];

        }

        $success = true;
        
      } catch (Exception $ex) {

        return [false, -1];

      } 

    } //close outer else

  }

  /*
  convert image orientations into css configuration classes arbitrarily defined for image display
  @param array images, array of images each element being an absolute path
  @return array of css classes
  */
  static public function getImageCssClasses(array $images): ?array { 
  
  $orientations = []; //array of landscape vs portrait in matching order as images array
  foreach ($images as $image) {

    $image = $_SERVER["DOCUMENT_ROOT"] . $image;
    list($width, $height) = getimagesize($image); 
    $width >= $height ? array_push($orientations, "landscape") : array_push($orientations, "portrait");  //tag each img as either portrait or landscape

  }

  $numberOfImg = count($orientations);
  $numberOfPortrait = 0;
  $numberOfLandscape = 0;
  foreach ($orientations as $orientation) {
    $orientation == "portrait" ? $numberOfPortrait++ : $numberOfLandscape++;
  }

  $configs = [];
  switch ($numberOfImg) {

    case 1:
      $numberOfPortrait > 0 ? array_push($configs, "portrait-1-in-1-portrait") : array_push($configs, "landscape-1-in-1-landscape");
      break;

    case 2:
      if ($numberOfPortrait == 2) { //both portraits
        array_push($configs, "portrait-1-in-2-portrait", "portrait-2-in-2-portrait");
      } else if ($numberOfLandscape == 2) { //both landscape
        array_push($configs, "landscape-1-in-2-landscape", "landscape-2-in-2-landscape");
      } else { //1 landscape, 2 portrait
        array_push($configs, "landscape-1-in-2-mixed", "landscape-2-in-2-mixed"); 
      }
      break;

    case 3:
      if ($orientations[0] == "portrait") { //most recent image is a portrait 
        array_push($configs, "portrait-1-in-3-portrait", "portrait-2-in-3-portrait", "portrait-3-in-3-portrait");
      } else {
        array_push($configs, "landscape-1-in-3-landscape", "landscape-2-in-3-landscape", "landscape-3-in-3-landscape");
      }
        break;

    case 4:
      if ($orientations[0] == "portrait") { //most recent image is a portrait
        array_push($configs, "portrait-1-in-4-portrait", "portrait-2-in-4-portrait", "portrait-3-in-4-portrait", "portrait-4-in-4-portrait");
      } else {
        array_push($configs, "landscape-1-in-4-landscape", "landscape-2-in-4-landscape", "landscape-3-in-4-landscape", "landscape-4-in-4-landscape");
      } 
      break;

    case 5:
      if ($orientations[0] == "portrait") { //most recent image is a portrait
        array_push($configs, "portrait-1-in-5-portrait", "portrait-2-in-5-portrait", "portrait-3-in-5-portrait", "portrait-4-in-5-portrait", "portrait-5-in-5-portrait");
      } else {
        array_push($configs, "landscape-1-in-5-landscape", "landscape-2-in-5-landscape", "landscape-3-in-5-landscape", "landscape-4-in-5-landscape", "landscape-5-in-5-landscape");
      }
      break;

    default: 
      throw new Exception("number of images exceeed 5 while configurations are defined up to 5.");    

  }

  return $configs;

}


} //end class

?>