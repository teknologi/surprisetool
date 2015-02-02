<?php
class mod_8 extends Module {
    public function load() {

        echo '<h2>'.$this->get_header().'</h2>';
        echo '<p>'.$this->get_paragraph().'</p><br />';

        /* Start of form */
        echo '<form method="post"><input type="hidden" name="save" /><span>'.$this->lingual->get_text(1484).'</span><span style="display:inline-block; width:380px; text-align:right; margin-right:10px;';
        $qry = "SELECT expire FROM mod8_status WHERE project_id=".$this->get_project_id()." AND step_id=".$this->get_step().";";

        $res = $this->db->query($qry);
        if($res && $res->num_rows) {
            $expire = $res->fetch_object()->expire;
            if(time() < strtotime($expire)) {
                //active
                echo ' color:green;">Active until '.date("F j, Y, H:i",strtotime($expire)).'</span><input type="submit" name="act" value="'.$this->lingual->get_text(1477).'" />';
                echo '<input type="hidden" name="act" value="Deactivate" />';
            } else {
                //expired
                echo ' color:grey;"> Inactive since '.date("F j, Y, H:i",strtotime($expire)).'</span><input type="submit" name="act" value="'.$this->lingual->get_text(1478).'" />';
                echo '<input type="hidden" name="act" value="Reactivate" />';
            }
        } else {
            echo 'color:grey;"> Inactive</span><input type="submit" value="'.$this->lingual->get_text(1483).'" />';
            echo '<input type="hidden" name="act" value="'.($this->get_contfrom_id()?"Import data and activate":"Activate").'" />';
            echo '</form>';
            return "";
        }
        echo '</form>';
        echo '<br />';
        $qry = "SELECT status.id, mod8_group.link, mod6_dim.dimension FROM mod8_status status LEFT JOIN mod8_group ON status.id = mod8_group.mod8_id LEFT JOIN mod6_dim ON mod8_group.dimension_id = mod6_dim.id WHERE status.project_id=".$this->get_project_id()." AND status.step_id=".$this->get_step();

        $res = $this->db->query($qry);
        if($res && $res->num_rows) {
            echo '<table style="width:100%"><tr><th>'.$this->lingual->get_text(1326).'</th><th>'.$this->lingual->get_text(1327).'</th><th>'.$this->lingual->get_text(1328).'</th></tr>';

            while($dimension = $res->fetch_object()) {
                /* Set visibility */
                echo '<tr><td>'.$this->lingual->get_text($dimension->dimension).'</td><td><a href="'.SITEISO.'round1/'.$dimension->id.'-'.$dimension->link.'/" target="_blank">'.$this->lingual->get_text(1486).'</a></td><td><a href="mailto:?subject='.$this->lingual->get_text(1102).'&body='.$this->lingual->get_text(1107).SITEISO.'round1/'.$dimension->id.'-'.$dimension->link.'/">'.$this->lingual->get_text(1487).'</a></td></tr>';

            }
            echo "</table>";
            echo '<p class="assessmentdescr">'.$this->lingual->get_text(1331).'</p>';
            echo '<form method="post">';
            echo '<input type="hidden" name="save" value="generate" />';
            echo '<input type="submit" name="generate" onClick="if (!confirm('."'".$this->lingual->get_text(1480)."'".')) { return false; }" value="'.$this->lingual->get_text(1479).'" />';
            echo '</form>';

        }
        echo '<p class="assessmentdescr">'.$this->lingual->get_text(1332).'</p>';
        echo '<form method="post">';
        echo '<input type="hidden" name="save" value="reset" />';
        echo '<input type="submit" onClick="if (!confirm('."'".$this->lingual->get_text(1481)."'".')) { return false; }" value="'.$this->lingual->get_text(1482).'" />';
        echo '</form>';

    }
    public function save() {
        if(isset($_POST["act"])) {
            $this->set_status(1);

            if($_POST["act"]=="Activate" || $_POST["act"]=="Import data and activate") {
                $qry = sprintf("INSERT INTO mod8_status (project_id, step_id, expire) VALUES ('%s', '%s', '%s');",
                    $this->get_project_id(),
                    $this->get_step(),
                    date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s')." +1 day")));

                $res = $this->db->query($qry);

                if($res) {
                    $ins_id = $this->db->insert_id;
                    $this->generate_new_links($ins_id);

                    if ($_POST["act"]=="Import data and activate") {
                        $qry = sprintf("INSERT INTO mod8_group_input (mod8_id, dimension_id, criteria, investment, message, open_discussion, summary, rating) (SELECT '%d', dimension_id, criteria, investment, message, open_discussion, summary, rating FROM mod8_group_input as input LEFT JOIN mod8_status status ON status.id = input.mod8_id WHERE status.project_id = '%d' AND status.step_id = '%d');",
                            $ins_id,
                            $this->get_project_id(),
                            $this->get_contfrom_id());

                        if (!$this->db->query($qry)){
                            echo 'empty?';//$qry;
                        }
                    }

                }
            } else if($_POST["act"]=="Reactivate") {
                $qry = sprintf("UPDATE mod8_status SET expire='%s' WHERE project_id=%s AND step_id=%s;",
                    date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s')." +1 day")),
                    $this->get_project_id(),
                    $this->get_step());
                $res = $this->db->query($qry);
            } else if($_POST["act"]=="Deactivate") {
                $qry = sprintf("UPDATE mod8_status SET expire='%s' WHERE project_id=%s AND step_id=%s;",
                    date('Y-m-d H:i:s', time()),
                    $this->get_project_id(),
                    $this->get_step());
                $res = $this->db->query($qry);
            }



        } else if($_POST["save"]=="generate") {
            $qry = sprintf("SELECT id FROM mod8_status WHERE project_id='%s' AND step_id='%s';",
                $this->get_project_id(),
                $this->get_step());
            $res = $this->db->query($qry);
            if($res && $res->num_rows) {
                $this->generate_new_links($res->fetch_object()->id);
            } else {
                //something is wrong, this should be logged.
            }
        } else if($_POST["save"]=="reset") {
            $this->reset_step();
        }
    }

    public function reset_step() {
        //remove from...a
        $qry = 'SELECT id FROM mod8_status WHERE project_id = '.$this->get_project_id().' AND step_id = '.$this->get_step().";";
        $res = $this->db->query($qry);
        if ($res && $res->num_rows == 1) {
            $mod8_id = $res->fetch_object()->id;
            $this->db->query("DELETE FROM mod8_status WHERE mod8_status.id = ".$mod8_id." LIMIT 1;");
            $this->db->query("DELETE FROM mod8_group WHERE mod8_group.mod8_id = ".$mod8_id.";");
            $this->db->query("DELETE FROM mod8_group_input WHERE mod8_group_input.mod8_id = ".$mod8_id.";");
        }
    }

    public function generate_new_links($id) {
        $qry = "SELECT mod8_id FROM mod8_group WHERE mod8_id=".$id.";";
        $res = $this->db->query($qry);
        $exist = ($res && $res->num_rows);

        $qry = sprintf("SELECT id FROM mod6_dim WHERE step_id = '16';", $this->get_project_id());
        $result = $this->db->query($qry);
        if($result && $result->num_rows) {
            while($dimension = $result->fetch_object()) {
                if($exist) {
                    $qry = sprintf("UPDATE mod8_group SET link='%s' WHERE mod8_id='%s' AND dimension_id='%s';",
                        md5($this->get_project_id().$this->get_step().$dimension->id.time()),
                        $id,
                        $dimension->id);
                    $res = $this->db->query($qry);
                } else {
                    $qry = sprintf("INSERT INTO mod8_group (mod8_id, dimension_id, link) VALUES ('%s', '%s', '%s');",
                        $id,
                        $dimension->id,
                        md5($this->get_project_id().$this->get_step().$dimension->id.time()));
                    $res = $this->db->query($qry);
                }
            }
        }
    }

    public function report(){

        echo '<h1 id="subsection'.$this->get_step().'">'.$this->get_header().'</h1>';
        //foreach group
        $qry = sprintf('SELECT id, dimension name FROM mod6_dim;');
        $result_group = $this->db->query($qry);
        while($group = $result_group->fetch_object()) {
            echo '<h2>'.$this->lingual->get_text($group->name).'</h2>';

            //foreach phase
            $qry = sprintf("SELECT DISTINCT(mod8_phase.id), mod8_phase.name, mod8_phase.description FROM mod8 LEFT JOIN mod8_phase ON mod8.phase = mod8_phase.id ORDER by mod8.phase, mod8.num;");
            $res = $this->db->query($qry);
            while($phase = $res->fetch_object()) {
                echo "<h3>".$phase->id.':'.$this->lingual->get_text($phase->name)."</h3>";
                $rowspan = array();
                $max_column = 4;
                $cur_column = 0;
                $endrow = 0;
								
                //foreach question
                $qry = sprintf('SELECT mod8.id, type, question, colspan_question, rowspann_question, colspan_answer, rowspan_answer  FROM mod8 WHERE phase = '.$phase->id.' ORDER BY mod8.phase, mod8.num;');
                $result = $this->db->query($qry);

                echo '<table class="mod8"><tr>';
                while($question = $result->fetch_object()) {
                    if($endrow) {
                        echo '<tr class="question">';
                    }
                    echo '<td class="'.($question->type == "label" ? 'tabox title' : 'tabox').'"'.($question->colspan_question > 1 ? ' colspan="'.$question->colspan_question.'"' : '' ).($question->rowspan_question > 1 ? ' rowspan="'.$question->rowspan_question.'"' : '' ).'>';
                    echo $this->lingual->get_text($question->question);
                    echo '</td>'."\n";
										
                    if($question->type != "label" && ($question->colspan_answer > 0)) {
                        echo '<td class="dabox"'.($question->colspan_answer > 1 ? ' colspan="'.$question->colspan_answer.'"' : '' ).($question->rowspan_answer > 1 ? ' rowspan="'.$question->rowspan_answer.'"' : '' ).'>';

                        if ($question->type == "itemint") {
                            $qry = sprintf("SELECT id, num, answer FROM mod8_answers_load WHERE question_id=%d ORDER BY num;", $question->id);
                            $result_option = $this->db->query($qry);
                            $i = 0;
                            echo '<ul>';
                            while($option = $result_option->fetch_object()) {
                                echo '<li><span class="option">'.$this->lingual->get_text($option->answer).'</span>';
                                $qry = sprintf('SELECT itemint.answer FROM mod8_answers_save save LEFT JOIN mod8_answers_itemint itemint ON itemint.answer_id = save.id AND itemint.answer_num = %d WHERE save.question_id = %d AND save.project_id = %d AND save.step_id = %d AND save.dimension_id = %d;',
                                               $i,
                                               $question->id,
                                               $this->get_project_id(),
                                               $this->get_step(),
                                               $group->id);

                                $result_answer = $this->db->query($qry);
                                if ($result_answer && $result_answer->num_rows){
                                    echo '<span class="optionanswer">'.$result_answer->fetch_object()->answer.'</span>';
                                }
                                echo '</li>';
                                ++$i;
                            }
                            echo '</ul>';
                        } else if ($question->type == "text") {
                            $qry = sprintf('SELECT str.answer FROM mod8_answers_save save LEFT JOIN mod8_answers_str str ON str.answer_id = save.id WHERE save.question_id = '.$question->id.' AND save.project_id = '.$this->get_project_id().' AND save.step_id = '.$this->get_step().' AND save.dimension_id = '.$group->id.';');
                            $result_answer = $this->db->query($qry);
                            if ($result_answer && $result_answer->num_rows)
                                echo '<span>'.nl2br(htmlspecialchars($result_answer->fetch_object()->answer)).'</span>';
                        } else {
                            echo '<p>'.'Not implemented'.'<p>';
                        }
                        echo '</td>'."\n";
                    }

                    for($i = 0; $i < $question->rowspan_question -1; ++$i) {
                        $rowspan[$i] = $question->rowspan_question;
                    }
                    for($i = 0; $i < $question->rowspan_answer -1; ++$i) {
                        $rowspan[$i] = $question->rowspan_answer;
                    }

                    $endrow = ($cur_column + $question->colspan_question + $question->colspan_answer)  >= $max_column;
                    if($endrow) {
                        if(count($rowspan) && $rowspan[0] > 0) {
                            $cur_column = $rowspan[0];
                            array_shift($rowspan);
                        } else {
                            $cur_column = 0;
                        }
                        echo '</tr>'."\n";
                    } else {
                        $cur_column += $question->colspan_question + $question->colspan_answer;
                    }
                }
                echo '</table>';
                echo '<div class="clear"></div>';
            }
        }
    }
}
