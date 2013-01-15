<?php


/**
 * Description of tutoriel
 *
 * Date - $Date: 2013-01-15 13:00:00 +0200 (lun., 15 janvier 2013) $
 */

 /*
 * @copyright  GPL License 2012 - Mehboub Sophien - Badreddine Zeghiche - Rudy Delépine sociaNova (http://www.socianova.com)
 * @license    http://www.gnu.org/licenses/gpl-3.0.fr.html  GPL License
 * @version 1.0
 */

include_once 'phpFetchMail.php';

$boiteMail = '{pop.gmail.com:995/pop3/ssl}INBOX';
// imap : '{imap.gmail.com:995/imap/ssl}INBOX';
$login = 'test@gmail.com';
$motDePasse = 'password';

$test = new phpFetchMail($boiteMail, $login, $motDePasse);

//si le dossier est spécifié, les pièces jointes seront enregistré en DB et
// téléchargé dans un dossier du nom de l'id du message auquel il est attaché en DB
//sinon il ne charge pas les pièces jointes en DB, ni sur disc dur
$test->take("C:\Users\name\test");

?>
