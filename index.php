<!doctype html>

<html>
  
  <head>
    <title>DOCX Reader by Siddharth Bhatt</title>
    <meta name="viewport" content="width=device-width">
    <link rel="stylesheet" type="text/css" href="css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="css/bootstrap-theme.min.css">
    <link rel="stylesheet" type="text/css" href="css/style.css">
    <link rel='icon' type='image/png' href='favicon.png'>
    <script type="text/javascript" src="js/jquery.js"></script>
    <script type="text/javascript" src="js/bootstrap.min.js"></script>
    
      <style type="text/css">
      body {
        padding-top: 70px;
        padding-bottom: 20px;
      }
    </style>
  </head>
  
  <body>
<div class="container">


<?php

class OneFileLoginApplication
{
    /**
     * @var string Type of used database (currently only SQLite, but feel free to expand this with mysql etc)
     */
    private $db_type = "sqlite"; //

    /**
     * @var string Path of the database file (create this with _install.php)
     */
    private $db_sqlite_path = "./users.db";

    /**
     * @var object Database connection
     */
    private $db_connection = null;

    /**
     * @var bool Login status of user
     */
    private $user_is_logged_in = false;

    /**
     * @var string System messages, likes errors, notices, etc.
     */
    public $feedback = "";


    /**
     * Does necessary checks for PHP version and PHP password compatibility library and runs the application
     */
    public function __construct()
    {
        if ($this->performMinimumRequirementsCheck()) {
            $this->runApplication();
        }
    }

    /**
     * Performs a check for minimum requirements to run this application.
     * Does not run the further application when PHP version is lower than 5.3.7
     * Does include the PHP password compatibility library when PHP version lower than 5.5.0
     * (this library adds the PHP 5.5 password hashing functions to older versions of PHP)
     * @return bool Success status of minimum requirements check, default is false
     */
    private function performMinimumRequirementsCheck()
    {
        if (version_compare(PHP_VERSION, '5.3.7', '<')) {
            echo "<div class='alert alert-dismissable alert-danger'>
        <button type='button' class='close' data-dismiss='alert'>&times;</button>
        <b>Sorry !</b> this script does not run on a PHP version older than 5.3.7 !</div>";
        } elseif (version_compare(PHP_VERSION, '5.5.0', '<')) {
            require_once("libraries/password_compatibility_library.php");
            return true;
        } elseif (version_compare(PHP_VERSION, '5.5.0', '>=')) {
            return true;
        }
        // default return
        return false;
    }

    /**
     * This is basically the controller that handles the entire flow of the application.
     */
    public function runApplication()
    {
        // check is user wants to see register page (etc.)
        if (isset($_GET["action"]) && $_GET["action"] == "register") {
            $this->doRegistration();
            $this->showPageRegistration();
        } else {
            // start the session, always needed!
            $this->doStartSession();
            // check for possible user interactions (login with session/post data or logout)
            $this->performUserLoginAction();
            // show "page", according to user's login status
            if ($this->getUserLoginStatus()) {
                $this->showPageLoggedIn();
            } else {
                $this->showPageLoginForm();
            }
        }
    }

    /**
     * Creates a PDO database connection (in this case to a SQLite flat-file database)
     * @return bool Database creation success status, false by default
     */
    private function createDatabaseConnection()
    {
        try {
            $this->db_connection = new PDO($this->db_type . ':' . $this->db_sqlite_path);
            return true;
        } catch (PDOException $e) {
            $this->feedback = "PDO database connection problem: " . $e->getMessage();
        } catch (Exception $e) {
            $this->feedback = "General problem: " . $e->getMessage();
        }
        return false;
    }

    /**
     * Handles the flow of the login/logout process. According to the circumstances, a logout, a login with session
     * data or a login with post data will be performed
     */
    private function performUserLoginAction()
    {
        if (isset($_GET["action"]) && $_GET["action"] == "logout") {
            $this->doLogout();
        } elseif (!empty($_SESSION['user_name']) && ($_SESSION['user_is_logged_in'])) {
            $this->doLoginWithSessionData();
        } elseif (isset($_POST["login"])) {
            $this->doLoginWithPostData();
        }
    }

    /**
     * Simply starts the session.
     * It's cleaner to put this into a method than writing it directly into runApplication()
     */
    private function doStartSession()
    {
        session_start();
    }

    /**
     * Set a marker (NOTE: is this method necessary ?)
     */
    private function doLoginWithSessionData()
    {
        $this->user_is_logged_in = true; // ?
    }

    /**
     * Process flow of login with POST data
     */
    private function doLoginWithPostData()
    {
        if ($this->checkLoginFormDataNotEmpty()) {
            if ($this->createDatabaseConnection()) {
                $this->checkPasswordCorrectnessAndLogin();
            }
        }
    }

    /**
     * Logs the user out
     */
    private function doLogout()
    {
        $_SESSION = array();
        session_destroy();
        $this->user_is_logged_in = false;
        $this->feedback = "<div class='alert alert-dismissable alert-info'>
        <button type='button' class='close' data-dismiss='alert'>&times;</button>You were just logged out.
      </div>";
    }

    /**
     * The registration flow
     * @return bool
     */
    private function doRegistration()
    {
        if ($this->checkRegistrationData()) {
            if ($this->createDatabaseConnection()) {
                $this->createNewUser();
            }
        }
        // default return
        return false;
    }

    /**
     * Validates the login form data, checks if username and password are provided
     * @return bool Login form data check success state
     */
    private function checkLoginFormDataNotEmpty()
    {
        if (!empty($_POST['user_name']) && !empty($_POST['user_password'])) {
            return true;
        } elseif (empty($_POST['user_name'])) {
            $this->feedback = "Username field was empty.";
        } elseif (empty($_POST['user_password'])) {
            $this->feedback = "Password field was empty.";
        }
        // default return
        return false;
    }

    /**
     * Checks if user exits, if so: check if provided password matches the one in the database
     * @return bool User login success status
     */
    private function checkPasswordCorrectnessAndLogin()
    {
        // remember: the user can log in with username or email address
        $sql = 'SELECT user_name, user_email, user_password_hash
                FROM users
                WHERE user_name = :user_name OR user_email = :user_name
                LIMIT 1';
        $query = $this->db_connection->prepare($sql);
        $query->bindValue(':user_name', $_POST['user_name']);
        $query->execute();

        // Btw that's the weird way to get num_rows in PDO with SQLite:
        // if (count($query->fetchAll(PDO::FETCH_NUM)) == 1) {
        // Holy! But that's how it is. $result->numRows() works with SQLite pure, but not with SQLite PDO.
        // This is so crappy, but that's how PDO works.
        // As there is no numRows() in SQLite/PDO (!!) we have to do it this way:
        // If you meet the inventor of PDO, punch him. Seriously.
        $result_row = $query->fetchObject();
        if ($result_row) {
            // using PHP 5.5's password_verify() function to check password
            if (password_verify($_POST['user_password'], $result_row->user_password_hash)) {
                // write user data into PHP SESSION [a file on your server]
                $_SESSION['user_name'] = $result_row->user_name;
                $_SESSION['user_email'] = $result_row->user_email;
                $_SESSION['user_is_logged_in'] = true;
                $this->user_is_logged_in = true;
                return true;
            } else {
                $this->feedback = "<div class='alert alert-dismissable alert-danger'>
        <button type='button' class='close' data-dismiss='alert'>&times;</button>
        Wrong Password. Try Again.
      </div>";
            }
        } else {
            $this->feedback = "<div class='alert alert-dismissable alert-danger'>
        <button type='button' class='close' data-dismiss='alert'>&times;</button>
<b>Sorry. </b>This user does not exist.</div>";
        }
        // default return
        return false;
    }

    /**
     * Validates the user's registration input
     * @return bool Success status of user's registration data validation
     */
    private function checkRegistrationData()
    {
        // if no registration form submitted: exit the method
        if (!isset($_POST["register"])) {
            return false;
        }

        // validating the input
        if (!empty($_POST['user_name'])
            && strlen($_POST['user_name']) <= 64
            && strlen($_POST['user_name']) >= 2
            && preg_match('/^[a-z\d]{2,64}$/i', $_POST['user_name'])
            && !empty($_POST['user_email'])
            && strlen($_POST['user_email']) <= 64
            && filter_var($_POST['user_email'], FILTER_VALIDATE_EMAIL)
            && !empty($_POST['user_password_new'])
            && !empty($_POST['user_password_repeat'])
            && ($_POST['user_password_new'] === $_POST['user_password_repeat'])
        ) {
            // only this case return true, only this case is valid
            return true;
        } elseif (empty($_POST['user_name'])) {
            $this->feedback = "<div class='alert alert-dismissable alert-danger'>
        <button type='button' class='close' data-dismiss='alert'>&times;</button>Empty Username</div>";
        } elseif (empty($_POST['user_password_new']) || empty($_POST['user_password_repeat'])) {
            $this->feedback = "<div class='alert alert-dismissable alert-danger'>
        <button type='button' class='close' data-dismiss='alert'>&times;</button>Empty Password</div>";
        } elseif ($_POST['user_password_new'] !== $_POST['user_password_repeat']) {
            $this->feedback = "<div class='alert alert-dismissable alert-danger'>
        <button type='button' class='close' data-dismiss='alert'>&times;</button>Password and password repeat are not the same</div>";
        } elseif (strlen($_POST['user_password_new']) < 6) {
            $this->feedback = "<div class='alert alert-dismissable alert-danger'>
        <button type='button' class='close' data-dismiss='alert'>&times;</button>Password has a minimum length of 6 characters</div>";
        } elseif (strlen($_POST['user_name']) > 64 || strlen($_POST['user_name']) < 2) {
            $this->feedback = "<div class='alert alert-dismissable alert-danger'>
        <button type='button' class='close' data-dismiss='alert'>&times;</button>Username cannot be shorter than 2 or longer than 64 characters</div>";
        } elseif (!preg_match('/^[a-z\d]{2,64}$/i', $_POST['user_name'])) {
            $this->feedback = "<div class='alert alert-dismissable alert-danger'>
        <button type='button' class='close' data-dismiss='alert'>&times;</button>Username does not fit the name scheme: only a-Z and numbers are allowed, 2 to 64 characters</div>";
        } elseif (empty($_POST['user_email'])) {
            $this->feedback = "<div class='alert alert-dismissable alert-danger'>
        <button type='button' class='close' data-dismiss='alert'>&times;</button>Email cannot be empty</div>";
        } elseif (strlen($_POST['user_email']) > 64) {
            $this->feedback = "<div class='alert alert-dismissable alert-danger'>
        <button type='button' class='close' data-dismiss='alert'>&times;</button>Email cannot be longer than 64 characters</div>";
        } elseif (!filter_var($_POST['user_email'], FILTER_VALIDATE_EMAIL)) {
            $this->feedback = "<div class='alert alert-dismissable alert-danger'>
        <button type='button' class='close' data-dismiss='alert'>&times;</button>Your email address is not in a valid email format</div>";
        } else {
            $this->feedback = "<div class='alert alert-dismissable alert-danger'>
        <button type='button' class='close' data-dismiss='alert'>&times;</button>An unknown error occurred.</div>";
        }

        // default return
        return false;
    }

    /**
     * Creates a new user.
     * @return bool Success status of user registration
     */
    private function createNewUser()
    {
        // remove html code etc. from username and email
        $user_name = htmlentities($_POST['user_name'], ENT_QUOTES);
        $user_email = htmlentities($_POST['user_email'], ENT_QUOTES);
        $user_password = $_POST['user_password_new'];
        // crypt the user's password with the PHP 5.5's password_hash() function, results in a 60 char hash string.
        // the constant PASSWORD_DEFAULT comes from PHP 5.5 or the password_compatibility_library
        $user_password_hash = password_hash($user_password, PASSWORD_DEFAULT);

        $sql = 'SELECT * FROM users WHERE user_name = :user_name OR user_email = :user_email';
        $query = $this->db_connection->prepare($sql);
        $query->bindValue(':user_name', $user_name);
        $query->bindValue(':user_email', $user_email);
        $query->execute();

        // As there is no numRows() in SQLite/PDO (!!) we have to do it this way:
        // If you meet the inventor of PDO, punch him. Seriously.
        $result_row = $query->fetchObject();
        if ($result_row) {
            $this->feedback = "
            <div class='alert alert-dismissable alert-danger'>
            <button type='button' class='close' data-dismiss='alert'>&times;</button>
            <b>Sorry !</b> that username / email is already taken. Please choose another one.
            </div>";
        } else {
            $sql = 'INSERT INTO users (user_name, user_password_hash, user_email)
                    VALUES(:user_name, :user_password_hash, :user_email)';
            $query = $this->db_connection->prepare($sql);
            $query->bindValue(':user_name', $user_name);
            $query->bindValue(':user_password_hash', $user_password_hash);
            $query->bindValue(':user_email', $user_email);
            // PDO's execute() gives back TRUE when successful, FALSE when not
            // @link http://stackoverflow.com/q/1661863/1114320
            $registration_success_state = $query->execute();

            if ($registration_success_state) {
                
                if (!file_exists("./files/".$_POST['user_name']."/")) 
                {
                        mkdir("./files/".$_POST['user_name']."/", 0777, true);
                }
                
                $this->feedback = "
                <div class='alert alert-dismissable alert-success'>
                <button type='button' class='close' data-dismiss='alert'>&times;</button>
                <b>Success !</b> Your account has been created successfully. You can now log in.
                </div>";
                return true;
            } else {
                $this->feedback = "<div class='alert alert-dismissable alert-danger'>
            <button type='button' class='close' data-dismiss='alert'>&times;</button>
            <b>Sorry !</b> Your registration failed. Please go back and try again.
            </div>";
            }
        }
        // default return
        return false;
    }

    /**
     * Simply returns the current status of the user's login
     * @return bool User's login status
     */
    public function getUserLoginStatus()
    {
        return $this->user_is_logged_in;
    }

    /**
     * Simple demo-"page" that will be shown when the user is logged in.
     * In a real application you would probably include an html-template here, but for this extremely simple
     * demo the "echo" statements are totally okay.
     */
    private function showPageLoggedIn()
    {
        if ($this->feedback) {
            echo $this->feedback . "";
        }

        include 'dash.php';
        
        //echo 'Hello ' . $_SESSION['user_name'] . ', you are logged in.<br/><br/>';
        //echo '<a href="' . $_SERVER['SCRIPT_NAME'] . '?action=logout">Log out</a>';
    }

    /**
     * Simple demo-"page" with the login form.
     * In a real application you would probably include an html-template here, but for this extremely simple
     * demo the "echo" statements are totally okay.
     */
    private function showPageLoginForm()
    {
        if ($this->feedback) {
            echo $this->feedback . "<br/><br/>";
        }
        echo "
            <div class='navbar navbar-default navbar-fixed-top'>
      <div class='container'>
        <div class='navbar-header'>
          <button type='button' class='navbar-toggle' data-toggle='collapse' data-target='.navbar-collapse'>
            <span class='icon-bar'></span><span class='icon-bar'></span><span class='icon-bar'></span>
          </button>
          <a class='navbar-brand' href='index.php'>DOCX Reader By Siddharth Bhatt</a>
        </div>
        <div class='navbar-collapse collapse'>
          <ul class='nav navbar-nav'>
            <li class='active'>
              <a href='index.php'>Home</a>
            </li>
       
            <li>
                <a href='index.php?action=register'>Register</a>
            </li>
          </ul>
          <form name='loginform' action='index.php' method='post' class='navbar-form navbar-right'>
            <div class='form-group'>
              <input type='text' placeholder='Username' class='form-control' id='login_input_username' name='user_name'>
            </div>
            <div class='form-group'>
              <input type='password' placeholder='Password' class='form-control' id='login_input_password' name='user_password'>
            </div>
            <input type='submit' class='btn btn-success' name='login' value='Sign in'>Sign in</button>
          </form>
        </div>
        <!--/.navbar-collapse -->
      </div>
    </div>
    ";
        
        echo '<h3>Login</h3><hr>';

        echo '<form method="post" action="' . $_SERVER['SCRIPT_NAME'] . '"  >';
        //echo '<label for="login_input_username">Username (or email)</label> ';
        echo '<input id="login_input_username" type="text" name="user_name" required class="form-control input-lg" placeholder="Username"/> ';
        //echo '<label for="login_input_password">Password</label> ';
        echo '<input id="login_input_password" type="password" name="user_password" required class="form-control input-lg" placeholder="Password"/> ';
        echo '<br><br><center><input type="submit"  name="login" value="Log in" class="btn btn-primary"/></center>';
        echo '</form>';

       // echo '<a href="' . $_SERVER['SCRIPT_NAME'] . '?action=register">Register new account</a>';
    }

    /**
     * Simple demo-"page" with the registration form.
     * In a real application you would probably include an html-template here, but for this extremely simple
     * demo the "echo" statements are totally okay.
     */
    private function showPageRegistration()
    {
        if ($this->feedback) {
            echo $this->feedback . "<br/><br/>";
        }
        echo "    <div class='navbar navbar-default navbar-fixed-top'>
      <div class='container'>
        <div class='navbar-header'>
          <button type='button' class='navbar-toggle' data-toggle='collapse' data-target='.navbar-collapse'>
            <span class='icon-bar'></span><span class='icon-bar'></span><span class='icon-bar'></span>
          </button>
          <a class='navbar-brand' href='index.php'>DOCX Reader By Siddharth Bhatt</a>
        </div>
        <div class='navbar-collapse collapse'>
          <ul class='nav navbar-nav'>
            <li>
              <a href='index.php'>Home</a>
            </li>
       
            <li class='active'>
                <a href='register.php'>Register</a>
            </li>
          </ul>
          <form name='loginform' action='index.php' method='post' class='navbar-form navbar-right'>
            <div class='form-group'>
              <input type='text' placeholder='Username' class='form-control' id='login_input_username' name='user_name'>
            </div>
            <div class='form-group'>
              <input type='password' placeholder='Password' class='form-control' id='login_input_password' name='user_password'>
            </div>
            <input type='submit' class='btn btn-success' name='login' value='Sign in'>Sign in</button>
          </form>
        </div>
        <!--/.navbar-collapse -->
      </div>
    </div>";
        echo '<h3>Register</h3><hr>';

        echo '<form method="post" action="' . $_SERVER['SCRIPT_NAME'] . '?action=register" name="registerform">';
        //echo '<label for="login_input_username">Username (only letters and numbers, 2 to 64 characters)</label>';
        echo '<input id="login_input_username" type="text" pattern="[a-zA-Z0-9]{2,64}" name="user_name" required  class="form-control input-lg" placeholder="Username : only letters and numbers"/>';
        //echo '<label for="login_input_email">User\'s email</label>';
        echo '<input id="login_input_email" type="email" name="user_email" required class="form-control input-lg" placeholder="Email"/>';
        //echo '<label for="login_input_password_new">Password (min. 6 characters)</label>';
        echo '<input id="login_input_password_new" type="password" name="user_password_new" pattern=".{6,}" required autocomplete="off" class="form-control input-lg" placeholder="Password : minimum 6 characters required"/>';
        //echo '<label for="login_input_password_repeat">Repeat password</label>';
        echo '<input id="login_input_password_repeat" type="password" name="user_password_repeat" pattern=".{6,}" required autocomplete="off" class="form-control input-lg" placeholder="Confirm Password : should match password"/>';
        echo '<br><br><center><input type="submit" name="register" value="Register" class="btn btn-primary"/></center>';
        echo '</form>';

       // echo '<hr><footer><a href="' . $_SERVER['SCRIPT_NAME'] . '">Homepage</a></footer>';
    }
}

// run the application
$application = new OneFileLoginApplication();
?>
    <hr>
    <footer>
        &copy; 2014 <a href="http://siddharthbhatt.com" target="_blank">Siddharth Bhatt</a>
    </footer>
    </div>
