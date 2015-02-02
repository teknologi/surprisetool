<?php
class mod_5 extends Module {
    public function load() {

        echo '<h2>'.$this->get_header().'</h2>';
        echo '<p>'.$this->get_paragraph().'</p><br />';

        /* Start of form */
        echo '<form method="post"><input type="hidden" name="save" /><span>'.$this->lingual->get_text(1484).'</span><span style="display:inline-block; width:380px; text-align:right; margin-right:10px;';
        $qry = "SELECT expire FROM mod4_status WHERE project_id=".$this->get_project_id()." AND step_id=".$this->get_step().";";

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
        $qry = "SELECT status.id, mod4_group.link, mod6_dim.dimension FROM mod4_status status LEFT JOIN mod4_group ON status.id = mod4_group.mod4_id LEFT JOIN mod6_dim ON mod4_group.dimension_id = mod6_dim.id WHERE status.project_id=".$this->get_project_id()." AND status.step_id=".$this->get_step();

        $res = $this->db->query($qry);
        if($res && $res->num_rows) {
            echo '<table style="width:100%"><tr><th>'.$this->lingual->get_text(1326).'</th><th>'.$this->lingual->get_text(1327).'</th><th>'.$this->lingual->get_text(1328).'</th></tr>';

            while($dimension = $res->fetch_object()) {
                /* Set visibility */
                echo '<tr><td>'.$this->lingual->get_text($dimension->dimension).'</td><td><a href="'.SITEISO.'link/'.$dimension->id.'-'.$dimension->link.'/" target="_blank">'.$this->lingual->get_text(1486).'</a></td><td><a href="mailto:?subject='.htmlspecialchars($this->lingual->get_text(1112)).'&body='.htmlspecialchars($this->lingual->get_text(1116)).SITEISO.'link/'.$dimension->id.'-'.$dimension->link.'/">'.$this->lingual->get_text(1487).'</a></td></tr>';

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
        echo '<input type="submit" onClick="if (!confirm('."'".$this->lingual->get_text(1485)."'".')) { return false; }" value="'.$this->lingual->get_text(1482).'" />';
        echo '</form>';

    }
    public function save() {
        if(isset($_POST["act"])) {
            $this->set_status(1);

            if($_POST["act"]=="Activate" || $_POST["act"]=="Import data and activate") {
                $qry = sprintf("INSERT INTO mod4_status (project_id, step_id, expire) VALUES ('%s', '%s', '%s');",
                    $this->get_project_id(),
                    $this->get_step(),
                    date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s')." +1 day")));
                $res = $this->db->query($qry);

                if($res) {
                    $ins_id = $this->db->insert_id;
                    $this->generate_new_links($ins_id);

                    if ($_POST["act"]=="Import data and activate") {
                        $qry = sprintf("INSERT INTO mod4_group_input (mod4_id, dimension_id, criteria, investment, message, open_discussion, summary, rating) (SELECT '%d', dimension_id, criteria, investment, message, open_discussion, summary, rating FROM mod4_group_input as input LEFT JOIN mod4_status status ON status.id = input.mod4_id WHERE status.project_id = '%d' AND status.step_id = '%d');",
                            $ins_id,
                            $this->get_project_id(),
                            $this->get_contfrom_id());

                        if (!$this->db->query($qry)){
                            echo 'empty?';//$qry;
                        }
                    }
                }
            } else if($_POST["act"]=="Reactivate") {
                $qry = sprintf("UPDATE mod4_status SET expire='%s' WHERE project_id=%s AND step_id=%s;",
                    date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s')." +1 day")),
                    $this->get_project_id(),
                    $this->get_step());
                $res = $this->db->query($qry);
            } else if($_POST["act"]=="Deactivate") {
                $qry = sprintf("UPDATE mod4_status SET expire='%s' WHERE project_id=%s AND step_id=%s;",
                    date('Y-m-d H:i:s', time()),
                    $this->get_project_id(),
                    $this->get_step());
                $res = $this->db->query($qry);
            }



        } else if($_POST["save"]=="generate") {
            $qry = sprintf("SELECT id FROM mod4_status WHERE project_id='%s' AND step_id='%s';",
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
        $qry = 'SELECT id FROM mod4_status WHERE project_id = '.$this->get_project_id().' AND step_id = '.$this->get_step().";";
        $res = $this->db->query($qry);
        if ($res && $res->num_rows == 1) {
            $mod4_id = $res->fetch_object()->id;
            $this->db->query("DELETE FROM mod4_status WHERE mod4_status.id = ".$mod4_id." LIMIT 1;");
            $this->db->query("DELETE FROM mod4_group WHERE mod4_group.mod4_id = ".$mod4_id.";");
            $this->db->query("DELETE FROM mod4_group_input WHERE mod4_group_input.mod4_id = ".$mod4_id.";");
        }
    }

    public function generate_new_links($id) {
        $qry = "SELECT mod4_id FROM mod4_group WHERE mod4_id=".$id.";";
        $res = $this->db->query($qry);
        $exist = ($res && $res->num_rows);

        $qry = sprintf("SELECT id FROM mod6_dim WHERE step_id = '16';", $this->get_project_id());
        $result = $this->db->query($qry);
        if($result && $result->num_rows) {
            while($dimension = $result->fetch_object()) {
                if($exist) {
                    $qry = sprintf("UPDATE mod4_group SET link='%s' WHERE mod4_id='%s' AND dimension_id='%s';",
                        md5($this->get_project_id().$this->get_step().$dimension->id.time()),
                        $id,
                        $dimension->id);
                    $res = $this->db->query($qry);
                } else {
                    $qry = sprintf("INSERT INTO mod4_group (mod4_id, dimension_id, link) VALUES ('%s', '%s', '%s');",
                        $id,
                        $dimension->id,
                        md5($this->get_project_id().$this->get_step().$dimension->id.time()));
                    $res = $this->db->query($qry);
                }
            }
        }
    }

    public function report(){
        $rating = array($this->lingual->get_text(1259), $this->lingual->get_text(1260), $this->lingual->get_text(1261), $this->lingual->get_text(1262), $this->lingual->get_text(1263));

        echo '<h1 id="subsection'.$this->get_step().'">'.$this->get_header().'</h1>';

        //foreach group
        $qry = sprintf('SELECT id, dimension name FROM mod6_dim;');
        $result_group = $this->db->query($qry);
        while($group = $result_group->fetch_object()) {
            $printed_group=false;
            //foreach criterion
            $qry = sprintf("SELECT id, title, question, rating FROM mod6_crit");
            $res = $this->db->query($qry);
            while($crit = $res->fetch_object()) {
                $qry = "SELECT input.open_discussion, input.summary, input.rating, input.message FROM mod4_status status LEFT JOIN mod4_group_input input ON input.mod4_id = status.id WHERE status.project_id = ".$this->get_project_id()." AND step_id = ".$this->get_step()." AND input.dimension_id=".$group->id." AND input.criteria_id='".$crit->id."';";
                 $result = $this->db->query($qry);
                 if ($result && $result->num_rows){
                     if(!$printed_group) {
                         echo '<h2>'.$this->lingual->get_text($group->name).'</h2>';
                         $printed_group = true;
                     }
                     $row = $result->fetch_object();
                     echo '<h3>'.$this->lingual->get_text($crit->title).'</h3>';
                     echo '<br/><p><span class="title">Question:</span>'.$this->lingual->get_text($crit->question).'</p>';
                     echo '<br/><p><span class="title">'.$this->lingual->get_text(1176).':</span><br />'.nl2br(htmlspecialchars($row->open_discussion)).'</p>';
                     echo '<br/><p><span class="title">'.$this->lingual->get_text(1177).':</span><br />'.nl2br(htmlspecialchars($row->summary)).'</p>';
                     echo '<p><span class="title">'.$this->lingual->get_text(1178).':</span>'.'<span id="chosen">'.$rating[$row->rating+2].'</span></p>';
                     echo '<p><span class="title">'.$this->lingual->get_text(1175).':</span>'.nl2br(htmlspecialchars($row->message)).'</p>';

                 }

                 $fields = array("open_discussion"=>"","summary"=>"","rating"=>"0","message"=>"");
            }
        }

    }
}
