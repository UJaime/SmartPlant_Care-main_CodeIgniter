<?php
require_once APPPATH . 'Libraries/Database.php';

class Planta {
    public static function all() {
        $db = Database::connect();
        return $db->query("SELECT * FROM plantas");
    }
}