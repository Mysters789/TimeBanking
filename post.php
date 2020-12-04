<?php
session_start();

//The default message if the PostID points to a non-existant post
$post = "Post couldn't be found :(";

//if the user isn't logged in, then we will redirect them to the login form to log in first
if (!isset($_SESSION["User"])) {
    header("Location: login.php?location=" . urlencode($_SERVER['REQUEST_URI']));
}

//This will get the post specficed by the URL
$data = array(); // create a variable to hold the information
$mysqli = new mysqli("mysql.cms.gre.ac.uk", "ms2721o", "D5ihHhojnjilPqFp", "mdb_ms2721o");
$result = $mysqli->query("SELECT * FROM Post WHERE PostID = " . test_input($_GET['post']));
if ($result->num_rows != 0) {
    unset($post);
    while ($row = $result->fetch_assoc()) {
        $data[] = $row; // add the row in to the results (data) array
    }
}
$mysqli->close();

//This loops through and prints all posts returned (1 usually)
foreach ($data as &$array) {
    
    //status is a boolean value, so it is either 1 or 0
    if ($array["Status"] == 1) {
        $status = '<font color="green">Open</font>';
    } else {
        $status = '<font color="red">Completed</font>';
    }

    //service type is the post title
    $service = $array["ServiceType"];

    //the post will have skills assossiated with it, and so this statement retrives the skillID of all those skills 
    $Postskills = array();
    $mysqli = new mysqli("mysql.cms.gre.ac.uk", "ms2721o", "D5ihHhojnjilPqFp", "mdb_ms2721o");
    $result = $mysqli->query("SELECT * FROM PostSkill WHERE PostID = " . $array["PostID"]);
    if ($result->num_rows != 0) {
        while ($row2 = $result->fetch_assoc()) {
            $Postskills[] = $row2;
        }
    }
    $mysqli->close();

    //Using the collected skillID's, we find the skill name one by one and add it to an array for use later
    foreach ($Postskills as &$eachSkill) {
        $mysqli = new mysqli("mysql.cms.gre.ac.uk", "ms2721o", "D5ihHhojnjilPqFp", "mdb_ms2721o");
        $result = $mysqli->query("SELECT * FROM Skill WHERE SkillID = " . $eachSkill["SkillID"]);
        if ($result->num_rows != 0) {
            while ($rowtemp = $result->fetch_assoc()) {
                $skills .= "<li>" . $rowtemp["Skill"] . "</li>";
            }
        }
        $mysqli->close();
    }

    //the post will have a memberID assigned to it
    $username = $array["MemberID"];
    //using the memberID, we find the member username to be displayed with the post
    $mysqli = new mysqli("mysql.cms.gre.ac.uk", "ms2721o", "D5ihHhojnjilPqFp", "mdb_ms2721o");
    $result = $mysqli->query("SELECT * FROM Member WHERE MemberID = " . $username);
    if ($result->num_rows != 0) {
        while ($rowtemp = $result->fetch_assoc()) {
            $username = $rowtemp["Username"];
        }
    }
    $mysqli->close();

    //The post will have a locationID assossiated with it, so we will get that
    $location = $array["LocationID"];
    //using the locationID, we can find the name of the location in the appropriate table
    $mysqli = new mysqli("mysql.cms.gre.ac.uk", "ms2721o", "D5ihHhojnjilPqFp", "mdb_ms2721o");
    $result = $mysqli->query("SELECT Address FROM Location WHERE LocationID = " . $location);
    if ($result->num_rows != 0) {
        while ($rowtemp = $result->fetch_assoc()) {
            $location = $rowtemp["Address"];
        }
    }
    $mysqli->close();
    
//the amount of credits the post rewards
    $credits = $array["Reward"];

    //Using the PostID, the Image table will have all the images that are assosiated with that post
    $mysqli = new mysqli("mysql.cms.gre.ac.uk", "ms2721o", "D5ihHhojnjilPqFp", "mdb_ms2721o");
    $result = $mysqli->query("SELECT ImageSRC FROM Image WHERE PostID = " . $array["PostID"]);
    if ($result->num_rows != 0) {
        $images = "";
        //for all images returned, we create a new img tag pointing to the address on the server where it is located. The address is saved in the database
        while ($rowtemp = $result->fetch_assoc()) {
            $images .= '<div><img src="uploads/' . $rowtemp["ImageSRC"] . '" alt="Post Image"></div>';
        }
    }
    $mysqli->close();

    //finally we print out the results in a neat manner
    echo "<div class='post'>";
    $post .= "<h1>$service</h1>";
    $post .= "<h6>$username</h6>";
    $post .= "<h3>Status : $status</h3>";
    $post .= "<h3>Location : $location</h3>";
    $post .= "<h3>Reward : $credits</h3>";
    $post .= "<h4>Skills Required :<ul style='list-style-type:none'>$skills</ul></h4>";
    $post .= "<div>$images</div>";
    $post .= "</div>";
    //we unset the skills incase there are more then one post, else we get erronous results for procedding posts
    unset($skills);
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
        <link rel="stylesheet" href="css/styles.css"></link>
        <link rel="shortcut icon" href="favicon.png"></link>
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
        <?php echo $post; ?>
        <div class="footer">&copy; 2018 - Time Banking Management</div>
    </body>
</html>
