<?php
/**
 * Bevorstehende Spiele für das Wochenende vorbereiten.
 * 
 * Wird dann als eMail an den WA verschickt.
 */
header("Content-Type: text/html; charset=UTF-8");

error_reporting(E_ALL ^ E_DEPRECATED);
ini_set("display_errors", "on");

$aGame = array();

// 1482015600 - 18.12.2016 - 00:00 Uhr

$samstag = strtotime('next saturday') + 2400; // +2400 da sonst durch die Zeitzone ggf. der Freitag mit 23:00 Uhr genommen wird.
$sonntag = strtotime('next sunday') + 2400; // +2400 da sonst durch die Zeitzone ggf. der Freitag mit 23:00 Uhr genommen wird.

// set url (Germania Lohauserholz)
$sUrl = "http://www.fussball.de/ajax.club.matchplan/-/id/00ES8GN8TC00003JVV0AG08LVUPGND5I/mime-type/JSON/mode/WIDGET/show-filter/false/max/99/";
$sUrl .= "datum-von/" . date("Y-m-d", $samstag) . "/datum-bis/" . date("Y-m-d", $sonntag) . "/show-venues/checked/offset/0";
$aGame = array_merge($aGame, generateGames($sUrl));

// Abgesetzte Spiele löschen
$aGameTmp = array ();
for($a = 0; $a < count($aGame); $a ++)
{
	// Nur Jugendspiele
	// Herrenspiele ausfiltern
	if (strpos($aGame[$a]['Datum'], "Herren") !== false) {
		$aGame[$a]['hide'] = 1;
	}
	
	if ($aGame[$a]['hide'] != 1)
	{
		$aGameTmp[] = $aGame[$a];
	}
}
$aGame = $aGameTmp;


// eMail vorbereiten
if (! empty($aGame) && count($aGame) > 0)
{
	$sBetreff = "TuS Germania Lohauserholz-Daberg e.V.: Spiele des kommenden Wochenende der Jugendabteilung (" . date("d.m.Y", $samstag) . ")";
	$sMessage = "Sehr geehrte Damen und Herren,<br /><br />";
	$sMessage .= "anbei erhalten Sie die Liste der Spiele unserer Jugendabteilung für das kommende Wochenende:<br />";
	$sMessage .= "Wir bitten um Veröffentlichung in der Presse.<br /><br />";
	
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
	$sMessage .= "Patrick Jaskulski<br />";
	$sMessage .= "Spielbetrieb Jugendabteilung / sportl. Leiter<br />";
	$sMessage .= "TuS Germania Lohauserholz-Daberg e.V.<br /><br /><br />";
	$sMessage .= "(Diese eMail wurde automatisch erstellt)";

	// PHPMailer Classes
	require_once ('libs/PHPMailer-master/PHPMailerAutoload.php');

	$mail = new PHPMailer;
	
	$mail->CharSet = "UTF-8";
	$mail->isSMTP();                                      // Set mailer to use SMTP
	$mail->Host = 'smtp.strato.de';  // Specify main and backup SMTP servers
	$mail->SMTPAuth = true;                               // Enable SMTP authentication
	$mail->Username = '<secret>';                 // SMTP username
	$mail->Password = '<secret>';                           // SMTP password
	$mail->SMTPSecure = 'ssl';                            // Enable TLS encryption, `ssl` also accepted
	$mail->Port = 465;
	
	$mail->setFrom('presse@germania-lohauserholz.de', 'TuS Germania Lohauserholz-Daberg e.V. - Presse');
	$mail->addAddress('sport@wa.de');     // Add a recipient
	$mail->addAddress('patrickjaskulski@gmail.com');
	
	$mail->isHTML(true);

	$mail->Subject = $sBetreff;
	$mail->Body    = $sMessage;
	
	if(!$mail->send()) {
		//echo 'Message could not be sent.';
		//echo 'Mailer Error: ' . $mail->ErrorInfo;
		$from = "From: Patrick Jaskulski <patrickjaskulski@gmail.com>\n";
		$from .= "Reply-To: patrickjaskulski@gmail.com\n";
		$from .= "Content-Type: text/html; charset=UTF-8\n";
		mail("patrickjaskulski@gmail.com", "Mailer Error: ", $mail->ErrorInfo, $from);
	} else {
		//echo 'Message has been sent';
	}
	

}


function generateGames($sUrl, $sAppendDate = "")
{
	// create curl resource
	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, $sUrl);

	// return the transfer as a string
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

	// Encoding
	// curl_setopt($ch, CURLOPT_ENCODING , "gzip");
	curl_setopt($ch, CURLOPT_ENCODING, "UTF-8");

	// $output contains the output string
	$output = curl_exec($ch);

	// close curl resource to free up system resources
	curl_close($ch);

	$json = json_decode($output, true);

	// echo '<pre>';
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
					if (pq($info)->html() == "Abse." || pq($info)->html() == "Absetzung")
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
		$i ++;
	}
	
	return $aGame;
}


