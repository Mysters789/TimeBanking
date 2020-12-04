<?php
$serviceErr;
$statusErr;
$skillErr;
$locationErr;
$creditErr;

$serviceType;
$status;
$skill;
$images;
$location;
$credit;

$last_id;
$postID;
$LocationID;

error_reporting(E_Error);
session_start();

//If they haven't logged in, they will be redirected to the appropriate page
if (!isset($_SESSION["Username"])) {
    header("Location: login.php?location=" . urlencode($_SERVER['REQUEST_URI']));
}

if (!isset($_SESSION["User"])) {
    header("Location: login.php?location=" . urlencode($_SERVER['REQUEST_URI']));
}

//if they didn't come from the manage page, redirect them back.
if (!isset($_SESSION["PostID"])) {
    header('Location: manage.php');
}

//Find the post using the PostID from the URL
$data = array(); // create a variable to hold the information
$mysqli = new mysqli("mysql.cms.gre.ac.uk", "ms2721o", "D5ihHhojnjilPqFp", "mdb_ms2721o");
$result = $mysqli->query("SELECT * FROM Post WHERE PostID = " . $_SESSION["PostID"]);
if ($result->num_rows != 0) {
    while ($row = $result->fetch_assoc()) {
        $data[] = $row; // add the row in to the results (data) array
    }
}
$mysqli->close();

//This is an array of size 1. this will print out the information of the post retreived, in the input feilds appropriate.
foreach ($data as &$array) {
    if ($array["Status"] == 1) {
        $status = "Open";
    } else {
        $status = "Completed";
    }

    //title of the post.
    $serviceType = $array["ServiceType"];

    //get all skillID's assossiated with the post
    $Postskills = array();
    $mysqli = new mysqli("mysql.cms.gre.ac.uk", "ms2721o", "D5ihHhojnjilPqFp", "mdb_ms2721o");
    $result = $mysqli->query("SELECT * FROM PostSkill WHERE PostID = " . $array["PostID"]);
    if ($result->num_rows != 0) {
        while ($row2 = $result->fetch_assoc()) {
            $Postskills[] = $row2;
        }
    }
    $mysqli->close();

    //Using all retreived SkillID's, get the skill names.
    foreach ($Postskills as &$eachSkill) {
        $mysqli = new mysqli("mysql.cms.gre.ac.uk", "ms2721o", "D5ihHhojnjilPqFp", "mdb_ms2721o");
        $result = $mysqli->query("SELECT * FROM Skill WHERE SkillID = " . $eachSkill["SkillID"]);
        if ($result->num_rows != 0) {
            while ($rowtemp = $result->fetch_assoc()) {
                $skill .= $rowtemp["Skill"] . ",";
            }
        }
        $mysqli->close();
    }

    //get the name of the location assossiated with the post.
    $location = $array["LocationID"];
    $mysqli = new mysqli("mysql.cms.gre.ac.uk", "ms2721o", "D5ihHhojnjilPqFp", "mdb_ms2721o");
    $result = $mysqli->query("SELECT Address FROM Location WHERE LocationID = " . $location);
    if ($result->num_rows != 0) {
        while ($rowtemp = $result->fetch_assoc()) {
            $location = $rowtemp["Address"];
        }
    }
    $mysqli->close();

    //find all images assosated with the post, and add the option to delete it next to it.
    $mysqli = new mysqli("mysql.cms.gre.ac.uk", "ms2721o", "D5ihHhojnjilPqFp", "mdb_ms2721o");
    $result = $mysqli->query("SELECT * FROM Image WHERE PostID = " . $array["PostID"]);
    if ($result->num_rows != 0) {
        while ($rowtemp = $result->fetch_assoc()) {
            $images .= '<img src="uploads/' . $rowtemp["ImageSRC"] . '" alt="Uploaded Image" width="100" height="100">';
            $images .= "<button class='button' name='Delete' value='" . $rowtemp["ImageID"] . "'>Delete</button>";
        }
    }
    $mysqli->close();

    //the number of credits awarded to the post.
    $credit = $array["Reward"];
}

if (isset($_POST['submit'])) {
    //if the form has been submitted, update the post
    if ($_POST["serviceStatus"] == "Open") {
        $status = 1;
    } else {
        $status = 0;
    }

    //Check if enough credits are available
    $totalMoney = 0;
    $mysqli = new mysqli("mysql.cms.gre.ac.uk", "ms2721o", "D5ihHhojnjilPqFp", "mdb_ms2721o");
    $result = $mysqli->query("SELECT * FROM TimeCredit WHERE MemberID = '" . $_SESSION["User"] . "'");
    if ($result->num_rows != 0) {
        while ($row5 = $result->fetch_assoc()) {
            $totalMoney = $totalMoney + $row5["Amount"];
        }
    }
    $totalMoney = $totalMoney + $credit; //This is because we are editing a post, so we need to assume the current value will probably change
    $mysqli->close();

    if (test_input($_POST["credit"]) <= $totalMoney && test_input($_POST["credit"]) > 0) {
        //Deduct from their account
        $mysqli = new mysqli("mysql.cms.gre.ac.uk", "ms2721o", "D5ihHhojnjilPqFp", "mdb_ms2721o");
        $result = $mysqli->query("UPDATE TimeCredit SET Amount = " . ($totalMoney - test_input($_POST["credit"])) . ", Date = '" . date('Y/m/d H:i:s') . "'" . " WHERE MemberID = " . $_SESSION["User"]);
        $mysqli->close();

        //update the post
        $mysqli = new mysqli("mysql.cms.gre.ac.uk", "ms2721o", "D5ihHhojnjilPqFp", "mdb_ms2721o");
        $result = $mysqli->query("UPDATE Post SET MemberID = " . $_SESSION["User"] . ", ServiceType = '" . test_input($_POST["serviceRequired"]) . "', Status = " . $status . ", Reward = " . test_input($_POST["credit"]) . " WHERE PostID = " . $_SESSION["PostID"]);
        $mysqli->close();

        //if skills are given, split by comma into an array
        $skillzz = explode(",", test_input($_POST["skillsRequired"]));
        array_filter($skillzz);

        //remove all post skills from before to be replaced!
        $mysqli = new mysqli("mysql.cms.gre.ac.uk", "ms2721o", "D5ihHhojnjilPqFp", "mdb_ms2721o");
        $result = $mysqli->query("DELETE FROM PostSkill WHERE PostID = " . $_SESSION['PostID']);
        $mysqli->close();

        //for each skill, find if the skill is known. If the skill is known, we get the SkillID and add the skill assossiation to the PostSkill table.
        //if the still isn't known, we add the skill to the Skills table, get the SkillID, and then add the skill assossiation to the PostSkill table.
        foreach ($skillzz as &$value) {
            if ($value != "") {
                $mysqli = new mysqli("mysql.cms.gre.ac.uk", "ms2721o", "D5ihHhojnjilPqFp", "mdb_ms2721o");
                $result = $mysqli->query("SELECT SkillID FROM Skill WHERE Skill = '" . test_input($value) . "'");
                if ($result->num_rows == 0) {
                    $mysqli->close();
                    $sql = "INSERT INTO Skill (Skill) VALUES ('" . test_input($value) . "')";
                    $SkillID = insertIntoTableQuery($sql);
                } else {
                    while ($row4 = $result->fetch_assoc()) {
                        $SkillID = $row4["SkillID"];
                    }
                    $mysqli->close();
                }
                $sql = "INSERT INTO PostSkill (PostID, SkillID) VALUES (" . $_SESSION["PostID"] . ", " . $SkillID . ")";
                $SkillID = insertIntoTableQuery($sql);
            }
        }
        unset($value);

        //redirect to post management page
        header('Location: manage.php');
    } else if (test_input($_POST["credit"]) > $totalMoney) {
        $creditErr = "The amount of credits you entered exceed the amount you have in your account!";
    } else if (test_input($_POST["credit"]) < 0) {
        $creditErr = "You are not allowed to enter a negetive amount of credits!";
    }
}


if (isset($_POST['Delete'])) {
    //if an image is being deleted
    $mysqli = new mysqli("mysql.cms.gre.ac.uk", "ms2721o", "D5ihHhojnjilPqFp", "mdb_ms2721o");
    $result = $mysqli->query("DELETE FROM Image WHERE ImageID = " . $_POST['Delete']);
    $mysqli->close();

    //redirect the page to ensure the user sees the image being deleted.
    header('Location: editPost.php');
}

if (isset($_POST['upload'])) {
    //if an image is being upload
    $fna = explode('.', $_FILES['fileToUpload']['name']);
    $ext = $fna[count($fna) - 1];
    //check if the image is of the correct format, and has the correct extension.
    if (!preg_match('/gif|png|jpeg|bmp/', $_FILES['fileToUpload']['type'])) {
        echo "<p><strong>Sorry, only browser compatible images allowed</strong></p>";
    } else if (!preg_match('/gif|png|jpg|jpeg|bmp/', $ext)) {
        echo "<p><strong>Sorry, only browser compatible images allowed</strong></p>";
    } else {
        // rename and copy the file to the uploads directory
        $filename = $_SESSION["Username"] . "_" . time() . "." . $ext;
        if (copy($_FILES['fileToUpload']['tmp_name'], "uploads/$filename")) {
            $sql = "INSERT INTO Image (PostID, ImageSRC) VALUES (" . $_SESSION["PostID"] . ",'" . $filename . "')";
            insertIntoTableQuery($sql);
            // set group rw permission to allow deleting of the upload
            if (chmod("uploads/$filename", 0664)) {
                echo "<p><strong>File successfully uploaded</strong></p>\n";
            } else {
                echo "<p><strong>Error: failed to chmod file</strong></p>";
            }
        } else {
            echo "<p><strong>Error: failed to copy file</strong></p>";
        }
    }
    //refresh the page to reflect the changes.
    header('Location: editPost.php');
}

function insertIntoTableQuery($sql) {
    //a convinient method for INSERT queries.
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
    //prevents XSS and SQL injection
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
        <title>Post Creator</title>
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
        <h1>Edit Post</h1>
        <form method="post" id="theForm" onsubmit="">
            <!--<fieldset>-->
            <table id="PostTable">
                <tbody>
                    <tr><td><label for="serviceRequired">Enter the type of service:<span class="red-star">*</span></label></td></tr>
                    <tr><td><input required="required" type="text" name="serviceRequired" id="serviceRequired" value="<?php echo $serviceType; ?>"></input></td></tr>
                    <tr><td><label for="skillsRequired">Enter the type of skill(s) you need, separated by a comma if more then one: (E.g. plumbing, accounting, baby-sitting... ) <span class="red-star">*</span></label></td></tr>
                    <tr><td><input required="required" type="text" name="skillsRequired" id="skillsRequired" value="<?php echo $skill; ?>"></input></td></tr>
                    <tr>
                        <td>
                            <form action="">
                                <legend><label for="serviceStatus">Service Status:</label></legend>
                                <div>
                                    <input type="radio" name="serviceStatus" value="Open" checked="checked"></input>
                                    <label for="Open">Open</label>
                                </div>
                                <div>
                                    <input type="radio" name="serviceStatus" value="Completed"> </input>               
                                    <label for="Completed">Completed</label>
                                </div>
                            </form>
                        </td>
                    </tr>
                    <tr><td><label for="Location">Service/Task Location : </label></td></tr>
                    <tr><td><i><?php echo $location; ?></i></td></tr>
                    <tr><td>Uploaded Images:<?php echo $images; ?></td></tr>
                    <tr><td>(Optional) Select any image(s) that help convey the post objective:
                            <form method="post" enctype="multipart/form-data">
                                <input type="file" name="fileToUpload" id="fileToUpload"></input>
                                <input type="submit" value="Upload Image" name="upload"></input>
                            </form>
                        </td></tr>
                    <tr><td><?php echo $validAddress; ?></td></tr>
                    <tr><td><label for="credit">Number of credits to award on task completion : <span class="red-star">*<?php echo $creditErr; ?></span></label></td></tr>
                    <tr><td><input type="number" required="required" name="credit" id="credit"  value="<?php echo $credit; ?>"></input></td></tr>
                    <tr><td><input type="submit" value="Update Post" id="submit" name="submit" onclick='return Validate(this.form)'></input></td></tr>         
                </tbody>
            </table>
            <!--</fieldset>-->
        </form>

        <script type="text/javascript">
            //javascript validation for the credits entered, to ensure it is above 0.
            function Validate(theForm) {
                var missing = "";
                if (theForm.credit.value < 0)
                    missing += "Credits cannot be negetive";
                if (missing !== "") {
                    alert(missing);
                    return false;
                } else
                    return true;
            }
        </script>
        <div class="footer">&copy; Time Banking Management</div>
    </body>
</html>
