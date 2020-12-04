<?php
$userNameErr;
$passWordErr;
$emailErr;
$captchaErr;
$userName;
$passWord;
$email;
$skills;
$captcha;
$last_id;
$memberID;
$SkillID;

error_reporting(E_Error);
session_start();

if ($_SERVER["HTTPS"] != "on") {
    header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    //Check username
    $conn = mysqli_connect("mysql.cms.gre.ac.uk", "ms2721o", "D5ihHhojnjilPqFp", "mdb_ms2721o");
    $sql = "SELECT * FROM Member WHERE Username = '" . test_input($_POST["userName"]) . "'";
    if (mysqli_num_rows(mysqli_query($conn, $sql)) > 0) {
        $userNameErr = "Username is already taken";
        mysqli_close($conn);
    } else {
        mysqli_close($conn);
        $userName = test_input($_POST["userName"]);
        $passWord = test_input($_POST["passWord"]);
        $sql = "SELECT * FROM Member WHERE Email = '" . test_input($_POST["email"]) . "'";
        if (mysqli_num_rows(mysqli_query($conn, $sql)) > 0) {
            $emailErr = "Email is already taken";
            mysqli_close($conn);
        } else if (preg_match('/^([\w\.\-])+\@(([a-zA-Z0-9\-])+\.)+([a-zA-Z0-9]{2,4})$/', $email)) {
            $emailErr = "Email is invalid";
            mysqli_close($conn);
        } else {
            $email = test_input($_POST["email"]);
            mysqli_close($conn);

            //check if captcha is correct
            $answer = str_split($_POST["captcha"]);
            $captcha = $_SESSION["CAPTCHA"];
            $answerModified = $answer[0];
            for ($i = 1; $i < 5; $i++) {
                $answerModified .= ' ' . $answer[$i];
            }

            if ($answerModified == $captcha) {                //We have the correct answer, move on.
                $Skills = $_POST["Skills"];
                $skills = explode(",", $_POST["Skills"]); //if skills are given, split by comma into an array
                foreach ($skills as &$value) {
                    $value = test_input($value); //then iterate through each value trimming and neatening up each value
                }
                unset($value); // break the reference with the last element
                //add member.
                $verificationcode = rand(pow(10, 4), pow(10, 5) - 1); // 5 digit verification code
                $sql = "INSERT INTO Member (Username, Password, Email, ActivationStatus, ActivationCode) VALUES ('" . $userName . "', '" . crypt(test_input($_POST["passWord"]), '$1$somethin$') . "', '" . $email . "', 0 , '" . $verificationcode . "')";
                $memberID = imsertIntoTableQuery($sql);

                //Add their skills now!
                //$SkillID;
                foreach ($skills as &$value) {
                    $mysqli = new mysqli("mysql.cms.gre.ac.uk", "ms2721o", "D5ihHhojnjilPqFp", "mdb_ms2721o");
                    $result = $mysqli->query("SELECT SkillID FROM Skill WHERE Skill = '" . $value . "'");
                    if ($result->num_rows == 0) {
                        $sql = "INSERT INTO Skill (Skill) VALUES ('" . $value . "')";
                        $SkillID = imsertIntoTableQuery($sql);
                    } else {
                        while ($row = $result->fetch_assoc()) {
                            $SkillID = $row["SkillID"];
                        }
                    }
                    $mysqli->close();
                    $sql = "INSERT INTO MemberSkill (MemberID, SkillID) VALUES (" . $memberID . ", " . $SkillID . ")";
                    $SkillID = imsertIntoTableQuery($sql); //assign to skill id just to get it to work.
                }
                unset($value);

                //Send a verification email
                mail($email, "Verification Code For GRE Time Banking", $verificationcode, "From: ms2721o@greenwich.ac.uk\r\n");
                $_SESSION['userID'] = $memberID;
                //  setcookie("user", $memberID, time() + (86400 * 30), "/"); //remember user for a day
                header('Location: validation.php'); //redirect to validation page!
            } else {
                //answer wrong!
                $captchaErr = "CAPTCHA is incorrect!";
                mysqli_close($conn);
            }
        }
    }
}

function imsertIntoTableQuery($sql) {
    //convenient method for inserting data into a database using SQL INSERT statement
    $conn = mysqli_connect("mysql.cms.gre.ac.uk", "ms2721o", "D5ihHhojnjilPqFp", "mdb_ms2721o");
    if (mysqli_connect_errno($conn)) {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
    }
    if (mysqli_query($conn, $sql)) {
        return mysqli_insert_id($conn);
    } else {
        echo "Error: " . $sql . "<br>" . mysqli_error($conn);
    }
    mysqli_close($conn);
}

function test_input($data) {
    //prevents XSS and SQL injection attacks
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}
?>

<? xml version = "1.0" encoding = "UTF-8" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html version="-//W3C//DTD XHTML 1.1//EN" xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.w3.org/1999/xhtml http://www.w3.org/MarkUp/SCHEMA/xhtml11.xsd" >
    <head>
        <meta charset="utf-8" name="viewport" content="width=device-width, initial-scale=1.0"></meta>
        <title>Sign Up! It's Free!</title>
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
        <h1>Sign Up For Time Banking!</h1>
        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" onsubmit="">
            <table id="SignUpTable">
                <tbody>
                    <tr>
                        <td>
                            <label for="userName">Username</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input required="required" type="text" name="userName" id="userName" value="<?php echo $_POST['userName']; ?>"></input>
                            <span class="red-star">*<?php echo $userNameErr; ?></span>
                        </td>
                    </tr>
                    <tr>
                        <td>

                            <label for="passWord">Password<span class="error">*</span></label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input required="required" type="password" name="passWord" id="passWord" value=""></input>
                            <span class="red-star">*<?php echo $passWordErr; ?></span>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="Skills">Enter all your skills, separated by a " , "</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input type="text" name="Skills" id="Skills" value="<?php echo $_POST['Skills']; ?>"></input>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <img id="CAPTCHAIMG" src="captcha.php" alt="CAPTCHA"></img>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="captcha">Enter the CAPTCHA below:</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input required="required" type="text" name="captcha" id="captcha" value=""></input>
                            <span class="red-star">*<?php echo $captchaErr; ?></span>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <label for="email">Email</label>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input required="required" type="email" name="email" id="email" value="<?php echo $_POST['email']; ?>" onblur="validateEmail(this.value)"></input>
                            <span class="red-star">*<?php echo $emailErr; ?></span>
                        </td>
                    </tr>
                    <tr>
                        <td>
                            <input type="submit" value="Sign Up" id="submit" onclick='return Validate(this.form)'></input>
                        </td>
                    </tr>
                </tbody>
            </table>
        </form>
        <div class="footer">&copy; 2018 - Time Banking Management</div>
    </body>
</html>
