<?php
define("SITEHTML", getcwd()."/");
require_once(SITEHTML."/cfg.php");


if(!$user) {
    header("location: ".SITEISO);
    exit();
} else{
    $qry = sprintf("SELECT superuser FROM users WHERE id = '%d';", $user->id);
    $res = $db->query($qry);
    if(!($res && $res->num_rows && $res->fetch_object()->superuser)) {
        exit();
    }
}

if(isset($_POST["pupd"]) && isset($_POST["pusr"]) && isset($_POST["piso"]) && isset($_POST["pval"])) {
    if((int)$_POST["pval"]) {
        $qry = sprintf("INSERT INTO lingual_rights (user_id , iso) VALUES ('%d', '%s')",
                       $db->real_escape_string($_POST["pusr"]),
                       $db->real_escape_string($_POST["piso"]));
    } else {
        $qry = sprintf("DELETE FROM lingual_rights WHERE lingual_rights.user_id = %d AND lingual_rights.iso = '%s'",
                       $db->real_escape_string($_POST["pusr"]),
                       $db->real_escape_string($_POST["piso"]));
    }
    exit($db->query($qry));
} else {

}


require_once(SITEHTML."class/project.class.php");
$project = new Project($db, LANG_ISO, $user->id);

/* True, this isn't optimal. This will be redone later in the process */
if(isset($_GET['p'])) {
    $breadcrumb = array("Dashboard");
    $pos = explode('/',$_GET['p']);
    $accpage = $pos[sizeof($pos)-1];
} else {
    echo "variable is not set...";
}

/*------------------------------------*/
/*  Meta tags (individual)            */

    //Regular
    $info["title"]="";
    $info["description"]="Description";
    $info["keywords"]="Key,words";

    //Robots
    $info["robots"]=array("index"=>false,"follow"=>false,"archive"=>false);
/*------------------------------------*/

    if(isset($_POST["rmusr"]) && $_POST["rmusr"]=="1" && isset($_POST["usrname"])) {
        $ajax = 1;
        $usr = $db->real_escape_string($_POST["usrname"]);
        $qry = sprintf("SELECT id FROM users WHERE username = '%s'", $usr);
        $res = $db->query($qry);
        if($res && $res->num_rows) {
            $qry = sprintf("DELETE FROM users WHERE username = '%s'", $usr);
            $res = $db->query($qry);
            /* delte project */
        }
    } else {
        $ajax = 0;
    }

    if(!$ajax) {
        //Require the html head
        require(SITEHTML."comp/html-head.php");
        echo '<div class="main">';
        echo '<div id="left-menu" class="lftcol"><ul>';
        echo '<li><a href="'.SITEISO.'" title="'.$lingual->get_text(1312).'">'.$lingual->get_text(1312).'</a></li>';
        echo '<li><a class="logout" href="'.SITEISO.'logout.php">'.$lingual->get_text(2419).'</a></li>';
        echo '</ul></div><div class="bigcol"><div class="content"><p id="msg"></p>';
    }

    if($_GET["p"]=="users") {
        //List users

        if(!$ajax) {
            echo '<h2>User overview</h2>';
            echo '<p>introduction text.</p><br />';
            echo '
            <script type="text/javascript">
                $(document).ready(function() {
                    $("a.usrdel").click(function() {
                        var usrn = $(this).parent().parent().find(".usrname").html();
                        if (confirm("Are you sure you want to delete the user \"" + usrn + "\" and all his projects ?")) {
                            $.post("",{rmusr:1, usrname:usrn },function(result){
                                $("#usrlst").html(result);
                                $("#msg").html("Removed");
                                $("#msg").attr("class", "");
                                $("#msg").fadeIn("fast");
                                $("#msg").fadeOut("slow", function() { $("#msg").attr("class", "hide"); });
                                location.reload(false);
                            });
                            return false;
                        }
                    /* window.location = ; */
                    });
                });
            </script>';
            echo '<table id="usrlst"';
        }
        /* Start of form */
        $qry = "SELECT user.id, user.mail, user.username, user.name, user.superuser, user.validated, user.created, count(projects.owner) amount FROM users user LEFT JOIN projects ON projects.owner = user.id GROUP BY user.id";
        $res = $db->query($qry);
        if($res && $res->num_rows) {
            echo "<tr><th>Username</th><th>Mail</th><th>Name</th><th>Superuser</th><th>Validated</th><th>option</th></tr>";
            while($fetcheduser = $res->fetch_object()) {
                echo '<tr><td class="usrname">'.$fetcheduser->username."</td><td>$fetcheduser->mail</td><td>$fetcheduser->name</td><td>".($fetcheduser->superuser ? "Yes" : "No" )."</td><td>".($fetcheduser->validated ? "Yes" : "No" ).'</td><td><a href="'.SITEISO.'user/'.$fetcheduser->id.'/" title="Edit user" class="usredit"><span class="fa fa-pencil-square"></a></span>&nbsp<a href="#" title="Delete  user" class="usrdel"><span class="fa fa-minus-square"></a></span></td></tr>';
            }
            echo "</table>";
        }
    } else if($_GET["p"]=="user") {
        //Edit user
        echo '<h2>Edit user</h2>';
        echo '<p>Introduction text</p><br />';

        /* Start of form */
        $safe_userid = (int)$_GET["userid"];
        $qry = "SELECT user.mail, user.username, user.name, user.superuser, user.validated, user.created, user.last_seen, count(projects.owner) amount FROM users user LEFT JOIN projects ON projects.owner = user.id WHERE user.id = ".$safe_userid." GROUP BY user.id";

        $res = $db->query($qry);
        if($res && $res->num_rows) {
            $fetcheduser = $res->fetch_object();

            //make sure variables are set be
            $changed["username"] = false;
            $changed["mail"] = false;
            $changed["name"] = false;
            $changed["superuser"] = false;

            if(isset($_POST["updateuser"]) && $_POST["updateuser"]=="Save") {
                require_once(SITEHTML."/class/validate.class.php");
                $_POST['username'] = trim($_POST['username']);
                $_POST['mail'] = trim($_POST['mail']);
                $_POST['name'] = trim($_POST['name']);

                $changed["username"] = ($fetcheduser->username != $_POST["username"]);
                $changed["mail"] = ($fetcheduser->mail != $_POST["mail"]);
                $changed["name"] = ($fetcheduser->name != $_POST["name"]);
                $changed["superuser"] = ($fetcheduser->superuser != $_POST["superuser"]);

                if($changed["username"] && !validate::length_between($_POST['username'],3,20)) {
                    $qry = "SELECT user.mail, user.username, user.name, user.superuser, user.validated, user.created, user.last_seen, count(projects.owner) amount FROM users user LEFT JOIN projects ON projects.owner = user.id WHERE user.id = ".$safe_userid." GROUP BY user.id";

                    $res = $db->query($qry);
                    if($res && $res->num_rows) {
                        $fetcheduser = $res->fetch_object();
                    }

                    $err = true;
                    $errmsg["username"] = $lingual->get_text(2460);
                }
                if($changed["mail"] && !filter_var($_POST['mail'], FILTER_VALIDATE_EMAIL)) {
                    //lookup email
                    $err = true;
                    $errmsg["mail"] = $lingual->get_text(2461);
                }
                if($changed["name"] && !validate::length_between($_POST['name'],3,20)) {
                    $err = true;
                    $errmsg["name"] = $lingual->get_text(2460);
                }
                if($changed["superuser"] && !((int)$_POST['superuser'] == 0 || (int)$_POST['superuser'] == 1 )) {
                    $err = true;
                    $errmsg["superuser"] = "Invalid value";
                }

                $safe_var["username"] = $db->real_escape_string($_POST["username"]);
                $safe_var["mail"] = $db->real_escape_string($_POST["mail"]);
                $safe_var["name"] = $db->real_escape_string($_POST["name"]);
                $safe_var["superuser"] = (int)$_POST["superuser"];

                if ($changed["username"] || $changed["mail"] || $changed["name"] || $changed["superuser"]) {
                    $qry = sprintf("UPDATE users SET username='%s', mail='%s', name='%s', superuser='%d' WHERE id = %d;",
                                   $safe_var["username"],
                                   $safe_var["mail"],
                                   $safe_var["name"],
                                   $safe_var["superuser"],
                                   $safe_userid);
                    if(!$error) {
                        $res = $db->query($qry);
//                        $errmsg["status"] = "Saved";
                    }
                } else {
//                    $errmsg["status"] = "Nothing changed";
                }
            }

            echo '<div class="halfleftcolumn">';
            echo '<form method="post">';
            echo '<label>Username</label>'.
                '<input name="username" type="text" value="'.($changed["username"] ? $_POST["username"] : $fetcheduser->username).'" />'.
                 (isset($errmsg["username"]) ? "<p class='err'>".$errmsg["username"]."</p>" : "").'<br />';
            echo '<label>Mail</label>'.
                '<input name="mail" type="text" value="'.($changed["mail"] ? $_POST["mail"] : $fetcheduser->mail).'" />'.
                 (isset($errmsg["mail"]) ? "<p class='err'>".$errmsg["mail"]."</p>" : "").'<br />';
            echo '<label>Name</label>'.
                '<input name="name" type="text" value="'.($changed["name"] ? $_POST["name"] : $fetcheduser->name).'" />'.
                 (isset($errmsg["name"]) ? "<p class='err'>".$errmsg["name"]."</p>" : "").'<br />';
            echo '<label>Superuser</label>'.
                '<select name="superuser"><option value="0">No</option><option value="1"'.(($changed["superuser"]&&$safe_var["superuser"])||(!$changed["superuser"]&&$fetcheduser->superuser) ? ' selected="selected"' : "").'>Yes</option></select>'.
                (isset($errmsg["superuser"]) ? "<p class='err'>".$errmsg["superuser"]."</p>" : "").'<br />';
//                echo '<p>'.$errmsg["status"].'</p>';
                echo '<input type="submit" name="updateuser" value="Save" />';

            echo '</form>';
            echo '</div>';
            echo '<div class="halfrightcolumn">';

                echo '<p class="left">Signed up date:</p><p class="right">'.$fetcheduser->created.'</p><br />';
                echo '<p class="left">Last active:</p><p class="right">'.$fetcheduser->last_seen.'</p><br />';
                echo '<p class="left">Amount of project:</p><p class="right">'.$fetcheduser->amount.'</p><br />';
                echo '<p class="left">Email verrified:</p><p class="right">'.($fetcheduser->validated ? "Yes" : "No" ).'</p><br />';
            echo '</div>';

            $qry = "SELECT languages.language, languages.iso, (lingual_rights.iso IS NOT NULL) permision FROM languages LEFT JOIN lingual_rights ON languages.iso = lingual_rights.iso AND lingual_rights.user_id = ".$safe_userid.";";
            $res = $db->query($qry);

            if($res && $res->num_rows) {
                echo "<p>The user has permission to translate on the following languages:</p>";
                echo '<table class="userperm">';
                echo '<tr><th class="column1">Permission</th><th class="column2">Language</th></tr>';
                while($fetchedlang = $res->fetch_object()) {
                    echo '<tr><td><select class="userpermision '.$fetchedlang->iso.'" name="userpermision"><option>No</option><option'.($fetchedlang->permision ? ' selected="selected"' : "").'>Yes</option></select></td><td><img class="flag" src="'.SITEURL.'img/flags/'.Strtoupper($fetchedlang->iso).'.png" alt="'.$fetchedlang->iso.'" />'.$fetchedlang->language.'</td></tr>';
                }
                echo "</table>";
?>
<script type="text/javascript">
$(document).ready(function() {
  $('.userpermision').change(function() {
      var iso = $(this).attr("class").split(' ').pop();
      var val = ($(this).val() == 'Yes' ? 1 : 0);
      var usr = "<?=$safe_userid?>";
      $.post("",{pupd:1, piso:iso, pval:val, pusr:usr },function(result){
                        $("#msg").html("Saved");
                        $("#msg").attr("class", "");
                        $("#msg").fadeIn("fast");
                        $("#msg").fadeOut("slow", function() { $("#msg").attr("class", "hide"); });
          });
    });
});

</script>
<?php
            }
        }
    } else {
        echo "nada";
        echo $_GET["p"];
    }
if (!$ajax) {
    echo '</div></div>';
    require(SITEHTML."comp/html-foot.php");
}
?>
