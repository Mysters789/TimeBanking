<?php
$userName;
$passWord;
$last_id;
$userNameErr;
$memberID;
$home;
$search = $_COOKIE['SEARCH'];
error_reporting(E_Error);
session_start();

//enforce HTTPS to ensure the login form is secure
if ($_SERVER["HTTPS"] != "on") {
    header("Location: https://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]);
    exit();
}

if (isset($_SESSION["User"])) {
    //If the person has logged it, the session variable user will be set, so find the amount of time credits the have
    $totalMoney = 0;
    $mysqli = new mysqli("mysql.cms.gre.ac.uk", "ms2721o", "D5ihHhojnjilPqFp", "mdb_ms2721o");
    $result = $mysqli->query("SELECT * FROM TimeCredit WHERE MemberID = '" . $_SESSION["User"] . "'");
    if ($result->num_rows != 0) {
        while ($row5 = $result->fetch_assoc()) {
            $totalMoney = $totalMoney + $row5["Amount"];
        }
    }
    $mysqli->close();

    //They display time credits and post manager
    $home = '<div><label>Welcome back , ' . $_SESSION["Username"] . '</label></div>'
            . '<div><label>Time Credits : ' . $totalMoney . '</label></div>'
            . '<div><a href="createPost.php">Create Post</a></div>'
            . '<div><a href="manage.php">Manage Posts</a></div>'
            . '<div><input type="submit" name="action" value="Log Out" /></div>';
} else {
    //The user hasn't logged it, so show them a generated login form
    $home = '<label>Log In :</label>
        <table id="LogInTable">
        <thead>
        <tr>
        <td><label for="userName">Username</label></td>
        <td><label for="passWord">Password</label></td>
        </tr>
        </thead>
        <tr>
        <td><input type="userName" name="userName" id="userName" value="' . $_COOKIE["USERNAME"] . '"></input></td>
        <td><input type="passWord" name="passWord" id="passWord"></input></td>
        <td><input type="submit" value="Log In" id="submit"></input></td>
        </tr>
        </table>';
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if ($_POST['action'] == 'Log Out') {
        //action for logging out here
        unset($_SESSION['User']);
        unset($_SESSION['Username']);
        header('Location: index.php');
    } else {
        //Lets log them in!
        //Check username
        $userName = test_input($_POST["userName"]);
        setcookie("USERNAME", $userName);
        $passWord = test_input($_POST["passWord"]);
        $conn = mysqli_connect("mysql.cms.gre.ac.uk", "ms2721o", "D5ihHhojnjilPqFp", "mdb_ms2721o");
        $sql = "SELECT * FROM Member WHERE Username = '" . test_input($_POST["userName"]) . "' AND Password = '" . crypt(test_input($_POST["passWord"]), '$1$somethin$') . "'";
        if (mysqli_num_rows(mysqli_query($conn, $sql)) > 0) {
            while ($row = mysqli_fetch_assoc(mysqli_query($conn, $sql))) {
                //If they already activated, redirect them 
                if ($row["ActivationStatus"] == 1) {
                    $_SESSION["User"] = $row["MemberID"];
                    $_SESSION["Username"] = $row["Username"];
                    mysqli_close($conn);
                    header('Location: index.php');
                } else {
                    //They haven't activated, redirect them
                    mysqli_close($conn);
                    header('Location: validation.php');
                }
            }
        } else {
            //$userNameErr = "Username and/or password is incorrect";
            header("Location: login.php?location=" . urlencode($_SERVER['REQUEST_URI']));
            mysqli_close($conn);
        }
    }
}

function test_input($data) {
    //this methods helps prevent XSS and SQL injection
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
        <title>Time Banking</title>
        <link rel="stylesheet" href="css/styles.css"></link>
        <link rel="shortcut icon" href=".../favicon.PNG"></link>
    </head>
    <body>
        <?php
        include('CookiePolicy.php');
        ?>
        <form class="theForm" method="post" id="theForm">
            <?php echo $home; ?>
        </form>

        <div class="wrap">
            <h1>Welcome</h1>
            <div>
                <a href="signup.php">Sign Up Today! It's Free!</a>
            </div>
            <div class="search">
                <form action="action_page.php?" method="get">
                    <input type="text" class="searchTerm" name="search" value="<?php echo $search ?>" placeholder="Enter a skill or location..."></input>
                    <button type="submit" class="searchButton"><b class="fa fa-search">⚲</b></button>
                </form>
            </div>
        </div>

        <!--        <div class="manageFooter">&copy; 2018 - Time Banking Management</div>-->

        <script src="js/three.js"></script>
        <script>
//                  Adapted from source : https://stackoverflow.com/questions/37950024/add-dots-to-vertices-in-three-js/37956110

            THREE.IcosahedronGeometry = function (radius, detail) {
                var t = (1 + Math.sqrt(5)) / 2;
                var vertices = [-1, t, 0, 1, t, 0, -1, -t, 0, 1, -t, 0, 0, -1, t, 0, 1, t, 0, -1, -t, 0, 1, -t, t, 0, -1, t, 0, 1, -t, 0, -1, -t, 0, 1];
                var indices = [0, 11, 5, 0, 5, 1, 0, 1, 7, 0, 7, 10, 0, 10, 11, 1, 5, 9, 5, 11, 4, 11, 10, 2, 10, 7, 6, 7, 1, 8, 3, 9, 4, 3, 4, 2, 3, 2, 6, 3, 6, 8, 3, 8, 9, 4, 9, 5, 2, 4, 11, 6, 2, 10, 8, 6, 7, 9, 8, 1];
                THREE.PolyhedronGeometry.call(this, vertices, indices, radius, detail);
                this.type = 'IcosahedronGeometry';
                this.parameters = {radius: radius, detail: detail};
            };
            THREE.IcosahedronGeometry.prototype = Object.create(THREE.PolyhedronGeometry.prototype);
            THREE.IcosahedronGeometry.prototype.constructor = THREE.IcosahedronGeometry;
            var scene = new THREE.Scene();
            var camera = new THREE.PerspectiveCamera(75, window.innerWidth / window.innerHeight, 0.1, 1000);
            var renderer = new THREE.WebGLRenderer({antialias: 1, alpha: true});
            // renderer.setClearColor(0xf7f7f7);
            renderer.setSize(window.innerWidth, window.innerHeight);
            document.body.appendChild(renderer.domElement);
            scene.fog = new THREE.Fog(0xd4d4d4, 8, 20);
            var mesh = new THREE.IcosahedronGeometry(10, 1); // radius, detail
            var vertices = mesh.vertices;
            var positions = new Float32Array(vertices.length * 3);
            for (var i = 0, l = vertices.length; i < l; i++) {
                vertices[i].toArray(positions, i * 3);
            }
            var geometry = new THREE.BufferGeometry();
            geometry.addAttribute('position', new THREE.BufferAttribute(positions, 3));
            var material = new THREE.PointsMaterial({
                size: 0.4,
                vertexColors: THREE.VertexColors,
                color: 0x252525
            });
            var points = new THREE.Points(geometry, material);
            var object = new THREE.Object3D();
            object.add(points);
            object.add(new THREE.Mesh(
                    mesh,
                    new THREE.MeshPhongMaterial({
                        color: 0x616161,
                        emissive: 0xa1a1a1,
                        wireframe: true,
                        fog: 1
                    })
                    ));
            scene.add(object);
            camera.position.z = 20;

            window.addEventListener('resize', onWindowResize, false);

            function onWindowResize() {

                camera.aspect = window.innerWidth / window.innerHeight;
                camera.updateProjectionMatrix();
                renderer.setSize(window.innerWidth, window.innerHeight);

            }
            var render = function () {
                requestAnimationFrame(render);
                object.rotation.x += 0.002;
                object.rotation.y += 0.002;
                renderer.render(scene, camera);
            };
            render();
        </script>
    </body>   
</html>
