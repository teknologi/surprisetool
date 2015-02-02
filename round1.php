<?php
define("SITEHTML", getcwd()."/");

//Require the configurations
require_once(SITEHTML."/cfg.php");

/*------------------------------------*/
/*  Meta tags (individual)            */

    //Regular
    $info["title"]="Title";
    $info["description"]="Description";
    $info["keywords"]="Key,words";

    //Robots
    $info["robots"]=array("index"=>false,"follow"=>false,"archive"=>false);
    $info["js"][] = SITEURL.'script/jquery-ui-1.10.0.custom.min.js';
    $info["css"][] = SITEURL.'css/jquery-ui-1.10.0.custom.css';

/*------------------------------------*/

/* $lang=$db->real_escape_string($_GET["la"]); */
if(isset($_GET["id"]) && isset($_GET["ln"])) {
    $id=(int)$_GET["id"];
    $link=$db->real_escape_string($_GET["ln"]);

    if(isset($_GET["phase_select"])) {
        $phase_select=(int) $_GET["phase_select"];
    }

    //Require the html head
    require(SITEHTML."comp/html-head.php");

    echo '<div class="main">';
    echo '<div id="left-menu" class="lftcol">';

    $qry = "SELECT mod6_dim.dimension, mod6_dim.id as dimension_id, status.project_id, status.step_id FROM mod8_status status LEFT JOIN mod8_group ON status.id=mod8_group.mod8_id LEFT JOIN mod6_dim ON mod8_group.dimension_id = mod6_dim.id WHERE mod8_group.mod8_id=".$id." AND mod8_group.link='".$link."' AND status.expire > NOW();";

    $res = $db->query($qry);
    $foundit_dim = false;
    $foundit_phase = false;
    if($res && $res->num_rows) {
        $foundit_dim = true;

        $link_info = $res->fetch_object();
        echo '<p class="dimension"><a href="'.SITEISO.'round1/'.$id.'-'.$link.'/" >'.$lingual->get_text($link_info->dimension).'</a></p>';

        $qry = sprintf("SELECT DISTINCT(mod8_phase.id), mod8_phase.name, mod8_phase.description FROM mod8 LEFT JOIN mod8_phase ON mod8.phase = mod8_phase.id ORDER by mod8.phase, mod8.num;");
        $result = $db->query($qry);

        if($result && $result->num_rows) {
            echo '<ul class="phase">';
            while($phase = $result->fetch_object()) {
                if(isset($phase_select) && $phase_select==$phase->id) {
                    $foundit_phase = true;
                    $phase_title = $lingual->get_text($phase->name);
                    $phase_description = $lingual->get_text($phase->description);
                }
                echo '<li><a href="'.SITEISO.'round1/'.$id.'-'.$link.'/'.$phase->id.'/"'.((isset($phase_select) && $phase_select==$phase->id)?' class="active"':'').'>'.$lingual->get_text($phase->name).'</a></li>';
//                echo '<li>'.$lingual->get_text($phase->name).'</li>';
            }
        }
            echo '</ul>';
    }

    echo '</div>';
    echo '<div class="bigcol"><div class="content">';


            if(isset($_POST["save"])) {
                //Save start-------------------------
                //check whether all questions are answered
                $qry = "SELECT mod8.id, mod8.type, mod8.show_if_x, mod8.and_x_contains as contains FROM mod8 WHERE mod8.step_id=".$link_info->step_id." AND mod8.phase=".$phase_select." ORDER BY mod8.num";
                $res = $db->query($qry);
                $checklist = array();
                if($res && $res->num_rows) {

                    while($map = $res->fetch_object()) {
                        if (is_null($map->show_if_x))
                            $checklist[] = $map->id;
                        else if (in_array($map->show_if_x, $checklist) && isset($_POST["a".$map->show_if_x])) {
                            if (is_array($_POST["a".$map->show_if_x])) {
                                if (in_array($map->contains, $_POST["a".$map->show_if_x]))
                                    $checklist[] = $map->id;
                            } else if($map->contains == $_POST["a".$map->show_if_x]) {
                                $checklist[] = $map->id;
                            }
                        }
                    }
                }

                $qry = 'SELECT mod8.id, mod8.type, (saved.id > 0) as exist, saved.id as saved_id FROM mod8 LEFT JOIN mod8_answers_save saved ON saved.question_id=mod8.id AND saved.project_id='.$link_info->project_id.' AND saved.step_id='.$link_info->step_id.' AND saved.dimension_id = '.$link_info->dimension_id.' WHERE mod8.step_id='.$link_info->step_id.' AND mod8.phase='.$phase_select.' ORDER BY mod8.num';
                $res = $db->query($qry);
                if($res && $res->num_rows) {

                    while($posts = $res->fetch_object()) {
                        $comment_str = "";
                        $isvalue = false;

                        if ($posts->type == "checkbox" && isset($_POST["a".$posts->id])) {
                            /* set checkbox value */
                            $ans_lst = $_POST["a".$posts->id];
                            $isvalue =  (count($ans_lst)>0);
                        } else if ($posts->type == "radio" && isset($_POST["a".$posts->id])){
                            /* set radiobox value */
                            $ans_int = $_POST["a".$posts->id];
                            $isvalue = !empty($ans_int);
                        } else if ($posts->type == "text" && isset($_POST["a".$posts->id])){
                            /* set text value */
                            $ans_str = $_POST["a".$posts->id];
                            $isvalue = !empty($ans_str);
                        } else if ($posts->type == "itemint" && isset($_POST["a".$posts->id])) {
                            /* set itemint value */
                            $ans_itemint = $_POST["a".$posts->id];
                            $isvalue =  (count($ans_itemint)>0);
                        }

                        if ((empty($comment_str) && !$isvalue) || !in_array($posts->id, $checklist)) {

                            /* if answer or comment exist in db and not in the current form, delete it from db */
                            if ($posts->exist) {
                                $qry = "DELETE FROM mod8_answers_save WHERE question_id = ".$posts->id." AND project_id = ".$link_info->project_id." AND step_id = ".$link_info->step_id.";";
                                $ins = $db->query($qry);
                                $qry = "DELETE FROM ".(($posts->type == "text")?"mod8_answers_str":"mod8_answers_int")." WHERE answer_id = ".$posts->saved_id.";";
                                $ins = $db->query($qry);
                            }
                        } else {
        //                    $this->set_status(1);

                            if ($posts->exist) {
                                $qry = sprintf("UPDATE mod8_answers_save SET comment='%s' WHERE question_id=%s AND project_id=%s AND step_id=%s AND dimension_id=%s;",
                                               $db->real_escape_string($comment_str),
                                               (int) $posts->id,
                                               $link_info->project_id,
                                               $link_info->step_id,
                                               $link_info->dimension_id);
                                $upd = $db->query($qry);

                                if ($posts->type == "checkbox" && $isvalue) {
                                    /* checkbox */
                                    /* Will be replaced by update if changed, instead of delete and then insert */
                                    $qry = "DELETE FROM mod8_answers_int WHERE answer_id = ".$posts->saved_id.";";
                                    $ins = $db->query($qry);
                                    for($i = 0; $i < count($ans_lst); $i++) {
                                        $qry = sprintf("INSERT INTO mod8_answers_int (answer_id, answer) VALUES ('%d', '%d');",
                                            (int) $posts->saved_id,
                                            (int) $ans_lst[$i]);

                                        $ins = $db->query($qry);
                                    }
                                } else if ($posts->type == "radio" && $isvalue) {
                                    /* Will be replaced by update if changed, instead of delete and then insert */
                                    $qry = "DELETE FROM mod8_answers_int WHERE answer_id = ".$posts->saved_id.";";
                                    $ins = $db->query($qry);
                                    $qry = sprintf("INSERT INTO mod8_answers_int (answer_id, answer) VALUES ('%d', '%d');",
                                        (int) $posts->saved_id,
                                        (int) $ans_int);
                                    $ins = $db->query($qry);
                                } else if ($posts->type == "text" && $isvalue) {
                                    $qry = sprintf("UPDATE mod8_answers_str SET answer = '%s' WHERE answer_id = %d;",
                                        $db->real_escape_string($ans_str),
                                        (int) $posts->saved_id);
                                    $ins = $db->query($qry);
                                } else if ($posts->type == "itemint" && $isvalue) {
                                    /* itemint */
                                    /* Will be replaced by update if changed, instead of delete and then insert */
                                    $qry = "DELETE FROM mod8_answers_itemint WHERE answer_id = ".$posts->saved_id.";";
                                    $ins = $db->query($qry);
                                    for($i = 0; $i < count($ans_itemint); $i++) {
                                        if(!empty($ans_itemint[$i]) && (int)$ans_itemint[$i] >= 0 && (int)$ans_itemint[$i] <= 9 ) {
                                            $qry = sprintf("INSERT INTO mod8_answers_itemint (answer_id, answer_num, answer) VALUES ('%d', '%d', '%d');",
                                            (int) $posts->saved_id,
                                            (int) $i,
                                            (int) $ans_itemint[$i]);
                                            $ins = $db->query($qry);
                                        }
                                    }

                                }
                            } else {
                                /* Insert */
                                $qry = sprintf("INSERT INTO mod8_answers_save (question_id, project_id, step_id, dimension_id, comment) VALUES ('%s','%s','%s','%s','%s');",
                                               (int) $posts->id,
                                               $link_info->project_id,
                                               $link_info->step_id,
                                               $link_info->dimension_id,
                                               $db->real_escape_string($comment_str));
                                $insterted = $db->query($qry);

                                $inserted_id = $insterted ? $db->insert_id : 0;

                                if ($posts->type == "checkbox" && $isvalue) {
                                    for($i = 0; $i < count($ans_lst); $i++) {
                                        $qry = sprintf("INSERT INTO mod8_answers_int (answer_id, answer) VALUES ('%s', '%s');",
                                            (int) $inserted_id,
                                            (int) $ans_lst[$i]);
                                        $ins = $db->query($qry);
                                    }
                                } else if ($posts->type == "radio" && $isvalue) {
                                    $qry = sprintf("INSERT INTO mod8_answers_int (answer_id, answer) VALUES ('%s', '%s');",
                                        (int) $inserted_id,
                                        (int) $ans_int);
                                    $ins = $db->query($qry);
                                } else if ($posts->type == "text" && $isvalue) {
                                    $qry = sprintf("INSERT INTO mod8_answers_str (answer_id, answer) VALUES ('%s', '%s');",
                                        (int) $inserted_id,
                                        $db->real_escape_string($ans_str));
                                    $ins = $db->query($qry);
                                } else if ($posts->type == "itemint" && $isvalue) {
                                    for($i = 0; $i < count($ans_itemint); $i++) {
                                        if(!empty($ans_itemint[$i]) && (int)$ans_itemint[$i] >= 0 && (int)$ans_itemint[$i] <= 9 ) {
                                            $qry = sprintf("INSERT INTO mod8_answers_itemint (answer_id, answer_num, answer) VALUES ('%s', '%s', '%s');",
                                            (int) $inserted_id,
                                            (int) $i,
                                            (int) $ans_itemint[$i]);
                                            $ins = $db->query($qry);
                                        }
                                    }
                                }
                            }
                        }
                    }
                    if(isset($_POST["save"])) {
                        //$this->print_msg();
                    }
                }
                //Save end -------------------------
            }

    if($foundit_phase) {
        echo '<h1>'.$phase_title.'</h1>';
        echo '<p>'.$phase_description.'</p><br />';
        echo '<p id="msg" class="hide">saved</p>';

        echo '<form id="qform" method="post" class="dresearch stanform"><table class="mod8"><tr>';
        $qry = 'SELECT mod8.id, mod8.num, mod8.category, mod8.type, mod8.question, mod8.help, mod8.show_if_x, mod8.and_x_contains, (CASE WHEN saved.id > 0 THEN 1 ELSE 0 END) as exist, saved.id as saved_id, saved.comment, mod8.colspan_question, mod8.colspan_answer, mod8.rowspan_question, mod8.rowspan_answer FROM mod8 LEFT JOIN mod8_answers_save saved ON saved.question_id=mod8.id AND saved.project_id='.$link_info->project_id.' AND saved.step_id = '.$link_info->step_id.' AND saved.dimension_id = '.$link_info->dimension_id.' WHERE mod8.step_id='.$link_info->step_id.' AND mod8.phase='.$phase_select.' ORDER BY mod8.phase, mod8.num';

        $res_1 = $db->query($qry);

        if($res_1 && $res_1->num_rows) {

            $showtree = array();
            $checktree = array();

            $rowspan = array();
            $max_column = 4;
            $cur_column = 0;
            $endrow = 0;
            while($quest = $res_1->fetch_object()) {
                /* Set visibility */
                $visible = ($quest->show_if_x == NULL);
                if (!$visible) {
                    $showtree[$quest->show_if_x][$quest->and_x_contains][] = $quest->id;
                    if(isset($checktree[$quest->show_if_x]))
                        $visible = in_array($quest->and_x_contains,$checktree[$quest->show_if_x]);

                }

                /* Load data */
                $choice_arr = array();
                $answer = "";
                if($quest->exist) {
                    if($quest->type == "checkbox" || $quest->type == "radio") {
                        /* if checkbox or radio */
                        $qry = "SELECT answer FROM mod8_answers_int WHERE answer_id = ".$quest->saved_id.";";

                        $result = $db->query($qry);
                        if($result && $result->num_rows) while($get = $result->fetch_object())
                            $choice_arr[] = $get->answer;
                        if ($quest->show_if_x == NULL)
                            $checktree[$quest->id] = $choice_arr;
                    } else if($quest->type == "itemint") {
                        /* if itemint */
                        $qry = "SELECT answer_num num, answer FROM mod8_answers_itemint WHERE answer_id = ".$quest->saved_id." ORDER BY answer_num;";
                        $result = $db->query($qry);
                        if($result && $result->num_rows) while($get = $result->fetch_object())
                            $choice_arr[$get->num] = $get->answer;
                        if ($quest->show_if_x == NULL)
                            $checktree[$quest->id] = $choice_arr;
                    } else {
                        /* it must be a textfield */
                        $answer = "";
                        $qry = "SELECT answer FROM mod8_answers_str WHERE answer_id = ".$quest->saved_id.";";
                        $result = $db->query($qry);
                        if($result && $result->num_rows) while($get = $result->fetch_object())
                            $answer = $get->answer;
                    }
                }

                if($endrow) {
                    echo '<tr id="q'.$quest->id.'" class="question'.($visible?'':' hide').'">';
                }

                if($quest->colspan_question > 0) {

                    echo '<td class="'.($quest->type == "label" ? 'tabox title' : 'tabox').'"'.($quest->colspan_question > 1 ? ' colspan="'.$quest->colspan_question.'"' : '' ).($quest->rowspan_question > 1 ? ' rowspan="'.$quest->rowspan_question.'"' : '' ).'>';
                    if($quest->category > 0)
                        echo "<p>".$lingual->get_text($quest->category)."</p>";

                    echo $lingual->get_text($quest->question);

                    if($quest->type != "label") {
                        echo ' <a href="#" class="questext"><i class="fa fa-question-circle"></i><span class="hide">'.$lingual->get_text($quest->help).'</span></a>';
                    }
                    echo '</td>';
                }
            
                if($quest->type != "label" && ($quest->colspan_answer > 0)) {
                    echo '<td class="dabox"'.($quest->colspan_answer > 1 ? ' colspan="'.$quest->colspan_answer.'"' : '' ).($quest->rowspan_answer > 1 ? ' rowspan="'.$quest->rowspan_answer.'"' : '' ).'>';
                    if($quest->type == "checkbox" || $quest->type == "radio") {
                        $qry = "SELECT id, num, answer FROM mod8_answers_load WHERE question_id=".$quest->id." ORDER BY num;";
                        $res2 = $db->query($qry);
                        if($res2 && $res2->num_rows) {
                            while($ans = $res2->fetch_object()) {
                                echo '<label><input type="'.$quest->type.'" name="a'.$quest->id.($quest->type=="checkbox"?"[]":"").'" '.(($quest->exist&&in_array($ans->id, $choice_arr))?' checked="checked"':'').' value="'.$ans->id.'" />'.$lingual->get_text($ans->answer).'</label>';
                            }
                        }
                    } else if($quest->type == "itemint") {
                        $qry = "SELECT id, num, answer FROM mod8_answers_load WHERE question_id=".$quest->id." ORDER BY num;";
                        $res2 = $db->query($qry);
                        if($res2 && $res2->num_rows) {
                            $i = 0;
                            while($ans = $res2->fetch_object()) {
                                echo '<label for="a'.$quest->id."[".$i."]".'" class="itemint">'.$lingual->get_text($ans->answer).'</label><input type="text" name="a'.$quest->id."[".$i."]".'" '.' value="'.$choice_arr[$i].'" class="itemint" /><br />';
                                ++$i;
                            }
                        }
                    } else if($quest->type == "text") {
                        /* text */
                        echo '<textarea name="a'.$quest->id.'" placeholder="'.$lingual->get_text(1357).'">'.$answer.'</textarea>';
                    }
                    echo '</td>';
                }

                for($i = 0; $i < $quest->rowspan_question -1; ++$i) {
                    $rowspan[$i] = $quest->rowspan_question;
                }
                for($i = 0; $i < $quest->rowspan_answer -1; ++$i) {
                    $rowspan[$i] = $quest->rowspan_answer;
                }

                $endrow = ($cur_column + $quest->colspan_question + $quest->colspan_answer)  >= $max_column;
                if($endrow) {
                    if(count($rowspan) && $rowspan[0] > 0) {
                        $cur_column = $rowspan[0];
                        array_shift($rowspan);
                    } else {
                         $cur_column = 0;
                    }
                    echo '</tr>';
                } else {
                    $cur_column += $quest->colspan_question + $quest->colspan_answer;
                }
//                echo ($endrow ? "is ending and colspan is" : "is not ending and colspan is ").$cur_column."<br />";
            }
        } else {
            echo 'Could not find data...';
        }

        echo '</table>';
        echo '<input type="submit" name="save" id="save_and_stay" value="'.$lingual->get_text(2485).'" />';
        echo '</form>';


//Javascript should be dynamic also
echo '<script type="text/javascript">'."\n".'$(document).ready(function() {'."\n";

    echo 'var is_changed = false;';
    echo 'var nav_str = "You have unsaved changes in this document. Cancel now, then \'Save\' to save them. Or continue to discard them.";';
    echo '$("textarea, input[type=radio], input[type=checkbox]").change(function() { if(!is_changed) { change_state(); } });';
    echo 'function change_state() { is_changed = true; window.onbeforeunload = function(){ return nav_str }}';

?>
    $("#save_and_stay").click(function() {
        $("#msg").html("<img src='<?= SITEURL."img/ajax-loader.gif" ?>' alt='ajax-loader' />");
        $("#msg").attr("class", "");
        $("#msg").fadeIn("fast");
        window.onbeforeunload = null;
        var values = $("#qform").serializeArray();
        values.push({name: "save", value: "1"});
        values.push({name: "jssend", value: "1"});
        $.ajax({
              url: window.location,
              type: "post",
              data: values,
              success: function(){
                  $("#msg").html("Saved");
                  $("#msg").fadeOut("slow", function() { $("#msg").attr("class", "hide"); });
                  is_changed = false;
              }, error:function(){
                  alert("Something went wrong");
                  $("#msg").html("Failed");
                  $("#msg").fadeOut("slow", function() { $("#msg").attr("class", "hide"); });
              }
        });

        return false;
    });
<?php
    echo '$("#qform, #sbmimport").submit(function(){ window.onbeforeunload = null; });';

    foreach ($showtree as $x => $arr){
        echo '$("#q'.$x.' input[type='."'radio'".']").change(function() {'."\n";
        foreach ($arr as $contains => $items) {

            echo 'if($(this).val()=='.$contains.') {'."\n";
            if (count($items)) {
                $subitems = "";
                for($i=0;$i<count($items);$i++) {
                    $subitems .= '#q'.$items[$i];
                    if ($i != count($items)-1)
                        $subitems .= ',';
                }
                echo '$("'.$subitems.'").attr('."'class', 'question'".');'."\n";
                echo '} else {'."\n";
                echo '$("'.$subitems.'").attr('."'class', 'question hide'".');'."\n";
//              echo "if(!$(this).attr('checked')) ".'$("'.$subitems.'").attr('."'class', 'question hide'".');'."\n";
                echo '}'."\n";
            }
        }
        echo '});'."\n";
    }


    foreach ($showtree as $x => $arr){

        echo '$("#q'.$x.' input[type='."'checkbox'".']").change(function() {'."\n";

        foreach ($arr as $contains => $items) {

            echo 'if($(this).val()=='.$contains.') {'."\n";
            if (count($items)) {
                $subitems = "";
                for($i=0;$i<count($items);$i++) {
                    $subitems .= '#q'.$items[$i];
                    if ($i != count($items)-1)
                        $subitems .= ',';
                }
                echo 'if ($(this).attr('."'checked'".') == '."'checked'".') {';
                echo '$("'.$subitems.'").attr('."'class', 'question'".');'."\n";
                echo '} else {'."\n";
                echo '$("'.$subitems.'").attr('."'class', 'question hide'".');'."\n";
                echo '}';
                echo '}'."\n";
            }
        }
        echo '});'."\n";
    }

    echo '});'."\n".'</script>';

    } else if ($foundit_dim){
        echo '<h1>'.$lingual->get_text(2374).'</h1>';
        echo '<p>'.$lingual->get_text(2375).'</p><br />';

    } else{
        //Should show 404.php instead
        echo '<h1>Page not found</h1>';
        echo '<p>The page you are looking for does not exist; it may have been moved, or removed altogether.</p>';
    }
    echo '</div></div>';
}

echo '</div>';



//Require the html foot
require(SITEHTML."comp/html-foot.php");
?>
