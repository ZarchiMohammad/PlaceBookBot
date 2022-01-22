<?php

class Database
{

    private $connection;
    private static $db;

    public static function getInstance($option = null)
    {
        if (self::$db == null) {
            self::$db = new Database($option);
        }
        return self::$db;
    }

    private function __construct($option = null)
    {
        if ($option != null) {
            $host = $option['host'];
            $user = $option['user'];
            $pass = $option['pass'];
            $name = $option['name'];
        } else {
            global $config;
            $host = $config['host'];
            $user = $config['user'];
            $pass = $config['pass'];
            $name = $config['name'];
        }

        $this->connection = new mysqli($host, $user, $pass, $name);
        if ($this->connection->connect_error) {
            echo "Connection failed: " . $this->connection->connect_error;
            exit;
        }

        $this->connection->query("SET NAMES 'ut8'");
    }

    public function query($sql)
    {
        return $this->connection->query($sql);
    }

    public function insertUserData($chatId)
    {
        $this->query("INSERT INTO `_user_data` VALUES (NULL, '" . time() . "', '" . $chatId . "', 'Menu', 'Home|Work|Religious|Memories|Nature|Visited')");
    }

    public function insertEntrance($chatId, $time, $entrance)
    {
        $this->query("INSERT INTO `_user_entrance` VALUES (NULL, '" . $chatId . "', '" . $time . "', '" . $entrance . "')");
    }

    public function insertUserPlace($placeId, $location, $chatId)
    {
        $this->query("INSERT INTO `_place_data` VALUES ('" . $placeId . "', '" . $location . "','" . $chatId . "', 'NoType', 'NoName', '0')");
    }

    public function getTableCount($table)
    {
        $result = $this->query("SELECT COUNT(*) AS _count FROM `" . $table . "` ");
        return $result->fetch_array()['_count'];
    }

    public function updateUserData($chatId, $key, $value)
    {
        $sql = "UPDATE `_user_data` SET `" . $key . "` = '" . $value . "' WHERE `_chatId` LIKE '" . $chatId . "'";
        $this->query($sql);
    }

    public function updatePlaceData($placeId, $key, $value)
    {
        $sql = "UPDATE `_place_data` SET `" . $key . "` = '" . $value . "' WHERE `_id` = '" . $placeId . "'";
        $this->query($sql);
    }

    public function getUserData($chatId)
    {
        $result = $this->query("SELECT * FROM `_user_data` WHERE `_chatId` LIKE '" . $chatId . "'");
        return $result->fetch_array();
    }

    public function getPlaceData($PlaceId)
    {
        $result = $this->query("SELECT * FROM `_place_data` WHERE `_id` LIKE '" . $PlaceId . "'");
        return $result->fetch_array();
    }

    public function getUserPlaces($chatId, $type)
    {
        $value = array();
        $result = $this->query("SELECT * FROM `_place_data` WHERE `_chatId` LIKE '" . $chatId . "' AND `_type` LIKE '" . $type . "' AND `_active` = 1 ORDER BY `_id` DESC");
        while ($fetch = mysqli_fetch_array($result)) {
            $base = array();
            $base['id'] = $fetch['_id'];
            $base['name'] = $fetch['_name'];
            $value[] = $base;
        }
        return $value;
    }
}
