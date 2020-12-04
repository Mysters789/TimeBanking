<?php
$userNameErr;
$userName = $_COOKIE["USERNAME"];
$passWord;
$last_id;
$memberID;

error_reporting(E_Error);
session_start();

//Enforce HTTPS to ensure the login form is secure
if ($_SERVER["HTTPS"] != "on") {
    header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
    exit();
}

//If the form has been submitted, react to it:
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    //Check username
    $userName = test_input($_POST["userName"]);
    
    //set the cookie so the website "remembers" it
    setcookie("USERNAME", $userName);
    
    $passWord = test_input($_POST["passWord"]);
    $conn = mysqli_connect("mysql.cms.gre.ac.uk", "ms2721o", "D5ihHhojnjilPqFp", "mdb_ms2721o");
    
    //check if username and password match any records on the database
    $sql = "SELECT * FROM Member WHERE Username = '" . test_input($_POST["userName"]) . "' AND Password = '" . crypt(test_input($_POST["passWord"]), '$1$somethin$') . "'";
    if (mysqli_num_rows(mysqli_query($conn, $sql)) > 0) {
        while ($row = mysqli_fetch_assoc(mysqli_query($conn, $sql))) {
            //If they already activated, redirect them 
            if ($row["ActivationStatus"] == 1) {
                $_SESSION["User"] = $row["MemberID"];
                $_SESSION["Username"] = $row["Username"];
                mysqli_close($conn);
                if (isset($_GET['location'])) {
                    header('Location: ' . test_input($_GET['location']));
                } else {
                    $userNameErr = "You were logged in successfully! However you will have to manually go back to the previous page.";
                }
            } else {
                //They haven't activated, redirect them
                mysqli_close($conn);
                //setcookie("user", $memberID, time() + (86400 * 30), "/"); //remember user for a day
                header('Location: validation.php');
            }
        }
    } else {
        //no records were found, so either the username, password or both were incorrect
        $userNameErr = "Username and/or password is incorrect";
        mysqli_close($conn);
    }
}

function test_input($data) {
    //this method is used to prevent XSS and SQL injection.
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
?>

<? xml version = "1.0" encoding = "UTF-8" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html version="-//W3C//DTD XHTML 1.1//EN" 
      xmlns="http://www.w3.org/1999/xhtml" 
      xml:lang="en" 
      xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" 
      xsi:schemaLocation="http://www.w3.org/1999/xhtml http://www.w3.org/MarkUp/SCHEMA/xhtml11.xsd" >
    <head>
        <meta charset="utf-8" name="viewport" content="width=device-width, initial-scale=1.0"></meta>
        <title>Login</title>
        <link rel="stylesheet" href="css/styles.css"></link>
        <link rel="shortcut icon" href="favicon.PNG"></link>
    </head>
    <body>
        <div class="menu-con-logo">
            <a href="index.php">
                <div class="menu-ico-logo">
                </div>
            </a>
        </div>
        <?php
        include('CookiePolicy.php');
        ?>
        <div class="wrap">
            <form class="theForm" method="post">
                <legend><label>You are required to authenticate to continue: </label></legend>
                <table>
                    <tr>
                        <span class="red-star"><?php echo $userNameErr; ?></span>
                    </tr>
                    <tr>
                        <td><label for="userName">Username</label></td>
                    </tr> 
                    <td>
                        <input required="required" type="userName" name="userName" id="userName" value="<?php echo $userName; ?>"></input>
                    </td>
                    <tr>
                        <td><label for="passWord">Password</label></td>
                    </tr> 
                    <tr>
                        <td>
                            <input required="required" type="passWord" name="passWord" id="passWord" type="password"></input>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input type="submit" value="Log In" id="submit"></input>
                        </td>
                    </tr>
                </table>
            </form>
        </div>
        <div class="footer">&copy; 2018 - Time Banking Management</div>
    </body>
</html>
