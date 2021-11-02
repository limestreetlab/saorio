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
    $usernameExists = $this->mysql->request(MySQL::readMembersTableQuery, [":user" => $user]);
    if (!$usernameExists) {
      throw new Exception("username is invalid");
    } else {
      $this->user = $user;
    }

    $this->numberOfPosts = $this->mysql->request(MySQL::readPostNumberQuery, [":user" => $this->user])[0][0];
    $this->numberOfPages = MAX(ceil( $this->numberOfPosts / self::POSTS_PER_PAGE ), 1); //total post number divided by posts per page, rounded up

  }

  /*
  function to retrieve a post's data using its id
  @param id of the post to retrieve
  @return array of post data [id, type, timestamp, text, [image paths], [image descriptions], likes, dislikes, haveAlreadyLiked, haveAlreadyDisliked, [comments]]
  */
  public function getData(string $id): ?array {

    $post = $this->mysql->request(MySQL::readPostQuery, [":id" => $id]);
    $type = $post[0]["type"]; 

    if (!$post) {
      throw new Exception("the provided post id " . $id . "cannot be found.");
    }    

    switch ($type) {

      case 1:
        $textPost = new PostOfText(null, $id);
        extract( $textPost->getData() );
        $haveAlreadyLiked = $textPost->haveAlreadyLiked($user);
        $haveAlreadyDisliked = $textPost->haveAlreadyDisliked($user);
        $data = [ "id" => $id, "type" => $type, "timestamp" => $timestamp, "text" => $content, "images" => null, "descriptions" => null, "likes" => $likes, "dislikes" => $dislikes, "haveAlreadyLiked" => $haveAlreadyLiked, "haveAlreadyDisliked" => $haveAlreadyDisliked, "comments" => $comments];
        break;
      case 2:
        $imagePost = new PostOfImage(null, $id);
        extract( $imagePost->getData() );
        $haveAlreadyLiked = $imagePost->haveAlreadyLiked($user);
        $haveAlreadyDisliked = $imagePost->haveAlreadyDisliked($user);
        $images = []; //web paths
        $descriptions = []; //caption
        foreach ($content as $row) {
          array_push($images, ($row[0])->getFileWebPath());
          array_push($descriptions, $row[1]);
        }
        $data = [ "id" => $id, "type" => $type, "timestamp" => $timestamp, "text" => is_null($text) ? null : $text->getContent(), "images" => $images, "descriptions" => $descriptions, "likes" => $likes, "dislikes" => $dislikes, "haveAlreadyLiked" => $haveAlreadyLiked, "haveAlreadyDisliked" => $haveAlreadyDisliked, "comments" => $comments ];
        break;       

    }

    return $data;

  }

  /*
  get posts created by user, from most recent
  @param int number, number of posts to retrieve
  @param int skip, number of posts to skip from 1 being most recent 
  @return array of post data [id, type, timestamp, text, [image paths], [image descriptions], likes, dislikes, haveAlreadyLiked, haveAlreadyDisliked, [comments]]
  */
  protected function getPosts(int $number = null, int $skip = null): ?array {

    //switch SQL query on input params
    if ( !is_null($skip) && !is_null($number) ) { //from a given number for a certain number of posts

      $posts = $this->mysql->request(MySQL::readPostsQuery, [":user" => $this->user, ":offset" => $skip, ":count" => $number]);

    } elseif ( !is_null($skip) && is_null($number)  ) { //from a given number til the end

      $posts = $this->mysql->request(MySQL::readPostsQuery, [":user" => $this->user, ":offset" => $skip, ":count" => 99]);

    } elseif ( is_null($skip) && !is_null($number) ) { //for a certain number of posts from most recent
      
      $posts = $this->mysql->request(MySQL::readPostsQuery, [":user" => $this->user, ":offset" => 0, ":count" => $number]);
      
    } else { //get all posts

      $posts = $this->mysql->request(MySQL::readPostsQuery, [":user" => $this->user, ":offset" => 0, ":count" => 99]);

    }

    $data = [];
    foreach ($posts as $post) {

      $id = $post["id"];
      array_push($data, $this->getData($id));     

    }

    return $data;

  }

  /*
  get post data for a certain paginated page
  @param page, pagination number
  @return array of post data [id, type, timestamp, text, [image paths], [image descriptions], likes, dislikes, haveAlreadyLiked, haveAlreadyDisliked, [comments]]
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

      $rows = $this->mysql->request(MySQL::readImagePostsQuery, [":user" => $this->user, ":offset" => $skip, ":count" => $number]);

    } elseif ( !is_null($skip) && is_null($number)  ) { //from a given number til the end

      $rows = $this->mysql->request(MySQL::readImagePostsQuery, [":user" => $this->user, ":offset" => $skip, ":count" => 99]);

    } elseif ( is_null($skip) && !is_null($number) ) { //for a certain number of posts from most recent
      
      $rows = $this->mysql->request(MySQL::readImagePostsQuery, [":user" => $this->user, ":offset" => 0, ":count" => $number]);
      
    } else { //get all posts

      $rows = $this->mysql->request(MySQL::readImagePostsQuery, [":user" => $this->user, ":offset" => 0, ":count" => 99]);

    }

    $data = [];
    foreach ($rows as $row) {

      $relPath = UploadedPostImageFile::ConvertFileWebPath($row["image"]); //from file path to URL
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

      return $this->mysql->request(MySQL::readTextPostsQuery, [":user" => $this->user, ":offset" => $skip, ":count" => $number]);

    } elseif ( !is_null($skip) && is_null($number)  ) { //from a given number til the end

      return $this->mysql->request(MySQL::readTextPostsQuery, [":user" => $this->user, ":offset" => $skip, ":count" => 99]);

    } elseif ( is_null($skip) && !is_null($number) ) { //for a certain number of posts from most recent
      
      return $this->mysql->request(MySQL::readTextPostsQuery, [":user" => $this->user, ":offset" => 0, ":count" => $number]);
      
    } else { //get all posts

      return $this->mysql->request(MySQL::readTextPostsQuery, [":user" => $this->user, ":offset" => 0, ":count" => 99]);

    }

  }

  /*
  get a number of images posted by user
  @param number of images to get
  @return relative paths of images, most recent first (timestamp desending order)
  */
  public function getPostedImages(int $number = null): ?array {

    $number = is_null($number) ? 99 : $number; //set number to all if isn't already set
    $id_rows = $this->mysql->request(MySQL::readImagePostIdQuery, [":user" => $this->user, ":count" => $number]);
    
    $paths = [];
    foreach($id_rows as $id_row) {

      $img_rows = $this->mysql->request(MySQL::readImagePostImagesQuery, [":id" => $id_row[0]]);

      foreach($img_rows as $img_row) {
        array_push($paths, UploadedPostImageFile::ConvertFileWebPath( $img_row["image"] ) );
      }

    } //here, the array has at least 9 image paths but can be over

    return array_slice($paths, 0, 9);

  }

  /*
  function to remove a created post
  @param id of the post to be removed
  @return success boolean
  */
  public function remove(string $id): bool {
  
    try {
      
      $post = $this->mysql->request(MySQL::readPostQuery, [":id" => $id]);
      $poster = $post[0]["user"]; //creator of the post
      $type = $post[0]["type"]; 

      if (!$post) {
        throw new Exception("the provided post id " . $id . "cannot be found.");
      }    

      if ($this->user != $poster) { //a post can only be removed by its own poster
        throw new Exception("the post created by " . $poster . "cannot be deleted by " . $this->user);
      }

      switch ($type) {

        case 1:
          $post = new PostOfText(null, $id);
          break;
        case 2:
          $post = new PostOfImage(null, $id);
          break;
        default:
          throw new Exception("Unknown post type of " . $type);

      }

      $post->delete();
      return true;

    } catch (Exception $ex) {

      return false;

    }

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
  get the number of posts made by user
  @return number of posts
  */
  public function getNumberOfPosts(): int {

    return $this->numberOfPosts;

  }

  /*
  get the number of image posts made by user
  @return number of images posts
  */
  public function getNumberOfImagePosts(): int {

    return $this->mysql->request(MySQL::readImagePostNumberQuery, [":user" => $this->user])[0][0];

  }

  /*
  get number of images posted by user
  @return number of images
  */
  public function getNumberOfPostedImages(): int {

    return $this->mysql->request(MySQL::readImagesNumber, [":user" => $this->user])[0][0];

  }

  /*
  function to record voting (liking/disliking) of a post
  @param id, the id of the post to cast a vote 
  @vote, whether the vote is up or down, used position int for up, negative for down
  @return array, [bool success, int message] where message is a number indicating why vote not recorded or what vote actually took place (based on if he already has disliked/liked it)
    -1 system err, 1 user on own post, 2 like, 3 dislike, 4 unlike, 5 undislike, 6 undislike then like, 7 unlike then dislike
  */
  public function vote(string $id, int $vote): array {
    
    $posting = $this->mysql->request(MySQL::readPostQuery, [":id" => $id]);
    if (!$posting) {
      throw new Exception("the provided post id " . $id . "cannot be found.");
    }
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
    
    if ($this->user == $poster) { //disabling user from voting on own posts

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

        }

        return [true, $message];
        
      } catch (Exception $ex) {

        return [false, -1];

      } 

    } //close outer else

  }

  /*
  get number of likes received by user
  @return number of likes
  */
  public function getNumberOfLikes(): int {

    return $this->mysql->request(MySQL::readNumberOfLikesQuery, [":user" => $this->user])[0][0];

  }

  /*
  get number of dislikes received by user
  @return number of dislikes
  */
  public function getNumberOfDislikes(): int {

    return $this->mysql->request(MySQL::readNumberOfDislikesQuery, [":user" => $this->user])[0][0];

  }

  /*
  convert image orientations into css configuration classes arbitrarily defined for image display
  @param array images, array of images each element being either a filesystem path or a web path (URL)
  @return array of css classes
  */
  static public function getImageCssClasses(array $images): ?array { 
  
  if (empty($images)) {
    return null;
  }

  $orientations = []; //array of landscape vs portrait in matching order as images array
  foreach ($images as $image) {

    $image = $image[0] == "/" ? $_SERVER["DOCUMENT_ROOT"] . $image : $image; //if path is URL convert it to system file path as file path is needed for reading
    list($width, $height) = getimagesize($image); //read width and height of image
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