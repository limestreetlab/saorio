### saorio

![screenshot](.png)


A simple social network prototype where registered users can create a profile, view others' profiles, add friends, exchange messages, and create posts.

##### File Structure
- The app is made up of a controller `index.php` and related function pages (header, functions, init) imported into controller. View pages are put inside `pages` folder.  
- The css uses [Bootstrap](https://getbootstrap.com/) for styling.  
- There is an `upload` folder to store user's uploaded data such as profile picture.  
- There is only one js, which uses ajax to call a php script to check username availability and display result.  
- It uses MySQL. 

##### The Database
There is a convenience script `database_initialization.php` that helps initialize all table creations.

- The database name is saorio.
- The tables are members, friends, messages, and profiles.

##### The PHP Scripts
- The `functions.php` contains a `queryDB()` function which is called to handle all database queries.
- The database connection handle `$dbh` is created in `ini.php` and imported into controller.
- Database login information is declared in `config.php`.
- `header.php` contains the navbar; there are two sets of navbars depending on login status.
- Each link clicked generates a `GET` request to `index.php` which then loads it using `require_once __DIR__."/pages/$reqPage.php";`.
- Inside `pages` there is a non-view file named `queries.php` containing query strings.

 


