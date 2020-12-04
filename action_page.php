<?php
$service;
$status;
$location;
$credits;
$skills;
$options;
$searchresults;
$NumberOfResults;
$i;
$j;
$i2;
//the last zsearched term is saved in cookies, make sure it is applied when the page loads.
$search = $_COOKIE['SEARCH'];

function test_input($data) {
    //a method for preventing XSS and SQL injection attacks.
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

//get the search term from the URL via $_GET.
$search = test_input($_GET['search']);
//save this search term in a cookie.
setcookie("SEARCH", $search);


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    //when the user wants to refine the search, the URL is used to sent the parameters.
    $url = "action_page.php?";

    //if the search feild isn't empty, filter the search to have skills or location that are similar to the entered search term
    if (isset($_GET['search'])) {
        $search = test_input($_GET['search']);
        $url .= '&search=' . $search;
        setcookie("SEARCH", $search);
    }

//if only images are desired
    if (isset($_POST['ImagesOnly'])) {
        $url .= '&ImagesOnly=' . 1;
    }

    //if both the location and the distance radius are set
    if (isset($_POST['LOC']) && !empty($_POST['LOC']) && !empty($_POST['KM']) && isset($_POST['KM'])) {
        $LOC = test_input($_POST['LOC']);
        $KM = test_input($_POST['KM']);
        $url .= '&location=';
        $url .= $LOC;
        $url .= '&km=';
        $url .= $KM;
    }

    //if the results should be displayed in asending or desending order
    $answer = $_POST['order'];
    if ($answer == "asc") {
        $url .= '&order=ASC';
    } else if ($answer == "dsc") {
        $url .= '&order=DSC';
    }

    //redirect to the newly created url, which will show the result, but filtered.
    header('Location: ' . $url);
}
?>

<? xml version = "1.0" encoding = "UTF-8" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html version="-//W3C//DTD XHTML 1.1//EN" xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.w3.org/1999/xhtml http://www.w3.org/MarkUp/SCHEMA/xhtml11.xsd">

    <head>
        <meta charset="utf-8" name="viewport" content="width=device-width, initial-scale=1.0"></meta>
        <title>Search Results</title>
        <link rel="stylesheet" href="css/styles.css"></link>
        <link rel="shortcut icon" href="favicon.PNG"></link>
    </head>
    <body>
        <a href="index.php">
            <div class="menu-ico-logo">
            </div>
        </a>
        <?php
        include('CookiePolicy.php');
        ?>
        <h1>Search Results</h1>
        <div class="wrapmini">
            <form action="action_page.php" method="get">
                <input type="text" class="searchTermmini" value="<?php echo $search; ?>" name="search" placeholder="Search..."></input>
                <button type="submit" class="searchButtonmini"><b class="fa fa-search">⚲</b></button>
            </form>   
        </div>

        <div class="leftNav">
            <form method="post" id="theForm">
                <ul style="list-style-type:none">
                    <?php
                    //if the ordering of the posts was set, the webpage will show which option was set
                    $answer = $_GET['order'];
                    echo "<li>";
                    if ($answer == 'ASC') {
                        echo '<input type="radio" name="order" checked="checked" value="asc">Asending Order</input>';
                    } else {
                        echo '<input type="radio" name="order" value="asc">Asending Order</input>';
                    }
                    echo "</li>";
                    echo "<li>";
                    if ($answer == 'DSC') {
                        echo '<input type="radio" name="order" checked="checked" value="dsc">Desending Order</input>';
                    } else {
                        echo '<input type="radio" name="order" value="dsc">Desending Order</input>';
                    }
                    echo "</li>";
                    ?>
                </ul>

                <div>
                    <?php
                    //if only image posts were choosen, the webpage will show the box checked.
                    if (isset($_GET['ImagesOnly'])) {
                        echo "<input type='checkbox' checked='checked' name='ImagesOnly'>Include posts with images only";
                    } else {
                        echo "<input type='checkbox' name='ImagesOnly'>Include posts with images only";
                    }
                    ?>
                </div>

                <Label> KM radius distance :</Label>
                <input type="number" name="KM" value="<?php echo $variable = test_input($_GET['km']); ?>"></input>
                <Label> From (Location):</Label>
                <input type="text" name="LOC" value="<?php echo $variable = test_input($_GET['location']); ?>"></input>
                <button class="button" name="Refine"><b>Refine</b></button>
            </form>
        </div>

        <table name="Results">
            <?php

            //a method for finding the distance between two GPS coordinates.
            function distance($lat1, $lon1, $lat2, $lon2, $unit) {
                $theta = $lon1 - $lon2;
                $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
                $dist = acos($dist);
                $dist = rad2deg($dist);
                $miles = $dist * 60 * 1.1515;
                $unit = strtoupper($unit);

                if ($unit == "K") {
                    return ($miles * 1.609344);
                } else if ($unit == "N") {
                    return ($miles * 0.8684);
                } else {
                    return $miles;
                }
            }

            //a method for sorting a array full of arrays, using the arrays ID e.g. sort by array["surname"]
            function array_sort_by_column(&$arr, $col, $dir) {
                $sort_col = array();
                foreach ($arr as $key => $row) {
                    $sort_col[$key] = $row[$col];
                }
                array_multisort($sort_col, $dir, $arr);
            }

            $skillsArray = array();
            $postIDarray = array();
            $locationarray = array();
            $searchresults = array();

            //FILTER OPTIONS
            $withinRadius = array(); //holds all locations that are within range
            $postWithImages = array(); //holds all postID's with images
            $filteredPostIDs = array();

            //if the location and distance radius was specified,  get all posts that are within this distance.
            if (isset($_GET['location']) && isset($_GET['km'])) {
                $lat1;
                $lon1;
// query google for the location entered and get the GPS coordinates from it.
                $query = "https://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode(test_input($_GET['location'])) . "&sensor=false&key=AIzaSyAVQE7J6YQVGyXYQ0e0RAW6flI4-egsP8M";
                $json = json_decode(file_get_contents($query), TRUE);
                if (count($json) > 0) {
                    $lat1 = $json["results"][0]["geometry"]["location"]["lat"];
                    $lon1 = $json["results"][0]["geometry"]["location"]["lng"];
                } else {
                    $locationerr = "No valid address found. Try again.";
                }

                //get all locations from all posts.
                $temp = array();
                $mysqli = new mysqli("mysql.cms.gre.ac.uk", "ms2721o", "D5ihHhojnjilPqFp", "mdb_ms2721o");
                $result = $mysqli->query("SELECT * FROM Location");
                if ($result->num_rows != 0) {
                    while ($row = $result->fetch_assoc()) {
                        $temp[] = $row;
                    }
                }

                //for each location, query google for it's GPS coordinates, if the distance is within the raduis, add it to an array to be compared later.
                foreach ($temp as &$address) {
                    $query = "https://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode($address["Address"]) . "&sensor=false&key=AIzaSyAVQE7J6YQVGyXYQ0e0RAW6flI4-egsP8M";
                    $json = json_decode(file_get_contents($query), TRUE);
                    $lat2 = $json["results"][0]["geometry"]["location"]["lat"];
                    $lon2 = $json["results"][0]["geometry"]["location"]["lng"];
                    if (distance($lat1, $lon1, $lat2, $lon2, "K") <= test_input($_GET['km'])) {
                        $withinRadius[] = $address["LocationID"];
                    }
                }
            }

            //if only image-having posts was checked, find all unique PostID's using the Image table.
            if (isset($_GET['ImagesOnly'])) {
                $temp2 = array();
                $mysqli = new mysqli("mysql.cms.gre.ac.uk", "ms2721o", "D5ihHhojnjilPqFp", "mdb_ms2721o");
                $result = $mysqli->query("SELECT * FROM Image");
                if ($result->num_rows != 0) {
                    while ($row = $result->fetch_assoc()) {
                        $temp2[] = $row["PostID"];
                    }
                }
                $postWithImages = array_unique($temp2);

                unset($temp2);
            }

            //Now we have all the posts from all the different filters, lets colelct them together and get a comprehsive list
            $temp3 = array();
            foreach ($withinRadius as &$locationID) {
                $mysqli = new mysqli("mysql.cms.gre.ac.uk", "ms2721o", "D5ihHhojnjilPqFp", "mdb_ms2721o");
                $result = $mysqli->query("SELECT * FROM Post WHERE LocationID = " . $locationID);
                if ($result->num_rows != 0) {
                    while ($row = $result->fetch_assoc()) {
                        $temp3[] = $row["PostID"];
                    }
                }
            }

            foreach ($postWithImages as &$PostID) {
                $temp3[] = $PostID;
            }

            $filteredPostIDs = array_unique($temp3);


            //now we have all posts that match the refined filters, lets find skillIDs that match the search term.
            $mysqli = new mysqli("mysql.cms.gre.ac.uk", "ms2721o", "D5ihHhojnjilPqFp", "mdb_ms2721o");
            $result = $mysqli->query("SELECT * FROM Skill WHERE Skill LIKE '%" . test_input($_GET['search']) . "%'");
            if ($result->num_rows != 0) {
                while ($row = $result->fetch_assoc()) {
                    $skillsArray[] = $row;
                }
            }
            $mysqli->close();

            //for each skillID, find the skill names.
            foreach ($skillsArray as &$array) {
                $mysqli = new mysqli("mysql.cms.gre.ac.uk", "ms2721o", "D5ihHhojnjilPqFp", "mdb_ms2721o");
                $result = $mysqli->query("SELECT * FROM PostSkill WHERE SkillID = " . $array["SkillID"]);
                if ($result->num_rows != 0) {
                    while ($row = $result->fetch_assoc()) {
                        $postIDarray[] = $row["PostID"];
                    }
                }
                $mysqli->close();
            }

            //find all locations that match the search term.
            $mysqli = new mysqli("mysql.cms.gre.ac.uk", "ms2721o", "D5ihHhojnjilPqFp", "mdb_ms2721o");
            $result = $mysqli->query("SELECT * FROM Location WHERE Address LIKE '%" . test_input($_GET['search']) . "%'");
            if ($result->num_rows != 0) {
                while ($row = $result->fetch_assoc()) {
                    $locationarray[] = $row;
                }
            }

            foreach ($locationarray as &$array2) {
                $mysqli = new mysqli("mysql.cms.gre.ac.uk", "ms2721o", "D5ihHhojnjilPqFp", "mdb_ms2721o");
                $result = $mysqli->query("SELECT * FROM Post WHERE LocationID = " . $array2["LocationID"]);
                if ($result->num_rows != 0) {
                    while ($row = $result->fetch_assoc()) {
                        $postIDarray[] = $row["PostID"];
                    }
                }
                $mysqli->close();
            }

            //now we have 2 arrays. 1 full of all PostID's that match the search term, the other full of PostID's
            //that match the filtered conditions. We must eliminate all PostID's that aren't common in both arrays!
            if (count($filteredPostIDs) > 0) {
                foreach ($postIDarray as &$PosttID) {
                    if (in_array($PosttID, $filteredPostIDs)) {
                        //Do nothing
                    } else {
                        //delete it!
                        $del_val = $PosttID;
                        if (($key = array_search($del_val, $postIDarray)) !== false) {
                            unset($postIDarray[$key]);
                        }
                    }
                }
            }

            //ensure we only have unqiue values for PostID's
            $uniquePostIDs = array_unique($postIDarray);

            //for each PostID in the array we made, get the posts and put them in an array
            foreach ($uniquePostIDs as &$array3) {
                $mysqli = new mysqli("mysql.cms.gre.ac.uk", "ms2721o", "D5ihHhojnjilPqFp", "mdb_ms2721o");
                $result = $mysqli->query("SELECT * FROM Post WHERE PostID = " . $array3);
                if ($result->num_rows != 0) {
                    while ($row = $result->fetch_assoc()) {
                        $searchresults[] = $row;
                    }
                }
                $mysqli->close();
            }

            //in that array full of posts, order them if required.
            if (isset($_GET['order'])) {
                if (test_input($_GET['order']) == "ASC") {
                    array_sort_by_column($searchresults, 'ServiceType', SORT_ASC);
                } else {
                    array_sort_by_column($searchresults, 'ServiceType', SORT_DESC);
                }
            }

            //pageination will occur every 10 results.
            $i = 0; //the starting result
            $i2 = 10; //the last result before paganation occurs
            //if there are less then 10 results, just make the value to size of the results array.
            if ($i2 > count($searchresults)) {
                $i2 = count($searchresults);
            }

            //depending on the page number, we adjust the values of the first and last value of the results array we are going to display.
            if (isset($_GET['Page']) && !empty($_GET['Page'])) {
                $i = (test_input($_GET['Page']) - 1) * 10;
                $i2 = $i + 10;
                if ($i2 > count($searchresults)) {
                    $i2 = count($searchresults);
                }
            }

            //used for pagination options at the bottom of the page, as $i is used in the for loop below.
            $j = $i;

            //the for loop will only loop and display results depending on the page number.
            for ($i; $i < $i2; $i++) {
                //post status is boolean, either 1 or 0.
                if ($searchresults[$i]["Status"] == 1) {
                    $status = '<font color="green">Open</font>';
                } else if ($searchresults[$i]["Status"] == 0) {
                    $status = '<font color="red">Completed</font>';
                }

                //title of the post
                $service = $searchresults[$i]["ServiceType"];

                //gathering all the SkillID's assosialted with the post.
                $Postskills = array();
                $mysqli = new mysqli("mysql.cms.gre.ac.uk", "ms2721o", "D5ihHhojnjilPqFp", "mdb_ms2721o");
                $result = $mysqli->query("SELECT * FROM PostSkill WHERE PostID = " . $searchresults[$i]["PostID"]);
                if ($result->num_rows != 0) {
                    while ($row2 = $result->fetch_assoc()) {
                        $Postskills[] = $row2;
                    }
                }
                $mysqli->close();

                //for each gathered SkillID, get the skill.
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

                //get the locationID, and use it to get the Post location
                $location = $searchresults[$i]["LocationID"];

                $mysqli = new mysqli("mysql.cms.gre.ac.uk", "ms2721o", "D5ihHhojnjilPqFp", "mdb_ms2721o");

                $result = $mysqli->query("SELECT Address FROM Location WHERE LocationID = " . $location);
                if ($result->num_rows != 0) {
                    while ($rowtemp = $result->fetch_assoc()) {
                        $location = $rowtemp["Address"];
                    }
                }
                $mysqli->close();

                //display the reward the post offers
                $credits = $searchresults[$i]["Reward"];

                //give vistors the option to see the post in it's full context.s
                $options = '<a href="post.php?post=' . $searchresults[$i]["PostID"] . '">View Post</a>';

                //echo the HTML required to display the post in a breif manner.
                echo "<tr><td>";
                echo "<h2>$service</h2>";
                echo "<h3>Status : $status</h3>";
                echo "<h4>Location : $location</h4>";
                echo "<h4>Reward : $credits</h4>";
                echo "<h5>Skills Required :<ul style='list-style-type:none'>$skills</ul></h5>";
                echo "<div>$options</div>";
                echo "</td></tr>";
                //<!> important to unset the skills variable, as each iteration of loop will add the the skills variable so it needs to be emptied each time.
                unset($skills);
            }
            ?>
        </table>

        <div class="manageFooter">
            <div>
                <?php
                //the following code is used to generate the <<previous page button.
                $NumberOfResults = "Showing results " . $i2 . " / " . count($searchresults);
                echo $NumberOfResults;
                $options = "";
                if ($j > 0) {
                    $options .= '<a href="action_page.php?';
                    if (isset($_GET['search'])) {
                        $options .= '&search=' . test_input($_GET['search']);
                    }
                    if (isset($_GET['ImagesOnly'])) {
                        $options .= '&ImagesOnly=' . 1;
                    }
                    if (isset($_GET['location']) && isset($_GET['km'])) {
                        $options .= '&location=' . test_input($_POST('location')) . '&km=' . test_input($_POST('km'));
                    }
                    $answer = $_GET['order'];
                    if ($answer == "ASC") {
                        $options .= '&order=ASC';
                    } else if ($answer == "DSC") {
                        $options .= '&order=DSC';
                    }
                    $options .= '&Page=' . round(($i - 11) / 10) . '"> << Previous Page</a>';
                }

                $options .= "  ";

                if ($i2 < count($searchresults)) {
                    //the following code is used to generate the next page>> button.
                    $options .= '<a href="action_page.php?';
                    if (isset($_GET['search'])) {
                        $options .= '&search=' . test_input($_GET['search']);
                    }
                    if (isset($_GET['ImagesOnly'])) {
                        $options .= '&ImagesOnly=' . 1;
                    }
                    if (isset($_GET['location']) && isset($_GET['km'])) {
                        $options .= '&location=' . test_input($_POST('location')) . '&km=' . test_input($_POST('km'));
                    }
                    $answer = $_GET['order'];
                    if ($answer == "ASC") {
                        $options .= '&order=ASC';
                    } else if ($answer == "DSC") {
                        $options .= '&order=DSC';
                    }
                    $options .= '&Page=' . (round($i2 / 10) + 1) . '">Next Page >> </a>';
                }
                echo $options;
                ?>
            </div>
            <div>
                &copy; 2018 - Time Banking Management
            </div>
        </div>
    </body>

</html>
