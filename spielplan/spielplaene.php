<?php
error_reporting(E_ALL ^ E_DEPRECATED);
ini_set("display_errors", "on");
date_default_timezone_set("Europe/Berlin");

define("DS", DIRECTORY_SEPARATOR);

require_once 'classes' . DS . 'Spielplaene.class.php';

$objSpielplaene = new Spielplaene();
$objSpielplaene->sendSpielplanMail();