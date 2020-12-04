<?php
//This will generate the cookie policy notification, aswell as the javascript to close it BUT only if the user hasn't already closed it before
        if (!isset($_COOKIE['CookiePolicy'])) {
            echo '<div class="CookiePolicyWarning" id="CookieAlert">
                        <div>
                By using this website, you agree to the usage of cookies to enhance your experience. Find our more <a href="about.php">here</a>
                <button title="Remove" type="button" onclick="Close()" id="RemoveAlert">x</button>
                <script>
                    function Close() {
                        document.getElementById("CookieAlert").innerHTML = "";
                        document.cookie = "CookiePolicy=seen";
                    }
                </script>  
            </div>
        </div>';
        }
?>