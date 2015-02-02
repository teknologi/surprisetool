<?php
class mod_1 extends Module {
    public function load() {
        $refs = array();
        $qry = "SELECT title, html FROM mod1 WHERE step_id=".$this->get_step().";"; //and check user

        $res = $this->db->query($qry);

        if($res && $res->num_rows) {
            $data = $res->fetch_object();
            echo '<h1>'.$this->get_header().'</h1>';
            echo '<p>'.$this->get_paragraph().'</p>';
        } else {
            echo 'Could not find data...';
        }
    }
    public function save() {

    }

    public function report(){
        return "";
    }
}
