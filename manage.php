<?php
session_start();

//if they aren't logged in, redirect them
if (!isset($_SESSION["User"])) {
    header("Location: login.php?location=" . urlencode($_SERVER['REQUEST_URI']));
}

if (isset($_POST['Edit'])) {
    //if edit is pressed, the page redirects to the editpost page, with the postID passed via $_POST
    $_SESSION["PostID"] = $_POST['Edit'];
    header('Location: editPost.php');
} else if (isset($_POST['Delete'])) {
    //find out how much time credit is in their account.
    $totalMoney = 0;
    $mysqli = new mysqli("mysql.cms.gre.ac.uk", "ms2721o", "D5ihHhojnjilPqFp", "mdb_ms2721o");
    $result = $mysqli->query("SELECT * FROM TimeCredit WHERE MemberID = '" . $_SESSION["User"] . "'");
    if ($result->num_rows != 0) {
        while ($row5 = $result->fetch_assoc()) {
            $totalMoney = $totalMoney + $row5["Amount"];
        }
    }
    $mysqli->close();

    $amountFromPost;
    $status;
    //delete the post using the postID
    $mysqli = new mysqli("mysql.cms.gre.ac.uk", "ms2721o", "D5ihHhojnjilPqFp", "mdb_ms2721o");
    $result = $mysqli->query("SELECT * FROM Post WHERE PostID = " . $_POST['Delete']);
    if ($result->num_rows != 0) {
        while ($row5 = $result->fetch_assoc()) {
            $amountFromPost = $row5["Reward"];
            $status = $row["Status"];
        }
    }
    $mysqli->close();

    if ($status == 0) {
        //If the post was marked as completed, the money shouldnt be returned.
    } else {
        //increase money to their account
        $mysqli = new mysqli("mysql.cms.gre.ac.uk", "ms2721o", "D5ihHhojnjilPqFp", "mdb_ms2721o");
        $result = $mysqli->query("UPDATE TimeCredit SET Amount = " . ($totalMoney + $amountFromPost) . ", Date = '" . date('Y/m/d H:i:s') . "'" . " WHERE MemberID = " . $_SESSION["User"]);
        $mysqli->close();
    }

    //Delete the post using the PostID
    $mysqli = new mysqli("mysql.cms.gre.ac.uk", "ms2721o", "D5ihHhojnjilPqFp", "mdb_ms2721o");
    $result = $mysqli->query("DELETE FROM Post WHERE PostID = " . $_POST['Delete']);
    $mysqli->close();

    //Delete all skills assosiations with the deleted post
    $mysqli = new mysqli("mysql.cms.gre.ac.uk", "ms2721o", "D5ihHhojnjilPqFp", "mdb_ms2721o");
    $result = $mysqli->query("DELETE FROM PostSkill WHERE PostID = " . $_POST['Delete']);
    $mysqli->close();

    //delete all images with the deleted post
    $mysqli = new mysqli("mysql.cms.gre.ac.uk", "ms2721o", "D5ihHhojnjilPqFp", "mdb_ms2721o");
    $result = $mysqli->query("SELECT * FROM Image WHERE PostID = " . $_POST['Delete']);
    if ($result->num_rows != 0) {
        while ($row = $result->fetch_assoc()) {
            $temp = 'uploads/' . $row["ImageSRC"];
            unlink($temp); //Deletes file form server
        }
    }
    $mysqli->close();

    //delete all image links' assosiations with the deleted post
    $mysqli = new mysqli("mysql.cms.gre.ac.uk", "ms2721o", "D5ihHhojnjilPqFp", "mdb_ms2721o");
    $result = $mysqli->query("DELETE FROM Image WHERE PostID = " . $_POST['Delete']);
    $mysqli->close();
    
//refresh the page to reflect the changes made.
    header('Location: manage.php');
}
?>

<? xml version = "1.0" encoding = "UTF-8" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html version="-//W3C//DTD XHTML 1.1//EN" xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.w3.org/1999/xhtml http://www.w3.org/MarkUp/SCHEMA/xhtml11.xsd" >
    <head>
        <meta charset="utf-8" name="viewport" content="width=device-width, initial-scale=1.0"></meta>
        <title>Manage Posts</title>
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
        <h1>Manage Posts</h1>
        <form action="" method="post">
            <table id="managePosts">
                <tbody>
                    <thead>
                        <tr>
                            <td><label>Status</label></td>
                            <td><label>Service/Task Type</label></td>
                            <td><label>Skills Required</label></td>
                            <td><label>Service/Task's Location</label></td>
                            <td><label>Reward Credits</label></td>
                            <td><label>Image(s)</label></td>
                            <td><label>Manage</label></td>
                        </tr>  
                    </thead>
                    <?php
                    //after the header of the table is created, we then programmincally create a new row for each new post 
                    
                    //First, find all posts the member owns.
                    $data = array(); // create a variable to hold the information
                    $mysqli = new mysqli("mysql.cms.gre.ac.uk", "ms2721o", "D5ihHhojnjilPqFp", "mdb_ms2721o");
                    $result = $mysqli->query("SELECT * FROM Post WHERE MemberID = " . $_SESSION["User"]);
                    if ($result->num_rows != 0) {
                        while ($row = $result->fetch_assoc()) {
                            $data[] = $row; // add the row in to the results (data) array
                        }
                    }
                    $mysqli->close();

                    //Then, for each post, programmically create a row of the table and fill it wil the post infomation. 
                    foreach ($data as &$array) {
                        //post status is a boolean value (1 or 0)
                        if ($array["Status"] == 1) {
                            $status = "<font color='green'>Open</font>";
                        } else {
                            $status = "<font color='red'>Completed</font>";
                        }

                        //get the post title.
                        $service = $array["ServiceType"];

                        //get all skillID's assosiated with the post
                        $Postskills = array();
                        $mysqli = new mysqli("mysql.cms.gre.ac.uk", "ms2721o", "D5ihHhojnjilPqFp", "mdb_ms2721o");
                        $result = $mysqli->query("SELECT * FROM PostSkill WHERE PostID = " . $array["PostID"]);
                        if ($result->num_rows != 0) {
                            while ($row2 = $result->fetch_assoc()) {
                                $Postskills[] = $row2;
                            }
                        }
                        $mysqli->close();
                        
                        //for each skillID, we get the skill name and add them to an unordered list.
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

                        //The locationID assosiated to the post is retreived, and used to get the address.
                        $location = $array["LocationID"];
                        $mysqli = new mysqli("mysql.cms.gre.ac.uk", "ms2721o", "D5ihHhojnjilPqFp", "mdb_ms2721o");
                        $result = $mysqli->query("SELECT Address FROM Location WHERE LocationID = " . $location);
                        if ($result->num_rows != 0) {
                            while ($rowtemp = $result->fetch_assoc()) {
                                $location = $rowtemp["Address"];
                            }
                        }
                        $mysqli->close();

                        //the number of credits the post offers.
                        $credits = $array["Reward"];

                        //each image assosiated with the post is retreived and added to a <img> element
                        $mysqli = new mysqli("mysql.cms.gre.ac.uk", "ms2721o", "D5ihHhojnjilPqFp", "mdb_ms2721o");
                        $result = $mysqli->query("SELECT ImageSRC FROM Image WHERE PostID = " . $array["PostID"]);
                        $images = "To add images to your post, click Edit";
                        if ($result->num_rows != 0) {
                            $images = "";
                            while ($rowtemp = $result->fetch_assoc()) {
                                $images .= '<img src="uploads/' . $rowtemp["ImageSRC"] . '" alt="Post Image" width="50" height="50">';
                            }
                        }
                        $mysqli->close();

                        //each post has options assosiated with it, each option is a button, which has a unique name, but all the buttons have the same value (the postID).
                        $options = "<button class='button' name='Edit' value='" . $array["PostID"] . "'>Edit</button>"
                                . "<button class='button' name='Delete' value='" . $array["PostID"] . "'>Delete</button>";

                        //The row of the table is generated, with each colomn corresponding to the header.
                        echo "<tr>";
                        echo "<td>$status</td>";
                        echo "<td>$service</td>";
                        echo "<td><ul style='list-style-type:none'>$skills</ul></td>";
                        echo "<td>$location</td>";
                        echo "<td>$credits</td>";
                        echo "<td>$images</td>";
                        echo "<td>$options</td>";
                        echo "</tr>";
                        unset($skills);
                    }
                    ?>
                </tbody>
            </table>
        </form>
        <div class="manageFooter">&copy; 2018 - Time Banking Management</div>
    </body>
</html>
