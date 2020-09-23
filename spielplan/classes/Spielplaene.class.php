<?php
class Spielplaene {
	
	private $ckfile = null;
	
	private $_curlUserAgent = 'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:56.0) Gecko/20100101 Firefox/56.0';
	private $_curlHeader = array(
				'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
				'Accept-Language: de,en-US;q=0.7,en;q=0.3',
				'Accept-Encoding: gzip, deflate, br',
				'Content-Type: application/x-www-form-urlencoded',
				'Connection: keep-alive',
	);
	
	private $_curlLoginUrl = "<url>";
	private $_loginUser = "<login>";
	private $_loginPass = "<pass>";
	
	private $_nextMonday = "";
	private $_nextSunday = "";
	private $_nextWeek = 0;
	private $_fileName = "";
	
	/**
	 * Spielplaene
	 */
	public function __construct() 
	{
	}
	
	
	/**
	 * postSpielergebnisseToFacebook
	 * 
	 * Die heutigen Ergebnisse der Spiele auf Facebook veröffentlichen 
	 */
	public function getSpielergebnisseToday() 
	{
		// temporäre Cookie File erstellen
		$this->_createTempFile();
		
		// Anmeldung durchführen für die SessionID
		$this->_getSession();
		
		// Zuerst die Seite Ergebnismeldung > Vereinsmeldung öffnen, da sonst der Ausdruck als PDF nicht funktioniert.
		$this->_getSeiteErgebnismeldung();
		
		// Ergebnisse des heutigen Tages abrufen
		$output =  $this->_getSeiteErgebnismeldungToday();
		
		// temporäre Cookie File löschen
		$this->_deleteTempFile();
		
		return $output;
	}
	
	/**
	 * sendSpielplanMail
	 * 
	 * Wöchentlicher Spielplan als PDF verschicken
	 */
	public function sendSpielplanMail() 
	{
		// temporäre Cookie File erstellen
		$this->_createTempFile();
		
		// Datum Montag / Sonntag festlegen
		$this->_createDays();
		
		// Dateinamen festlegen
		$this->_createFilename();
		
		// Anmeldung durchführen für die SessionID
		$this->_getSession();
		
		// Zuerst die Seite Ergebnismeldung > Vereinsmeldung öffnen, da sonst der Ausdruck als PDF nicht funktioniert.
		$this->_getSeiteErgebnismeldung();
		
		// Spielplan Datei holen
		$this->_getSpielplanPDF();
		
		// temporäre Cookie File löschen
		$this->_deleteTempFile();
		
		// PDF Per eMail versenden
		$this->_sendMail();
	}
	
	
	
	/**
	 * _getSession
	 * 
	 * Durch das Anmelden holen wir uns die SessionID die später benötigt wird.
	 */
	private function _getSession ()
	{
		echo '_getSession\r\n';
		
		$post = ['UN' => $this->_loginUser, 'PW' => $this->_loginPass];
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_USERAGENT, $this->_curlUserAgent);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $this->_curlHeader);
		curl_setopt($ch, CURLOPT_URL, $this->_curlLoginUrl);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
		curl_setopt($ch, CURLOPT_COOKIEJAR, $this->ckfile);
		curl_setopt($ch, CURLOPT_HEADER, true);
		$output = curl_exec($ch);
		curl_close($ch);
	}
	
	/**
	 * _getSeiteErgebnismeldung
	 * 
	 * Zuerst die Seite Ergebnismeldung > Vereinsmeldung öffnen, da sonst der Ausdruck als PDF nicht funktioniert.
	 */
	private function _getSeiteErgebnismeldung()
	{
		echo '_getSeiteErgebnismeldung\r\n';
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_USERAGENT, $this->_curlUserAgent);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $this->_curlHeader);
		curl_setopt($ch, CURLOPT_URL, "https://www.dfbnet.org/egmweb/mod_egm/webflow.do?event=VEREINSMELDER_NEW&dmg_menu=10104");
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $this->ckfile);
		curl_setopt($ch, CURLINFO_HEADER_OUT, true);
		curl_setopt($ch, CURLOPT_HEADER, true);
		return curl_exec($ch);
	}
	
	/**
	 * _getSeiteErgebnismeldung
	 *
	 * Zuerst die Seite Ergebnismeldung > Vereinsmeldung öffnen, da sonst der Ausdruck als PDF nicht funktioniert.
	 */
	private function _getSeiteErgebnismeldungToday()
	{
		// Url
		$sUrl = "https://www.dfbnet.org/egmweb/mod_egm/webflow.do?event=VED_SEARCH&dmg_menu=10104";
		$sUrl .= "&kontext.suche.datumAb=" . date("d.m.Y");
		$sUrl .= "&kontext.suche.datumBis=" . date("d.m.Y");
		//$sUrl .= "&kontext.suche.datumAb=" . date("d.m.Y", strtotime('-3 day'));
		//$sUrl .= "&kontext.suche.datumBis=" . date("d.m.Y", strtotime('-3 day'));		
		$sUrl .= "&kontext.suche.spielStatus=0"; // Alle Spiele auch mit Ergebnisse
		$sUrl .= "&kontext.suche.sportdisziplin=-1";
		$sUrl .= "&kontext.suche.mannschaftsart=-1";
		$sUrl .= "&kontext.suche.heimAuswaerts=0";
		$sUrl .= "&kontext.suche.spielkennung=";
		
		echo '_getSeiteErgebnismeldungToday\r\n';
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_USERAGENT, $this->_curlUserAgent);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $this->_curlHeader);
		curl_setopt($ch, CURLOPT_URL, $sUrl);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $this->ckfile);
		curl_setopt($ch, CURLINFO_HEADER_OUT, true);
		curl_setopt($ch, CURLOPT_HEADER, true);
		return curl_exec($ch);
	}
	
	/**
	 * _getSpielplanPDF
	 * 
	 * Spielplan für nächste Woche herunterladen
	 */
	private function _getSpielplanPDF()
	{
		echo '_getSpielplanPDF\r\n';
		
		// Dateihandler
		$fp = fopen("./tmp/" . $this->_fileName, "w");
		
		// Url
		$sUrl = "https://www.dfbnet.org/egmweb/mod_egm/webflow.do?event=VED_PRINT&dmg_menu=10104";
		$sUrl .= "&kontext.suche.printDatumAb=" . $this->_nextMonday;
		$sUrl .= "&kontext.suche.printDatumBis=" . $this->_nextSunday;
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_USERAGENT, $this->_curlUserAgent);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $this->_curlHeader);
		curl_setopt($ch, CURLOPT_URL, $sUrl);
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FILE, $fp);
		curl_setopt($ch, CURLOPT_COOKIEFILE, $this->ckfile);
		curl_setopt($ch, CURLOPT_TIMEOUT, -1); # optional: -1 = unlimited, 3600 = 1 hour
		curl_exec($ch);
		curl_close($ch);
		
		# Close the file pointer
		fclose($fp);
	}
	
	/**
	 * _sendMail
	 * 
	 * E-Mail mit Anhang verschicken
	 */
	private function _sendMail()
	{
		$sBetreff = "Spielplan KW " . $this->_nextWeek . " " . $this->_nextMonday . " - " . $this->_nextSunday;
		$sMessage = "Guten Morgen<br /><br />";
		$sMessage .= "anbei der Spielplan für die kommende Woche vom " . $this->_nextMonday . " - " . $this->_nextSunday . ".<br /><br />";
		$sMessage .= "Mit sportlichen Grüßen<br />";
		//$sMessage .= "Patrick Jaskulski<br />";
		//$sMessage .= "2. Abteilungsleiter Spielbetrieb Jugend<br />";
		$sMessage .= "TuS Germania Lohauserholz-Daberg e.V.<br />";
		//$sMessage .= "Mobil: 01 76 - 48 27 40 76<br />";
		$sMessage .= "web: http://www.germania-lohauserholz.de<br />";
		$sMessage .= "mail: info@germania-lohauserholz.de<br /><br />";
		$sMessage .= "(Hinweis: Diese eMail wurde automatisch über ein laufendes Programm erstellt.)";
		
		// PHPMailer Classes
		require_once ('libs/PHPMailer-master/PHPMailerAutoload.php');
		
		$mail = new PHPMailer;
		
		$mail->CharSet = "UTF-8";
		$mail->isSMTP();                                      // Set mailer to use SMTP
		$mail->Host = '<host>';  // Specify main and backup SMTP servers
		$mail->SMTPAuth = true;                               // Enable SMTP authentication
		$mail->Username = '<UserName>';                 // SMTP username
		$mail->Password = '<password>';                           // SMTP password
		
		$mail->SMTPSecure = 'ssl';                            // Enable TLS encryption, `ssl` also accepted
		$mail->Port = 465;
		
		$mail->setFrom('patrickjaskulski@germania-lohauserholz.de', 'Spielplan Verteiler - TuS Germania Lohauserholz-Daberg e.V.');
		$mail->addAddress('verteiler-spielplan@germania-lohauserholz.de');     // Add a recipient
		
		$mail->isHTML(true);
		
		$mail->Subject = $sBetreff;
		$mail->Body    = $sMessage;
		
		$mail->AddAttachment('./tmp/' . $this->_fileName, $this->_fileName);
		
		if(!$mail->send()) {
			$from = "From: Patrick Jaskulski <patrickjaskulski@gmail.com>\n";
			$from .= "Reply-To: patrickjaskulski@gmail.com\n";
			$from .= "Content-Type: text/html; charset=UTF-8\n";
			mail("patrickjaskulski@gmail.com", "Mailer Error: ", $mail->ErrorInfo, $from);
		} else {
			//echo 'Message has been sent';
		}
	}
	
	/**
	 * _createDays
	 * 
	 * Datum von Montag / Sonntag festlegen
	 */
	private function _createDays()
	{
		// nächsten Montag
		$nextMondayTimestamp = strtotime('next monday');
		$this->_nextMonday = date("d.m.Y", $nextMondayTimestamp);
		
		// Nächsten Sonntag
		$nextSundayTimestamp = strtotime('Sunday next week');
		$this->_nextSunday = date("d.m.Y", $nextSundayTimestamp);
		
		// Kalenderwoche
		$this->_nextWeek = date('W', $nextMondayTimestamp);
		
		echo 'KW:' . $this->_nextWeek . ' ' . $this->_nextMonday . ' - ' . $this->_nextSunday . '\r\n';
	}
	
	/**
	 * _createFilename
	 * 
	 * PDF Dateinamne festlegen
	 */
	private function _createFilename()
	{
		// Dateinamen
		$this->_fileName = "Spielplan_KW" . $this->_nextWeek . "_" . $this->_nextMonday . "-" . $this->_nextSunday . ".pdf";
		echo 'Datei.' . $this->_fileName . '\r\n';
	}
	
	/**
	 * _createTempFile
	 * 
	 * Temporäre Cookie file für die Session
	 */
	private function _createTempFile()
	{
		$this->ckfile = tempnam ("./tmp", "cookie");
		echo 'Temporäre Datei erstellt.\r\n';
	}
	
	private function _deleteTempFile()
	{
		unlink($this->ckfile);
		echo 'Temporäre Datei gelöscht.\r\n';
	}
}