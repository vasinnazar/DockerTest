<?php

namespace App\Utils;

use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class EmailReader {

    // imap server connection
    public $conn;
    // inbox storage and inbox message count
    private $inbox;
    public $msg_cnt = 0;
    // email login credentials
    private $server = 'imap.yandex.ru';
    private $user = 'sb.proverka106@yandex.ru';
    private $pass = '9060086sb';
    private $port = 993; // adjust according to server settings
    private $param = '/imap/ssl';
    private $folder = 'INBOX';

    // connect to the server and get the inbox emails

    function __construct() {
        $this->connect();
        $this->msg_cnt = imap_num_msg($this->conn);
//        $this->inbox();
    }

    // close the server connection
    function close() {
        $this->inbox = array();
        $this->msg_cnt = 0;

        imap_close($this->conn);
    }

    // open the server connection
    // the imap_open function parameters will need to be changed for the particular server
    // these are laid out to connect to a Dreamhost IMAP server
    function connect() {
//        $this->conn = imap_open('{'.$this->server . ':143/imap/notls}', $this->user, $this->pass);
        $this->conn = imap_open("{" . "{$this->server}:{$this->port}{$this->param}" . "}$this->folder", $this->user, $this->pass);
    }

    // move the message to a new folder
    function move($msg_index, $folder = 'INBOX.Processed') {
        // move on server
//        imap_mail_move($this->conn, $msg_index, $folder);
//        imap_expunge($this->conn);
        // re-read the inbox
//        $this->inbox();
    }

    // get a specific message (1 = first email, 2 = second email, etc.)
    function get($msg_index = NULL, $upload = false, $loadFromServer = false) {
        if ($loadFromServer) {
            $this->inbox[$msg_index] = [
                'index' => $msg_index,
                'header' => imap_headerinfo($this->conn, $msg_index)
            ];
            return $this->inbox[$msg_index];
        } else if (!$loadFromServer && count($this->inbox) == 0) {
            return [];
        } else {
            if ($upload) {
                $this->inbox[$msg_index]['body'] = imap_fetchstructure($this->conn, $msg_index + 1);
                $this->inbox[$msg_index]['structure'] = imap_fetchstructure($this->conn, $msg_index + 1);
            }
            if (!is_null($msg_index) && isset($this->inbox[$msg_index])) {
                return $this->inbox[$msg_index];
            } else {
                return $this->inbox[0];
            }
        }
    }

    function get2($msg_index = NULL, $upload = false, $loadFromServer = false) {
        return [
            'index' => $msg_index,
            'header' => imap_headerinfo($this->conn, $msg_index),
            'body' => imap_fetchstructure($this->conn, $msg_index),
            'structure' => imap_fetchstructure($this->conn, $msg_index)
        ];
    }

    public function getIndexListByDate($date) {
        $res = [];
        for ($i = 0; $i < $this->msg_cnt; $i++) {
            $email = $this->inbox[$i];
            $mdate = new \Carbon\Carbon($email['header']->MailDate);
            if ($mdate->format('dmY') == $date) {
                $res[] = $i;
            }
        }
        return $res;
    }

    public function getMails($date, $surname = null) {
        if (!is_null($surname)) {
            return imap_search($this->conn, 'SUBJECT ' . $surname . ' ON "' . with(new Carbon($date))->format('Y-m-d') . '"', SE_FREE, 'UTF-8');
//            return imap_search($this->conn, 'SUBJECT "' . $surname . '"');
        } else {
            return imap_search($this->conn, 'ON "' . with(new Carbon($date))->format('Y-m-d') . '"');
        }
    }

    // read the inbox
    function inbox() {
        $this->msg_cnt = imap_num_msg($this->conn);

        $in = array();
        for ($i = 1; $i <= $this->msg_cnt; $i++) {
            $in[] = array(
                'index' => $i,
                'header' => imap_headerinfo($this->conn, $i)
            );
        }

        $this->inbox = $in;
    }

    function email_pull() {
        // this method is run on a cronjob and should process all emails in the inbox
        while (1) {
            // get an email
            $email = $this->get(NULL, true);

            // if there are no emails, jump out
            if (count($email) <= 0) {
                break;
            }

            $this->email_load_files($email);

            // don't slam the server
            sleep(1);
        }

        // close the connection to the IMAP server
        $this->close();
    }

    function email_load_files($email, $folder_start = null) {
        $attachments = array();
        // check for attachments
        if (isset($email['structure']->parts) && count($email['structure']->parts)) {
//            echo '<br>';
//            var_dump($email['structure']->parts);
//            echo '<br>';
            // loop through all attachments
            for ($i = 0; $i < count($email['structure']->parts); $i++) {
                // set up an empty attachment
                $attachments[$i] = array(
                    'is_attachment' => FALSE,
                    'filename' => '',
                    'name' => '',
                    'attachment' => ''
                );

                // if this attachment has idfparameters, then proceed
                if ($email['structure']->parts[$i]->ifdparameters) {
                    foreach ($email['structure']->parts[$i]->dparameters as $object) {
                        // if this attachment is a file, mark the attachment and filename
                        if (strtolower($object->attribute) == 'filename') {
                            $attachments[$i]['is_attachment'] = TRUE;
                            $attachments[$i]['filename'] = $object->value;
                        }
                    }
                }

                // if this attachment has ifparameters, then proceed as above
                if ($email['structure']->parts[$i]->ifparameters) {
                    foreach ($email['structure']->parts[$i]->parameters as $object) {
                        if (strtolower($object->attribute) == 'name') {
                            $attachments[$i]['is_attachment'] = TRUE;
                            $attachments[$i]['name'] = $object->value;
                        }
                    }
                }

                // if we found a valid attachment for this 'part' of the email, process the attachment
//                echo '<br>';
//                var_dump($attachments[$i]);
//                echo '<br>';
                if ($attachments[$i]['is_attachment']) {
                    // get the content of the attachment
                    $attachments[$i]['attachment'] = imap_fetchbody($this->conn, $email['index'], $i + 1);

                    // check if this is base64 encoding
                    if ($email['structure']->parts[$i]->encoding == 3) { // 3 = BASE64
                        $attachments[$i]['attachment'] = base64_decode($attachments[$i]['attachment']);
                    }
                    // otherwise, check if this is "quoted-printable" format
                    elseif ($email['structure']->parts[$i]->encoding == 4) { // 4 = QUOTED-PRINTABLE
                        $attachments[$i]['attachment'] = quoted_printable_decode($attachments[$i]['attachment']);
                    }
                }
            }
        }

        $found_img = FALSE;
        foreach ($attachments as $a) {
            if ($a['is_attachment'] == 1) {
                // get information on the file
                $finfo = pathinfo($a['filename']);
                $date = new Carbon($email['header']->MailDate);
//                echo( $email['header']->MailDate . '<br>');
                $folder = $date->format('Y-m-d');

                if (array_key_exists('extension', $finfo)) {
//                    echo $finfo['filename'] . '.' . $finfo['extension'] . '<br>';
                    $this->process_file($a['attachment'], $finfo['filename'], $finfo['extension'], $folder_start . $folder);
                } else {
//                    var_dump(imap_mime_header_decode($finfo['filename']));
//                    var_dump($finfo);
//                    echo '<br>';
//                    echo imap_mime_header_decode($finfo['filename']) . '<br>';
//                    $filename = imap_mime_header_decode($finfo['filename'])[0]->text;
                    $filename = uniqid() . '.' . 'jpg';
                    $this->process_file($a['attachment'], substr($filename, 0, strrpos($filename, '.')), substr($filename, strrpos($filename, '.') + 1), $folder_start . $folder);
//                    echo '<br>';
                }
            }
        }
//        $addr = $email['header']->from[0]->mailbox . "@" . $email['header']->from[0]->host;
//        $sender = $email['header']->from[0]->mailbox;
//        $text = (!empty($email['header']->subject) ? $email['header']->subject : '');

        return $attachments;
    }

    function process_file($file, $filename, $ext, $folder_name = null) {
        $dir = (is_null($folder_name)) ? '' : ($folder_name . '/');
//        if (!file_exists('./mailfiles/' . $dir)) {
//            mkdir('./mailfiles/' . $dir, 0777, true);
//        }
//        $filename = iconv("UTF-8", "WINDOWS-1251", $filename);
        Storage::put('images/'.$dir . $filename . '.' . $ext, $file);
//        $fp = fopen('./mailfiles/' . $dir . $filename . '.' . $ext, 'w');
//        fputs($fp, $file);
//        fclose($fp);
        return $filename . '.' . $ext;
    }

}
