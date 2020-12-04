<?php
session_start();
$serviceErr;
$statusErr;
$skillErr;
$locationErr;
$creditErr;
$status;
$last_id;
$postID;
$images;
$LocationID;
$validAddress;

//Any images stored in the session are automatically loaded.
for ($i = 0; $i < count($_SESSION['ImagesUploaded']); $i++) {
    $images .= '<img src="uploads/' . $_SESSION['ImagesUploaded'][$i] . '" alt="Uploaded Image" width="100" height="100">';
    $images .= "<button class='button' name='Delete' value='" . $i . "'>Delete</button>";
}

//if the person hasen't logged in, redirect them.
if (!isset($_SESSION["Username"])) {
    header("Location: login.php?location=" . urlencode($_SERVER['REQUEST_URI']));
}

if (!isset($_SESSION["User"])) {
    header("Location: login.php?location=" . urlencode($_SERVER['REQUEST_URI']));
}

//if the person presses delete, delete the corresponding image that delete button was assosiated to.
if (isset($_POST['Delete'])) {
    unset($_SESSION['ImagesUploaded'][$_POST['Delete']]);
    header('Location: createPost.php');
}

//if the person presses upload, upload the image into a temp folder, add the image location into the image session variable, and reload the page.
if (isset($_POST['upload'])) {
    $fna = explode('.', $_FILES['fileToUpload']['name']);
    $ext = $fna[count($fna) - 1];
    if (!preg_match('/gif|png|jpeg|bmp/', $_FILES['fileToUpload']['type'])) {
        echo "<p><strong>Sorry, only browser compatible images allowed</strong></p>";
    } else if (!preg_match('/gif|png|jpg|jpeg|bmp/', $ext)) {
        echo "<p><strong>Sorry, only browser compatible images allowed</strong></p>";
    } else {
        // rename and copy the file to the uploads directory
        $filename = $_SESSION["Username"] . "_" . time() . "." . $ext;
        if (copy($_FILES['fileToUpload']['tmp_name'], "uploads/$filename")) {
            $_SESSION['ImagesUploaded'][] = $filename;
        } else {
            echo "<p><strong>Error: failed to copy file</strong></p>";
        }
    }
    header('Location: createPost.php');
}

//if the person presses find, Google's API is queried to find the location
if (isset($_POST['Find'])) {
    //save all the entered values of the other text feilds as the page will reload.
    $_SESSION["serviceRequired"] = $_POST["serviceRequired"];
    $_SESSION["skillsRequired"] = $_POST["skillsRequired"];
    $_SESSION["Location"] = $_POST["Location"];
    $_SESSION["credit"] = $_POST["credit"];
    //send the query.
    $query = "https://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode(test_input($_POST["Location"])) . "&sensor=false&key=AIzaSyAVQE7J6YQVGyXYQ0e0RAW6flI4-egsP8M";
    //the query will return a JSON file. go through the array and get all the possible locations that Google found that match the entered one. 
    //For each result, add it to a dropdown list, for the person to choose with one it matches
    $json = json_decode(file_get_contents($query), TRUE);
    if (count($json) > 0) {
        $validAddress = "<select id='place' name='place'>";
        for ($i = 0; $i < count($json["results"]); $i++) {
            $validAddress .= '<option value="' . $json["results"][$i]["formatted_address"] . '">' . $json["results"][$i]["formatted_address"] . '</option>';
        }
        $validAddress .= "</select>";
    } else {
        $validAddress = "No valid address found. Try again.";
    }
} else if (isset($_POST['submit'])) {
    //Check if enough credits are available
    $totalMoney = 0;
    $mysqli = new mysqli("mysql.cms.gre.ac.uk", "ms2721o", "D5ihHhojnjilPqFp", "mdb_ms2721o");
    $result = $mysqli->query("SELECT * FROM TimeCredit WHERE MemberID = '" . $_SESSION["User"] . "'");
    if ($result->num_rows != 0) {
        while ($row5 = $result->fetch_assoc()) {
            $totalMoney = $totalMoney + $row5["Amount"];
        }
    }
    $mysqli->close();

    //if the person has choosen a location from the generated dropdown list, the proceed
    if (isset($_POST['place'])) {
        if (test_input($_POST["credit"]) <= $totalMoney && test_input($_POST["credit"]) > 0) {
            //Deduct from their account, as the creation of the post should do so.
            $mysqli = new mysqli("mysql.cms.gre.ac.uk", "ms2721o", "D5ihHhojnjilPqFp", "mdb_ms2721o");
            $result = $mysqli->query("UPDATE TimeCredit SET Amount = " . ($totalMoney - test_input($_POST["credit"])) . ", Date = '" . date('Y/m/d H:i:s') . "'" . " WHERE MemberID = " . $_SESSION["User"]);
            $mysqli->close();

            //service status is boolean.
            if ($_POST["serviceStatus"] == "Open") {
                $status = 1;
            } else {
                $status = 0;
            }
            
            //create the post with some actual values, along with some temporoary values which we update later on.
            $sql = "INSERT INTO Post (MemberID, ServiceType, Status, LocationID, Reward) VALUES ('" . $_SESSION["User"] . "','" . test_input($_POST["serviceRequired"]) . "'," . $status . ",-1," . test_input($_POST["credit"]) . ")";
            $postID = insertIntoTableQuery($sql);
            $_SESSION["PostID"] = $postID;

            //lets update the location to the correct one.
            //if the location is a brand new one we haven't seen before, add it to the lcoation table. After we get the locationID.
            //if the location is a previously known one, we just get the lcoationID assosicated with it.
            $mysqli = new mysqli("mysql.cms.gre.ac.uk", "ms2721o", "D5ihHhojnjilPqFp", "mdb_ms2721o");
            $result = $mysqli->query("SELECT LocationID FROM Location WHERE Address = '" . test_input($_POST["place"]) . "'");
            if ($result->num_rows == 0) {
                $sql = "INSERT INTO Location (Address) VALUES ('" . test_input($_POST["place"]) . "')";
                $LocationID = insertIntoTableQuery($sql);
            } else {
                while ($row = $result->fetch_assoc()) {
                    $LocationID = $row["LocationID"];
                }
            }
            $mysqli->close();

            $mysqli = new mysqli("mysql.cms.gre.ac.uk", "ms2721o", "D5ihHhojnjilPqFp", "mdb_ms2721o");
            $result = $mysqli->query("UPDATE Post SET LocationID = " . $LocationID . " WHERE PostID = " . $postID);
            $mysqli->close();

            //Now lets add the skills the post requires
            $_SESSION["skillsRequired"] = test_input($_POST["skillsRequired"]);
            //if skills are given, split by comma into an array
            $skills = explode(",", test_input($_POST["skillsRequired"]));
            array_filter($skills);
            foreach ($skills as &$value) {
                //then iterate through each value trimming and neatening up each value
                $value = test_input($value);
            }
            // break the reference with the last element
            unset($value);

            foreach ($skills as &$value) {
                //for each skill, find the skillID assosiated with it. If the skill is new we must make sure to add the skill to the Skills Table, then get the SkillID.
                $mysqli = new mysqli("mysql.cms.gre.ac.uk", "ms2721o", "D5ihHhojnjilPqFp", "mdb_ms2721o");
                $result = $mysqli->query("SELECT SkillID FROM Skill WHERE Skill = '" . $value . "'");
                if ($result->num_rows == 0) {
                    $sql = "INSERT INTO Skill (Skill) VALUES ('" . $value . "')";
                    $SkillID = insertIntoTableQuery($sql);
                } else {
                    while ($row = $result->fetch_assoc()) {
                        $SkillID = $row["SkillID"];
                    }
                }
                $mysqli->close();
                //We then add the skillID along with the PostID to the PostSkill table, which is responsible for linking posts with their skills requried.
                $sql = "INSERT INTO PostSkill (PostID, SkillID) VALUES (" . $postID . ", " . $SkillID . ")";
                $SkillID = insertIntoTableQuery($sql);
            }
            unset($value);

            foreach ($_SESSION['ImagesUploaded'] as &$value) {
                //we iterate through the uploaded images, and add them to the post.
                $sql = "INSERT INTO Image (PostID, ImageSRC) VALUES (" . $_SESSION["PostID"] . ",'" . $value . "')";
                insertIntoTableQuery($sql);
                // set group rw permission to allow deleting of the upload
                if (chmod("uploads/$filename", 0664)) {
                    echo "<p><strong>File successfully uploaded</strong></p>\n";
                } else {
                    echo "<p><strong>Error: failed to chmod file</strong></p>";
                }
            }

            //We unset this incase another post is created in the future
            unset($_SESSION['ImageUploaded']);

            //redirect to post management page
            header('Location: manage.php');
        } else if (test_input($_POST["credit"]) > $totalMoney) {
            $creditErr = "The amount of credits you entered exceed the amount you have in your account!";
        } else if (test_input($_POST["credit"]) < 0) {
            $creditErr = "You are not allowed to enter a negetive amount of credits!";
        }
    } else {
        $locationErr = "You haven't choosen an address from the drop down list. To see the dropdown list, you must enter a valid location and press 'Find'";
    }
}

function insertIntoTableQuery($sql) {
    //conveinient method for SQL INSERT queries
    $conn = mysqli_connect("mysql.cms.gre.ac.uk", "ms2721o", "D5ihHhojnjilPqFp", "mdb_ms2721o");
    if (mysqli_query($conn, $sql)) {
        //returns the ID of the inserted colomn
        return mysqli_insert_id($conn);
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
        <h1>Create Post</h1>
        <form method="post" id="theForm" onsubmit="">
            <!--<fieldset>-->
            <table id="PostTable">
                <tbody>
                    <tr><td><label for="serviceRequired">Enter the type of service:<span class="red-star">*</span></label></td></tr>
                    <tr><td><input required="required" type="text" name="serviceRequired" id="serviceRequired" value="<?php echo $_SESSION["serviceRequired"]; ?>"></input></td></tr>
                    <tr><td><label for="skillsRequired">Enter the type of skill(s) you need, separated by a comma if more then one: (E.g. plumbing, accounting, baby-sitting... ). Enter none if no skills are required. <span class="red-star">*</span></label></td></tr>
                    <tr><td><input required="required" type="text" name="skillsRequired" id="skillsRequired" value="<?php echo $_SESSION["skillsRequired"]; ?>"></input></td></tr>
                    <tr><td><form action="">
                                <!--<fieldset>-->
                                <legend><label for="serviceStatus">Service Status:</label></legend>
                                <div>
                                    <input type="radio" name="serviceStatus" value="Open" checked="checked"></input>
                                    <label for="Open">Open</label>
                                </div>
                                <div>
                                    <input type="radio" name="serviceStatus" value="Completed"> </input>               
                                    <label for="Completed">Completed</label>
                                </div>
                                <!--</fieldset>-->
                            </form></td></tr>
                    <tr><td><label for="Location">Service/Task Location : </label></td></tr>
                    <tr>
                        <td>
                            <input required="required" type="text" name="Location" id="Location" value="<?php echo $_SESSION["Location"]; ?>"><span class="red-star">*<?php echo $locationErr; ?></span></input>
                            <button class='button' name='Find' value='Find'>Find</button>
                        </td>
                    </tr>    
                    <tr><td><?php echo $validAddress; ?></td></tr>
                    <tr><td>Uploaded Images:<?php echo $images; ?></td></tr>
                    <tr><td>(Optional) Select any image(s) that help convey the post objective:
                            <form method="post" enctype="multipart/form-data">
                                <input type="file" name="fileToUpload" id="fileToUpload"></input>
                                <input type="submit" value="Upload Image" name="upload"></input>
                            </form>
                        </td></tr>

                    <tr><td><label for="credit">Number of credits to award on task completion : <span class="red-star">*<?php echo $creditErr; ?></span></label></td></tr>
                    <tr><td><input type="number" required="required" name="credit" id="credit"  value="<?php echo $_SESSION["credit"]; ?>"></input></td></tr>
                    <tr>
                        <td>
                            <button class='button' name='submit' value='submit' onclick='return Validate(this.form)'>Create Post</button>
                        </td>
                    </tr>
                </tbody>
            </table>
            <!--</fieldset>-->
        </form>

        <script type="text/javascript">
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
        <div class="footer">&copy; 2018 - Time Banking Management</div>
    </body>
</html>
<a href="post.php"></a>