<?php

class Core
{

    private static $cr;
    private static $db;
    private static $tg;

    public static function getInstance()
    {
        if (self::$cr == null) {
            self::$cr = new Core();
        }
        return self::$cr;
    }

    public function __construct()
    {
        self::$db = Database::getInstance();
        self::$tg = Telegram::getInstance();
    }

    public function sendUserEntrance($chatId, $entrance, $function)
    {
        $message = "User: <code>" . $chatId . "</code>\n";
        $message .= "Name: <code>" . self::$tg->getFirstName() . " " . self::$tg->getLastName() . "</code>\n";
        $message .= "Profile: <a href='tg://user?id=" . $chatId . "'>Click To Go</a>" . "\n";
        $message .= "Entrace: <code>" . $entrance . "</code>" . "\n";
        $message .= "Function: <code>" . $function . "</code>" . "\n";
        $time = $this->getTimeStamp(true);
        $message .= "TimeStamp: <code>" . $time . "</code>\n";
        self::$tg->sendMessage(_REPORT_CHANNEL, $message);
        self::$db->insertEntrance($chatId, $time, $entrance);
    }

    public function setStartMenu($chatId, $text)
    {
        self::$tg->setChatAction($chatId);
        $this->sendUserEntrance($chatId, $text, __FUNCTION__);
        self::$db->insertUserData($chatId);
        $message = "Hi dear friend" . "\n";
        $message .= "Please selct one of them .." . "\n";
        $body[0]['text'] = "Your Place(s)";
        $body[0]['callback_data'] = "UserPlace";
        $body[1]['text'] = "Set new Place";
        $body[1]['callback_data'] = "SetPlace";
        $buttons = array('body' => $body, 'bodyVertical' => 2);
        self::$tg->sendInlineKeyboard($chatId, $message, "text", null, $buttons);
    }

    public function setPlaceMenu($chatId, $text)
    {
        self::$tg->setChatAction($chatId);
        $this->sendUserEntrance($chatId, $text, __FUNCTION__);
        $message = "Send to me a location" . "\n";
        $message .= "(<i>Click 📎 and send</i> <b>Location</b>)";
        self::$tg->sendMessage($chatId, $message);
    }

    public function setUserPlaceMenu($chatId, $text)
    {
        self::$tg->setChatAction($chatId);
        $this->sendUserEntrance($chatId, $text, __FUNCTION__);
        $userData = self::$db->getUserData($chatId);
        $message = "Please select one of the following categorys ..";
        $types = explode("|", $userData['_hashtag']);
        for ($i = 0; $i < sizeOf($types); $i++) {
            $body[$i]['text'] = $types[$i];
            $body[$i]['callback_data'] = "ShowList-" . $types[$i];
        }
        $buttons = array('body' => $body, 'bodyVertical' => 2);
        self::$tg->sendInlineKeyboard($chatId, $message, "text", null, $buttons);
    }

    public function setShowList($chatId, $text)
    {
        self::$tg->setChatAction($chatId);
        $this->sendUserEntrance($chatId, $text, __FUNCTION__);
        $type = str_replace("ShowList-", "", $text);
        $userPlaces = self::$db->getUserPlaces($chatId, $type);
        if (sizeOf($userPlaces) > 0) {
            $message = "Your place(s):" . "\n";
            foreach ($userPlaces as $place) {
                $message .= "Name: <code>" . base64_decode($place['name']) . "</code>" . "\n";
                $timestamp = ($place['id'] / 1000000);
                $message .= "Created: <code>" . date('Y-m-d, H:i:s', $timestamp) . "</code> UTC" . "\n";
                $message .= "Show: /P" . $place['id'] . "\n \n";
            }
        } else {
            $message = "You have no place." . "\n";
        }
        $body[0]['text'] = "Back to menu";
        $body[0]['callback_data'] = "Start";
        $buttons = array('body' => $body, 'bodyVertical' => 1);
        self::$tg->sendInlineKeyboard($chatId, $message, "text", null, $buttons);
    }

    public function setLocationPlaceMenu($chatId)
    {
        self::$tg->setChatAction($chatId);
        $data = self::$tg->getLocationData();
        $location = $data['lat'] . "|" . $data['lon'];
        $this->sendUserEntrance($chatId, $location, __FUNCTION__);
        $placeId = $this->getTimeStamp(true);
        self::$db->insertUserPlace($placeId, $location, $chatId);
        self::$db->updateUserData($chatId, "_level", "GetPlaceName-" . $placeId);
        $message = "Send name for this place ..";
        $body[0]['text'] = "Cancel save location";
        $body[0]['callback_data'] = "CancelFunction";
        $buttons = array('body' => $body, 'bodyVertical' => 1);
        self::$tg->sendInlineKeyboard($chatId, $message, "text", null, $buttons);
    }

    public function getPlaceNameMenu($chatId, $helper, $text)
    {
        self::$tg->setChatAction($chatId);
        $this->sendUserEntrance($chatId, $helper, __FUNCTION__);
        $placeId = str_replace("GetPlaceName-", "", $helper);
        self::$db->updateUserData($chatId, "_level", "Menu");
        $message = null;
        if ($text != "CancelFunction") {
            self::$db->updatePlaceData($placeId, "_name", base64_encode($text));
            self::$db->updatePlaceData($placeId, "_active", "1");
            $this->getEditCategoryMenu($chatId, "EditCategory-" . $placeId);
        } else {
            self::$db->updatePlaceData($placeId, "_name", "Deleted");
            $message = "Your location has been deleted." . "\n";
            $body[0]['text'] = "Back to menu";
            $body[0]['callback_data'] = "Start";
            $buttons = array('body' => $body, 'bodyVertical' => 1);
            self::$tg->sendInlineKeyboard($chatId, $message, "text", null, $buttons);
        }
    }

    public function setLocationView($chatId, $text)
    {
        self::$tg->setChatAction($chatId);
        $this->sendUserEntrance($chatId, $text, __FUNCTION__);
        $placeId = str_replace("/P", "", $text);
        $placeData = self::$db->getPlaceData($placeId);
        $message = null;
        $body = array();
        if ($placeData['_chatId'] == $chatId) {
            $message = "Name: <code>" . base64_decode($placeData['_name']) . "</code>" . "\n";
            $category = $placeData['_type'] == "NoType" ? "Not set." : "#" . $placeData['_type'];
            $message .= "Category: " . $category . "\n";
            $timeStamp = ($placeId / 1000000);
            $age = time() - $timeStamp;
            $days = floor($age / 86400);
            $hours = floor(($age % 86400) / 3600);
            $message .= "Created: <code>" . number_format($days) . "</code> day(s), <code>" . $hours . "</code> hour(s) ago.\n";
            $message .= "- Time: <code>" . date("H:i:s", $timeStamp) . "</code> UTC\n";
            $message .= "- Date: <code>" . date("Y-m-d", $timeStamp) . "</code>\n";
            $message .= "Location: <code>" . str_replace("|", ", ", $placeData['_location']) . "</code>" . "\n";
            $header[0]['text'] = "See the location on map";
            $header[0]['callback_data'] = "ShowMap-" . $placeId;
            $body[0]['text'] = "Edit name";
            $body[0]['callback_data'] = "EditName-" . $placeId;
            if ($placeData['_type'] == "NoType") {
                $body[1]['text'] = "Set category";
            } else {
                $body[1]['text'] = "Edit category";
            }
            $body[1]['callback_data'] = "EditCategory-" . $placeId;
            $body[2]['text'] = "Back";
            $body[2]['callback_data'] = "Start";
            $body[3]['text'] = "Delete map";
            $body[3]['callback_data'] = "Delete-" . $placeId;
            $buttons = array(
                'header' => $header, 'headerVertical' => 1,
                'body' => $body, 'bodyVertical' => 2
            );
        } else {
            $message = "This location is private.";
            $body[0]['text'] = "Back";
            $body[0]['callback_data'] = "Start";
            $buttons = array('body' => $body, 'bodyVertical' => 1);
        }
        self::$tg->sendInlineKeyboard($chatId, $message, "text", null, $buttons);
    }

    public function setEditMapMenu($chatId, $text)
    {
        self::$tg->setChatAction($chatId);
        $this->sendUserEntrance($chatId, $text, __FUNCTION__);
        $placeId = str_replace("EditName-", "", $text);
        self::$db->updateUserData($chatId, "_level", "GetNameEdit-" . $placeId);
        $message = "Send name for this place ..";
        $body[0]['text'] = "Cancel Edit Name";
        $body[0]['callback_data'] = "CancelFunction";
        $buttons = array('body' => $body, 'bodyVertical' => 1);
        self::$tg->sendInlineKeyboard($chatId, $message, "text", null, $buttons);
    }

    public function getEditNameMenu($chatId, $helper, $text)
    {
        self::$tg->setChatAction($chatId);
        $this->sendUserEntrance($chatId, $helper, __FUNCTION__);
        $placeId = str_replace("GetNameEdit-", "", $helper);
        self::$db->updateUserData($chatId, "_level", "Menu");
        if ($text != "CancelFunction") {
            self::$db->updatePlaceData($placeId, "_name", base64_encode($text));
        }
        $this->setLocationView($chatId, "/P" . $placeId);
    }

    public function setShowMap($chatId, $text)
    {
        self::$tg->setChatAction($chatId);
        $this->sendUserEntrance($chatId, $text, __FUNCTION__);
        $placeId = str_replace("ShowMap-", "", $text);
        $placeData = self::$db->getPlaceData($placeId);
        $place = explode("|", $placeData['_location']);
        self::$tg->sendLocation($chatId, $place[0], $place[1]);
    }

    public function setDeleteMenu($chatId, $text)
    {
        self::$tg->setChatAction($chatId);
        $this->sendUserEntrance($chatId, $text, __FUNCTION__);
        $placeId = str_replace("Delete-", "", $text);
        self::$db->updatePlaceData($placeId, "_active", "0");
        $message = "Your location has been deleted." . "\n";
        $body[0]['text'] = "Back to menu";
        $body[0]['callback_data'] = "Start";
        $buttons = array('body' => $body, 'bodyVertical' => 1);
        self::$tg->sendInlineKeyboard($chatId, $message, "text", null, $buttons);
    }

    public function getEditCategoryMenu($chatId, $text)
    {
        self::$tg->setChatAction($chatId);
        $this->sendUserEntrance($chatId, $text, __FUNCTION__);
        $placeId = str_replace("EditCategory-", "", $text);
        $userData = self::$db->getUserData($chatId);
        self::$db->updateUserData($chatId, "_level", "EditCategory-" . $placeId);
        $message = "Please select one of the following categorys, you can also create a new category by entering the word";
        $types = explode("|", $userData['_hashtag']);
        for ($i = 0; $i < sizeOf($types); $i++) {
            $body[$i]['text'] = $types[$i];
            $body[$i]['callback_data'] = $types[$i];
        }
        $buttons = array('body' => $body, 'bodyVertical' => 2);
        self::$tg->sendInlineKeyboard($chatId, $message, "text", null, $buttons);
    }

    public function setEditCategoryMenu($chatId, $helper, $text)
    {
        self::$tg->setChatAction($chatId);
        $this->sendUserEntrance($chatId, $text, __FUNCTION__);
        self::$db->updateUserData($chatId, "_level", "Menu");
        $placeId = str_replace("EditCategory-", "", $helper);
        self::$db->updatePlaceData($placeId, "_type", $text);
        $this->setLocationView($chatId, "/P" . $placeId);
    }

    public function setDefaultMessage($chatId, $text)
    {
        $this->sendUserEntrance($chatId, $text, __FUNCTION__);
    }

    /*     * * * * * * * * * * * * * * * * * * * * * *
     * ╔═╗╔═╗  ╔╗╔╗             ╔╗╔╗      ╔╗      *
     * ║║╚╝║║ ╔╝╚╣║            ╔╝╚╣║      ║║      *
     * ║╔╗╔╗╠═╩╗╔╣╚═╦══╦═╗ ╔╗╔╦═╩╗╔╣╚═╦══╦═╝╠══╗  *
     * ║║║║║║╔╗║║║╔╗║║═╣╔╝ ║╚╝║║═╣║║╔╗║╔╗║╔╗║══╣  *
     * ║║║║║║╚╝║╚╣║║║║═╣║  ║║║║║═╣╚╣║║║╚╝║╚╝╠══║  *
     * ╚╝╚╝╚╩══╩═╩╝╚╩══╩╝  ╚╩╩╩══╩═╩╝╚╩══╩══╩══╝  *
     * * * * * * * * * * * * * * * * * * * * * * */


    public function getTimeStamp($report = false)
    {
        $data = explode(" ", microtime());
        if ($report)
            $mic = str_replace("0.", "", number_format($data[0], 6));
        else
            $mic = str_replace("0.", "", number_format($data[0], 4));
        return time() . $mic;
    }
}
