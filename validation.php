<?php
session_start();
$activatecodeerror;

//if the user hasn't signed up, they will be redirect to do so.
if (!isset($_SESSION["userID"])) {
    header('Location: signup.php');
}

//If the person submits the form, the following code executes:
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $mysqli = new mysqli("mysql.cms.gre.ac.uk", "ms2721o", "D5ihHhojnjilPqFp", "mdb_ms2721o");
    $result = $mysqli->query("SELECT * FROM Member WHERE MemberID = " . $_SESSION["userID"]);
    //This should be one result only
    if ($result->num_rows != 0) {
        while ($row = $result->fetch_assoc()) {
            //If they already activated, redirect them 
            if ($row["ActivationStatus"] == 1) {
                $_SESSION["User"] = $row["MemberID"];
                $_SESSION["Username"] = $row["Username"];
                $mysqli->close();
                header('Location: index.php');
            }
            //Check if entered activation code is correct
            if (test_input($_POST["activationcode"]) == test_input($row["ActivationCode"])) {
                //activate the user
                $_SESSION["User"] = $row["MemberID"];
                $_SESSION["Username"] = $row["Username"];
                $mysqli->close();
                $mysqli = new mysqli("mysql.cms.gre.ac.uk", "ms2721o", "D5ihHhojnjilPqFp", "mdb_ms2721o");
                $result = $mysqli->query("UPDATE Member SET ActivationStatus = 1 WHERE MemberID = " . $_SESSION["userID"]);
                $mysqli->close();
                $sql = "INSERT INTO TimeCredit (MemberID, Amount, Date) VALUES (" . $_SESSION["userID"] . " , 100 , '" . date('Y/m/d H:i:s') . "')";
                $temp = imsertIntoTableQuery($sql);
                header('Location: index.php');
            } else {
                $activatecodeerror = "Activation code is incorrect :( try again";
                $mysqli->close();
            }
        }
    } else {
        $mysqli->close();
        header('Location: signup.php');
    }
}

function test_input($data) {
    //a method to prevent XSS and SQL injection
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function imsertIntoTableQuery($sql) {
    //a convenient method to use INSERT SQL statements
    $conn = mysqli_connect("mysql.cms.gre.ac.uk", "ms2721o", "D5ihHhojnjilPqFp", "mdb_ms2721o");
    if (mysqli_query($conn, $sql)) {
        $last_id = mysqli_insert_id($conn);
    }
    mysqli_close($conn);
}
?>

<? xml version = "1.0" encoding = "UTF-8" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html version="-//W3C//DTD XHTML 1.1//EN" xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.w3.org/1999/xhtml http://www.w3.org/MarkUp/SCHEMA/xhtml11.xsd" >
    <head>
        <meta charset="utf-8" name="viewport" content="width=device-width, initial-scale=1.0"></meta>
        <title>Activation</title>
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
        <p>Please check your email, and enter the activation code below:</p>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="theForm">
            <table id="activationTable">
                <tr><td><input required="required" type="text" name="activationcode" id="activationcode"></input></td></tr>
                <tr><td><span class="red-star"><?php echo $activatecodeerror; ?></span></td></tr>
                <tr><td><input type="submit" value="Verify" id="activate"></input></td></tr>
            </table>
        </form>
        <div class="footer">&copy; 2018 - Time Banking Management</div>
    </body>
</html>
