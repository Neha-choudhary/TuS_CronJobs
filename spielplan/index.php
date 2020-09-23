<?php
header("Content-Type: text/html; charset=UTF-8");

error_reporting(E_ALL ^ E_DEPRECATED);
ini_set("display_errors", "on");
date_default_timezone_set("Europe/Berlin");

define("DS", DIRECTORY_SEPARATOR);

$aGame = array();

$date_von = date("Y-m-d", time());
$date_bis = date("Y-m-d");
//$date_bis = date("Y-m-d", time() + 86400); // Morgen

// set url (Germania Lohauserholz)
$sUrl = "http://www.fussball.de/ajax.club.matchplan/-/id/00ES8GN8TC00003JVV0AG08LVUPGND5I/mime-type/JSON/mode/WIDGET/show-filter/false/max/99/";
$sUrl .= "datum-von/" . $date_von . "/datum-bis/" . $date_bis . "/show-venues/checked/offset/0";
$aGame = array_merge($aGame, generateGames($sUrl));

// Abgesetzte Spiele löschen
$aGameTmp = array ();
for($a = 0; $a < count($aGame); $a ++)
{
	if ($aGame[$a]['hide'] != 1)
	{
		$aGameTmp[] = $aGame[$a];
	}
}
$aGame = $aGameTmp;


// eMail vorbereiten
if (! empty($aGame) && count($aGame) > 0)
{
	$sBetreff = "Spielplan: Heutige Spiele (" . date("d.m.Y") . ")";
	$sMessage = "Guten Morgen<br />";
	$sMessage .= "anbei die Liste der heutigen Spiele:<br /><br />";
	
	for($a = 0; $a < count($aGame); $a ++)
	{
		if ($aGame[$a]['hide'] != 1)
		{
			$sMessage .= $aGame[$a]['Datum'] . "<br />";
			$sMessage .= "<b>" . $aGame[$a]['Verein'][0] . " - " . $aGame[$a]['Verein'][1] . "</b><br />";
			$sMessage .= $aGame[$a]['platz'] . "<br /><br />";
		}
	}
	
	$sMessage .= "Mit freundlichen Grüßen<br />";
	$sMessage .= "TuS Germania Lohauserholz-Daberg e.V.<br /><br />";
	$sMessage .= "(Diese eMail wurde automatisch erstellt)";
	
	// PHPMailer Classes
	require_once ('libs/PHPMailer-master/PHPMailerAutoload.php');
	
	$mail = new PHPMailer;
	
	$mail->CharSet = "UTF-8";
	$mail->isSMTP();                                      // Set mailer to use SMTP
	$mail->Host = '<host>';  // Specify main and backup SMTP servers
	$mail->SMTPAuth = true;                               // Enable SMTP authentication
	$mail->Username = '<Secret>';                 // SMTP username
	$mail->Password = '<Secret>';                           // SMTP password
	
	$mail->SMTPSecure = 'ssl';                            // Enable TLS encryption, `ssl` also accepted
	$mail->Port = 465;
	
	$mail->setFrom('patrickjaskulski@germania-lohauserholz.de', 'Spielplan Verteiler - TuS Germania Lohauserholz-Daberg e.V.');
	$mail->addAddress('verteiler-spielplan@germania-lohauserholz.de');     // Add a recipient
	
	$mail->isHTML(true);
	
	$mail->Subject = $sBetreff;
	$mail->Body    = $sMessage;

	if(!$mail->send()) {
		$from = "From: Patrick Jaskulski <patrickjaskulski@gmail.com>\n";
		$from .= "Reply-To: patrickjaskulski@gmail.com\n";
		$from .= "Content-Type: text/html; charset=UTF-8\n";
		mail("patrickjaskulski@gmail.com", "Mailer Error: ", $mail->ErrorInfo, $from);
	} else {
		//echo 'Message has been sent';
	}

	/**
	 * Auf Facebook posten
	 */
	require_once __DIR__ . DS . 'libs' . DS . 'Facebook' . DS . 'autoload.php';
	require_once 'classes' . DS . 'FacebookWallAbstract.class.php';

	$fbMessage = array();
	$fbMessage['message'] = "Unsere heutigen Spiele:\r\n\r\n";
	for($a = 0; $a < count($aGame); $a ++)
	{
		if ($aGame[$a]['hide'] != 1)
		{
			$sMannschaft = explode(" - ", $aGame[$a]['Datum']);
			$fbMessage['message'] .= trim($sMannschaft[1]) . "\r\n";
			$fbMessage['message'] .= $aGame[$a]['Verein'][0] . " - " . $aGame[$a]['Verein'][1] . "\r\n";
			$fbMessage['message'] .= $aGame[$a]['platz'] . "\r\n";
			$fbMessage['message'] .= "------------------------------";
			$fbMessage['message'] .= "\r\n";
		}
	}
	
	$objFacebook = new FacebookWall();
	$objFacebook->postToFacebookGroupWall($fbMessage);
	
	// Ältere Beiträge löschen
	$objFacebook->deleteOldPosts();
}



function generateGames($sUrl, $sAppendDate = "")
{
	// create curl resource
	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, $sUrl);

	// return the transfer as a string
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	// Encoding
	curl_setopt($ch, CURLOPT_ENCODING, "UTF-8");

	// $output contains the output string
	$output = curl_exec($ch);

	// close curl resource to free up system resources
	curl_close($ch);

	$json = json_decode($output, true);

	require_once ('libs/phpQuery/phpQuery.php');

	$doc = phpQuery::newDocumentHTML($json['html']);

	$aGame = array ();

	$i = 0;
	$b = 0;
	foreach ( pq('tr') as $tr )
	{
		switch (pq($tr)->attr('class')) {
			case 'odd row-headline' : // Headline - Datum
			case 'row-headline' :
				if ($i > 1)
				{
					$b ++;
				}
				$aGame[$b]['Datum'] = pq($tr)->find('td')->html() . $sAppendDate;
				break;
			case 'odd' : // Vereine
			case '' :
				foreach ( pq($tr)->find('td.column-club') as $td )
				{
					$aGame[$b]['Verein'][] = pq($td)->find('.club-name')->html();
				}
				$aGame[$b]['hide'] = 0;
				
				// Überprüfen ob Abgesetzt wurde
				foreach ( pq($tr)->find('span.info-text') as $info )
				{
					// Abgesetzt
					if (pq($info)->html() == "Abse." || pq($info)->html() == "Absetzung" || strpos(pq($info)->html(), "Nichtantritt") !== false )
					{
						$aGame[$b]['hide'] = 1;
					}
				}
				break;
			case 'odd row-venue hidden-small' : // Spielstätten
			case 'row-venue hidden-small' :
				$aGame[$b]['platz'] = pq($tr)->find('td:nth-child(2)')->html();
				break;
		}
		
		// Link
		foreach(pq($tr)->find("a") AS $atag)
		{
			if (strpos(pq($atag)->attr('href'), "/spiel/"))
			{
				$aGame[$b]['link'] = pq($atag)->attr('href');
			}
		}
		$i ++;
	}
	
	return $aGame;
}


