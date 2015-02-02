<?php

define("SITEHTML", getcwd()."/");
require_once(SITEHTML."/cfg.php");

/*------------------------------------*/
/*  Meta tags (individual)            */

    //Regular
    $info["title"]="";
    $info["description"]="Description";
    $info["keywords"]="Key,words";

    //Robots
    $info["robots"]=array("index"=>false,"follow"=>false,"archive"=>false);

/*------------------------------------*/
//Require the html head
require(SITEHTML."comp/html-head.php");

echo '<div class="bigcol1"><div class="content">';


    if(isset($_GET['user']) && isset($_GET['token'])) {
        $token = $db->real_escape_string($_GET['token']);
        $user = $db->real_escape_string($_GET['user']);

        $qry = "UPDATE users SET validated = 1, token_string='' WHERE username ='$user' AND token_string='$token' LIMIT 1";
        $res = $db->query($qry);
        if($res && $db->affected_rows > 0) {
            echo '<h1>Confirmed</h1>';
            echo '<p>Thank you for confirming your email address. You can log in <a href="'.SITEISO.'">here</a>.</p>';
        } else {
            echo '<h1>Expired</h1>';
            echo '<p>The link is expired.</p>';
        }
    } else {
            echo "<h1>Couldn't crunch data</h1>";
            echo "<p>The webmaster has been informed</p>";
    }
    echo '</div></div>';
echo '</div>';

//Require the html foot
require(SITEHTML."comp/html-foot.php");
?>
