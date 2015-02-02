<?php
class mod_7 extends Module {
    public function load() {


        echo '<h2>'.$this->get_header().'</h2>';
        echo '<p>'.$this->get_paragraph().'</p><br />';

        echo '<input id="refresher" type="checkbox" name="refresher" checked /><label for="refresher">'.$this->lingual->get_text(1264).'</label><br />';
        $qry = 'SELECT step1.id, step1.contfrom_id, step1.pid FROM steps step1 JOIN steps step2 ON step1.pid = step2.pid AND step1.num <= step2.num AND step2.id = '.$this->get_step().' WHERE step1.module_id=7 AND step1.template_id=1 ORDER BY step1.num';

        $res = $this->db->query($qry);
        if($res && $res->num_rows) {
            $i = 1;
            while($comp_round = $res->fetch_object()) {
                if($res->num_rows > 1) {
                    echo '<input id="compare'.$i.'" type="radio" name="compare" value="'.$comp_round->contfrom_id.'"'.($this->get_step()==$comp_round->id ? ' checked' : '').' /><label for="compare'.$i.'">'.$this->lingual->get_text(1333).' '.($i++).'</label><br />';
                } else {
                    $contfrom = $comp_round->contfrom_id;
                }
            }
        } else {
            exit("Could not find step data...");
        }

        echo '<a href="#" id="fullscreen">'.$this->lingual->get_text(1488).'</a>';
        echo '<div id="bigtable">';
        echo $this->ajax();
        echo '</div>';

        echo '<script type="text/javascript">'."\n".'$(document).ready(function() {';
        echo 'setInterval(function(){ if ($("#refresher").attr('."'checked'".') == '."'checked'".') {';
        echo 'updatemonster();';
        echo '}} ,10000);';

        echo 'function requestFullScreen() { var elem = document.getElementById("bigtable"); var requestMethod = elem.requestFullScreen || elem.webkitRequestFullScreen || elem.mozRequestFullScreen || elem.msRequestFullScreen; if (requestMethod) { requestMethod.call(elem); } }';

        echo '$("#fullscreen").click(function() { requestFullScreen(); return false; });';
        echo '$("input[name=compare]").click(function() { updatemonster(); });';

        echo 'function updatemonster() {';
        echo '$.post("", { content: "only", assessid: ($("input[name=compare]").length ? $("input[name=compare]:checked").val() : '.(int)$contfrom.')'.' }, function(data){ $("#bigtable").html(data); });';
        echo '}';

        echo '});</script>';
    }


    public function ajax() {
        $gethtml = "";

        $qry = "SELECT dim.dimension FROM mod6_dim dim ORDER BY dim.id";
        $res = $this->db->query($qry);

        if($res && $res->num_rows) {
            $invest_amount = $res->num_rows;
            echo '<table class="monster"><tr><th rowspan=2 class="col1">'.$this->lingual->get_text(1265).'</th><th colspan='.$res->num_rows.'>'.$this->lingual->get_text(1266).'</th></tr><tr>';
            $group_amount = 0;
            while($row = $res->fetch_object()) {
                ++$group_amount;
                echo '<th>'.$this->lingual->get_text($row->dimension).'</th>';
            }
            echo '</tr>';

            $qry = 'SELECT crit.id crit_id, crit.title, dim.id dim_id, dim.dimension, stat.id stat_id, mod4_group.link, input.open_discussion, input.rating FROM mod6_crit crit LEFT JOIN mod4_status stat ON stat.project_id = '.$this->get_project_id().' LEFT JOIN mod4_group ON stat.id = mod4_group.mod4_id LEFT JOIN mod4_group_input AS input ON input.mod4_id = stat.id AND input.criteria_id = crit.id AND input.dimension_id = mod4_group.dimension_id LEFT JOIN mod6_dim dim ON mod4_group.dimension_id = dim.id WHERE stat.project_id = '.$this->get_project_id().' AND crit.step_id = 16 ORDER BY crit.num,dim.id;';

            $res = $this->db->query($qry);
            if($res && $res->num_rows) {
                $group_index = 0;
                while($row = $res->fetch_object()) {
                    if($group_index == 0) {
                        echo '<td class="col2">'.$this->lingual->get_text($row->title).'</td>';
                    }
                    ++$group_index;

                    $workshop_url = SITEISO.'link/'.$row->stat_id.'-'.$row->link.'/'.$row->crit_id.'/';
                    echo '<td><a class="rate'.(is_null($row->rating) ? '' : ($row->rating + 3)).'" href="'.$workshop_url.'" title="'.(is_null($row->open_discussion) ? 'Nothing entered' : htmlspecialchars($row->open_discussion)).'" target="_blank">'.'&nbsp;'."</a></td>";

                        if($group_index == $group_amount) {
                            $group_index = 0;
                            echo '</tr>';
                        }
                    }
                }
            echo "</table>";

        }
    }

    public function save() {
        if(isset($_POST["act"])) {
            if($_POST["act"]=="Activate") {
                $qry = sprintf("INSERT INTO mod4_status (project_id, step_id, expire) VALUES ('%s', '%s', '%s');",
                    $this->get_project_id(),
                    $this->get_step(),
                    date('Y-m-d H:i:s', strtotime(date('Y-m-d H:i:s')." +1 day")));
                $res = $this->db->query($qry);

                if($res) {
                    $this->generate_new_links( $this->db->insert_id);
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
        } else if(isset($_POST["generate"])) {
                $qry = sprintf("SELECT id FROM mod4_status WHERE project_id='%s' AND step_id='%s';",
                    $this->get_project_id(),
                    $this->get_step());
                $res = $this->db->query($qry);
                if($res && $res->num_rows) {
                    $this->generate_new_links($res->fetch_object()->id);
                } else {
                    //should not happen
                }
        }
    }
    public function generate_new_links($id) {
        $qry = "SELECT mod4_id FROM mod4_group WHERE mod4_id=".$id.";";
        $res = $this->db->query($qry);
        $exist = ($res && $res->num_rows);

        $qry = "SELECT id FROM dessi_dimensions;";
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

    }
}
