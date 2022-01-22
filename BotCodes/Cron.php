<?php
require_once("config.php");
$db = Database::getInstance();
$tg = Telegram::getInstance();

$botVersion = 3;
$timeStamp = 1606153602;
$postId = 22;
$age = time() - $timeStamp;
$days = floor($age / 86400);
$hours = floor(($age % 86400) / 3600);
$userCount = $db->getTableCount("_user_data");
$entranceCount = $db->getTableCount("_user_entrance");

$post = "#RoBot Name: <b>Place Book</b>" . "\n";
$post .= "Username: @PlaceBookBot" . "\n";
$post .= "Member(s): <code>" . number_format($userCount) . "</code>\n";
$post .= "Version: <code>" . $botVersion . "</code>\n \n";
$post .= "Created: <code>" . number_format($days) . "</code> day(s), <code>" . $hours . "</code> hour(s) ago.\n";
$post .= "- Time: <code>" . date("H:i:s", $timeStamp) . "</code> UTC\n";
$post .= "- Date: <code>" . date("Y-m-d", $timeStamp) . "</code>\n \n";
$post .= "<b>Abilities: </b>\n";
$post .= "v1: Save your locations and send them wherever and whenever you want." . "\n";
$post .= "v2: Added <b>/places</b> command for quick access." . "\n";
$post .= "v3: Added category mode option for better saved of places." . "\n";
$post .= "\n";
$post .= "<i>This post is updated automatically every hour.</i>\n";
$post .= "Channel: @ZarchiProjects";

$tg->editMessage(_ZARCHI_CHANNEL, $postId, $post);
//$tg->setDescription(_REPORT_CHANNEL, "Entrance(s): " . number_format($entranceCount));
