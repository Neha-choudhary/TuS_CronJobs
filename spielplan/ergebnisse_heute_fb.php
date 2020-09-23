<?php
error_reporting(E_ALL ^ E_DEPRECATED);
ini_set("display_errors", "on");
date_default_timezone_set("Europe/Berlin");

define("DS", DIRECTORY_SEPARATOR);

require_once __DIR__ . DS . 'libs' . DS . 'phpQuery' . DS . 'phpQuery.php';
require_once __DIR__ . DS . 'libs' . DS . 'Facebook' . DS . 'autoload.php';
require_once 'classes' . DS . 'FacebookWallAbstract.class.php';
require_once 'classes' . DS . 'Spielplaene.class.php';

$objSpielplaene = new Spielplaene();
$ergebnisseHTML = $objSpielplaene->getSpielergebnisseToday();

$doc = phpQuery::newDocumentHTML($ergebnisseHTML);

$aErgebnis = array();

$table = pq('#SPIELE')->html();
if (strlen($table) > 0) {
	foreach ( pq($table)->find('tr') as $tr )
	{
		// Wenn nicht Kopfdaten
		if (pq($tr)->attr('class') != 'listtexthead') 
		{
			// Mannschaftsart / Klasse			
			$sArt = pq($tr)->find('td:nth-child(7)')->html();
			
			// Liga
			$sLiga = pq($tr)->find('td:nth-child(8)')->html();
			
			// Welche Mannschaften
			$sMannschaften = pq($tr)->find('td:nth-child(5) span')->html();
			$sMannschaften = str_replace("<br>", " - ", trim($sMannschaften));
			
			$bHasErgebnis = false;
			$tdErgebnis = pq($tr)->find('td:nth-child(9)');
			$sErgebnis = "";
			
			// Ergebnisse bei G und F Junioren ausblenden
			if ($sArt != "G-Junioren" && $sArt != "F-Junioren")
			{
				$i = 0;
				foreach(pq($tdErgebnis)->find(':input') as $inputErgebnis)
				{
					if (pq($inputErgebnis)->attr('value') != NULL) {
						$bHasErgebnis = true;
						$sErgebnis .= pq($inputErgebnis)->attr('value');
						if ($i == 0) {
							$sErgebnis .= ':';
						}
						$i++;
					}
				}
			}

			if ($bHasErgebnis)
					$aErgebnis[] = array('Art' => $sArt, 'Liga' => $sLiga, 'Mannschaften' => $sMannschaften, 'Ergebnis' => $sErgebnis);
		}
		
	}
}

if (count($aErgebnis) > 0)
{
	$fbMessage = array();
	$fbMessage['message'] = "Die Ergebnisse unserer heutigen Spiele:\r\n\r\n";
	for($a = 0; $a < count($aErgebnis); $a ++)
	{
		$fbMessage['message'] .= $aErgebnis[$a]['Art'] . " - " . $aErgebnis[$a]['Liga'] . "\r\n";
		$fbMessage['message'] .= $aErgebnis[$a]['Mannschaften'] . "\r\n";
		$fbMessage['message'] .= 'Ergebnis: ' . $aErgebnis[$a]['Ergebnis'] . "\r\n";
		$fbMessage['message'] .= "------------------------------\r\n";
	}
	$objFacebook = new FacebookWall();
	$objFacebook->postToFacebookGroupWall($fbMessage);
	
	echo '<br /><br />\r\n<pre>';
	print_R($fbMessage['message']);
	echo '</pre>';
}