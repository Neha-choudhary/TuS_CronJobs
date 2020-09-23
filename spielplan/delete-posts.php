<?php
header("Content-Type: text/html; charset=UTF-8");

error_reporting(E_ALL ^ E_DEPRECATED);
ini_set("display_errors", "on");
date_default_timezone_set("Europe/Berlin");

define("DS", DIRECTORY_SEPARATOR);

require_once __DIR__ . DS . 'libs' . DS . 'Facebook' . DS . 'autoload.php';
require_once 'classes' . DS . 'FacebookWallAbstract.class.php';

$objFacebook = new FacebookWall();
$objFacebook->deleteOldPosts();