<?php

/**
 * Description of phpFetchMail
 *
 * Date - $Date: 2013-01-15 13:00:00 +0200 (lun., 15 janvier 2013) $
 */

 /*
 * @copyright  GPL License 2012 - Mehboub Sophien - Badreddine Zeghiche - Rudy Delépine sociaNova (http://www.socianova.com)
 * @license    http://www.gnu.org/licenses/gpl-3.0.fr.html  GPL License
 * @version 1.0
 */
include_once 'singleMysql.php';

class phpFetchMail {

    private $inbox;
    private $emails;

    public function __construct($boiteMail, $login, $motDePasse) {

        $this->inbox = imap_open($boiteMail, $login, $motDePasse)
                or die('Cannot connect to mail: ' . imap_last_error());
    }

    public function take($savedirpath = '') {

        if ($savedirpath != '') {

            $savedirpath = str_replace('\\', '/', $savedirpath);
            if (substr($savedirpath, strlen($savedirpath) - 1) != '/') {
                $savedirpath .= '/';
            }
        }

        $message = array();
        $message["attachment"]["type"][0] = "text";
        $message["attachment"]["type"][1] = "multipart";
        $message["attachment"]["type"][2] = "message";
        $message["attachment"]["type"][3] = "application";
        $message["attachment"]["type"][4] = "audio";
        $message["attachment"]["type"][5] = "image";
        $message["attachment"]["type"][6] = "video";
        $message["attachment"]["type"][7] = "other";

        $mails = imap_search($this->inbox, 'UNSEEN');

        $con = mysql::getInstance();
        if (!$con) {
            die('Could not connect: ' . mysql_error());
        }

        if ($mails) {

            rsort($mails);

            foreach ($mails as $email_number) {
                $overview = imap_fetch_overview($this->inbox, $email_number, 0);

                $headerText = imap_fetchHeader($this->inbox, $overview[0]->uid, FT_UID);
                $header = imap_rfc822_parse_headers($headerText);
                $from = $header->from;
                $structure = imap_fetchstructure($this->inbox, $email_number, FT_UID);

                $to = '';
                foreach ($header->to as $value) {
                    $to .= $value->mailbox . "@" . $value->host . ';';
                }

                $toaddress = $header->toaddress;
                $date = $overview[0]->date;
                $subject = $overview[0]->subject;
                

                $from = '';
                if (isset($header->from)) {
                    foreach ($header->from as $value) {
                        $from .= $value->mailbox . "@" . $value->host . ';';
                    }
                }
                $fromaddress = isset($header->fromaddress) ? $header->fromaddress : '';

                $reply_to = '';
                if (isset($header->reply_to)) {
                    foreach ($header->reply_to as $value) {
                        $reply_to .= $value->mailbox . "@" . $value->host . ';';
                    }
                }
                $reply_toaddress = isset($header->reply_toaddress) ? $header->reply_toaddress : '';

                $sender = '';
                if (isset($header->sender)) {
                    foreach ($header->sender as $value) {
                        $sender .= $value->mailbox . "@" . $value->host . ';';
                    }
                }
                $senderaddress = isset($header->senderaddress) ? $header->senderaddress : '';

                $cc = '';
                if (isset($header->cc)) {
                    foreach ($header->cc as $value) {
                        $cc .= $value->mailbox . "@" . $value->host . ';';
                    }
                }
                $ccaddress = isset($header->ccaddress) ? $header->ccaddress : '';

                $con;
                $body = imap_fetchbody($this->inbox, $email_number, 1);


                if ($structure->subtype == "MIXED") {
                    $body = imap_fetchbody($this->inbox, $email_number, 1.1);
                    $coding = $structure->parts[0]->encoding;
                }

                if ($structure->parts[0]->encoding == 4) {
                    $body = quoted_printable_decode($body);
                } elseif ($structure->parts[0]->encoding == 3) {
                    $body = base64_decode($body);
                } elseif ($structure->parts[0]->parts[0]->encoding == 3) {
                    $body = base64_decode($body);
                } elseif ($structure->parts[0]->parts[0]->encoding == 4) {
                    $body = quoted_printable_decode($body);
                }

                mysql_select_db("mail", $con);

                mysql_query("SET NAMES 'utf8'");

                $sql = "INSERT INTO email (toaddress, email, date, subject, body, `from`, fromaddress, reply_to, reply_toaddress, sender, senderaddress, cc, ccaddress) VALUES ('" . mysql_real_escape_string($to) . "', '" . mysql_real_escape_string($toaddress) . "',  '" . mysql_real_escape_string($date) . "', '" . mysql_real_escape_string($subject) . "', '" . mysql_real_escape_string($body) . "', '" . mysql_real_escape_string($from) . "', '" . mysql_real_escape_string($fromaddress) . "', '" . mysql_real_escape_string($reply_to) . "', '" . mysql_real_escape_string($reply_toaddress) . "', '" . mysql_real_escape_string($sender) . "', '" . mysql_real_escape_string($senderaddress) . "', '" . mysql_real_escape_string($cc) . "', '" . mysql_real_escape_string($ccaddress) . "')";

                mysql_query('BEGIN');

                $flag = mysql_query($sql, $con);

                if (!$flag) {
                    die('Error: ' . mysql_error());
                } else {

                    $number = mysql_insert_id();

                    if (($savedirpath != '') && (isset($structure->parts))) {

                        $parts = $structure->parts;

                        $fpos = 2;
                        for ($i = 0; $i < count($parts); $i++) {
                            $message["pid"][$i] = ($i);
                            $part = $parts[$i];

                            if ($part->ifdisposition == 1 and $part->disposition == "ATTACHMENT") {
                                $message["type"][$i] = $message["attachment"]["type"][$part->type] . "/" . strtolower($part->subtype);
                                $message["subtype"][$i] = strtolower($part->subtype);
                                $ext = $part->subtype;
                                $params = $part->dparameters;
                                $filename = $part->dparameters[0]->value;

                                $mege = "";
                                $data = "";

                                $mege = imap_base64(imap_fetchbody($this->inbox, $email_number, $fpos));

                                $sql2 = "INSERT INTO attachments (number,name,extension,path) VALUES ('" . $number . "','" . mysql_real_escape_string($filename) . "','" . mysql_real_escape_string(substr($filename, strrpos($filename, '.') + 1)) . "', '" . mysql_real_escape_string($savedirpath . $number . '/' . $filename) . "')";

                                $flag2 = mysql_query($sql2, $con);

                                if (!$flag2) {
                                    mysql_query('ROLLBACK');
                                    die('Error: ' . mysql_error());
                                } else {
                                    mysql_query('COMMIT');

                                    $filename = "$filename";
                                    if (!file_exists($savedirpath . $number)) {
                                        mkdir($savedirpath . $number, 0644);
                                    }
                                    $fp = fopen($savedirpath . $number . '/' . $filename, 'w');
                                    fputs($fp, $mege);
                                    fclose($fp);
                                    $fpos+=1;
                                }
                            } else {
                                mysql_query('COMMIT');
                            }
                        }
                    }
                }
            }
        }
        mysql_close($con);
        imap_close($this->inbox);
    }

}


?>
