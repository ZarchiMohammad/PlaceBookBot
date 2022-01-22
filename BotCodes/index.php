<?php

require_once("config.php");
$json = file_get_contents('php://input');
$tg = Telegram::getInstance($json);
$db = Database::getInstance();
$cr = Core::getInstance();

$chatId = $tg->getChatId();
$text = $tg->getMessageText();
$level = $db->getUserData($chatId)['_level'];

if ($tg->isChannelPost() === false && $tg->isEditChannelPost() === false) {
    if ($text == "/start") {
        $cr->setStartMenu($chatId, $text);
    } else {
        if ($tg->getLocation() === true) {
            $cr->setLocationPlaceMenu($chatId);
        } else {
            if ($level == "Menu") {
                $helper = $text;
                if (strpos($text, "/P") !== false) {
                    $text = "ShowLocation";
                } elseif (strpos($text, "ShowMap-") !== false) {
                    $text = "ShowMap";
                } elseif (strpos($text, "ShowList-") !== false) {
                    $text = "ShowList";
                } elseif (strpos($text, "EditName-") !== false) {
                    $text = "EditName";
                } elseif (strpos($text, "EditCategory-") !== false) {
                    $text = "EditCategory";
                } elseif (strpos($text, "Delete-") !== false) {
                    $text = "Delete";
                }

                switch ($text) {
                    case "Start":
                        $cr->setStartMenu($chatId, $text);
                        break;
                    case "SetPlace":
                        $cr->setPlaceMenu($chatId, $text);
                        break;
                    case "/places":
                    case "UserPlace":
                        $cr->setUserPlaceMenu($chatId, $text);
                        break;
                    case "ShowLocation":
                        $cr->setLocationView($chatId, $helper);
                        break;
                    case "EditName":
                        $cr->setEditMapMenu($chatId, $helper);
                        break;
                    case "EditCategory":
                        $cr->getEditCategoryMenu($chatId, $helper);
                        break;
                    case "ShowMap":
                        $cr->setShowMap($chatId, $helper);
                        break;
                    case "ShowList":
                        $cr->setShowList($chatId, $helper);
                        break;
                    case "Delete":
                        $cr->setDeleteMenu($chatId, $helper);
                        break;
                    default:
                        $cr->setDefaultMessage($chatId, $helper);
                        break;
                }
            } else {
                $helper = $level;
                if (strpos($level, "GetPlaceName-") !== false) {
                    $level = "GetPlaceName";
                } elseif (strpos($level, "GetNameEdit-") !== false) {
                    $level = "GetNameEdit";
                } elseif (strpos($level, "EditCategory-") !== false) {
                    $level = "EditCategory";
                }

                switch ($level) {
                    case "GetPlaceName":
                        $cr->getPlaceNameMenu($chatId, $helper, $text);
                        break;
                    case "GetNameEdit":
                        $cr->getEditNameMenu($chatId, $helper, $text);
                        break;
                    case "EditCategory":
                        $cr->setEditCategoryMenu($chatId, $helper, $text);
                        break;
                }
            }
        }
    }
}
