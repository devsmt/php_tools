<?php

define("TAB", "\t", false);


//-----------------------------------------------------------------------------------
//
//  wrappers
//
//-----------------------------------------------------------------------------------
// abstract driver
class MailDriver {

    // multiple addresses recipient
    // param $a array or string
    function send($to, $subject, $msg, $opt = []) {
        $result = true;
        $a_to = is_array($to) ? $to : [$to];
        for ($i = 0; $i < count($a_to); $i++) {
            $to = $a_to[$i];
            // se anche solo 1 invio non funziona, tutta la funzione ritorna false
            if ($this->SendOne($to, $subject, $msg, $opt)) {
                // TODO: if ( config )
                // log('spedizione effettuata all'indirizzo '.$to);
            } else {
                $result = false;
                // TODO: if ( config )
                // log('spedizione fallita all'indirizzo '.$to);
            }
        }
        return $result;
    }

    // one recipient
    function sendOne($to, $subject, $msg, $opt = []) {
        die('MailDriver::sendOne, your driver is unimplemented');
    }

}

class MailDriverPHP extends MailDriver {

    function __construct($conf = []) {

    }

    function sendOne($to, $subject, $msg, $opt = []) {
        $h = '';
        if (isset($opt['from'])) {
            $h .= "From: " . $opt['from'] . "\r\n";
        } else {
            $server = str_replace('www.', '', @$GLOBALS['_SERVER']['SERVER_NAME']);
            $h .= "From: info@" . $server . "\r\n";
        }
        if (isset($opt['replyto'])) {
            $h .= "Reply-To: " . $opt['replyto'] . "\r\n";
        }

        self::log($subject, $msg);
        return mail($to, $subject, $msg, $h);
    }


    // implementazione minima con invio html
    function sendHTML($to, $subject, $body, array $opt = []) {
        $option = array_merge([
            'charset' => 'iso-8859-1',
        ], $opt);
        extract($option);

        $headers = "From: $from\r\n";
        $headers .= "Reply-To: $email_reply_to\r\n";
        $headers .= "Return-Path: $email_return_path\r\n";
        $headers .= "X-Mailer: Linux\n";
        $headers .= 'MIME-Version: 1.0' . "\n";
        $headers .= "Content-type: text/html; charset=$charset" . "\r\n";
        return mail($to, $subject, $body, $headers);
    }

    function sendAttachment($from, $to, $subject, $message, $file_path, $replyto) {
        $filename = basename($file_path);
        $file_size = filesize($file_path);
        $handle = fopen($file_path, "r");
        $content = fread($handle, $file_size);
        fclose($handle);
        $content = chunk_split(base64_encode($content));
        $uid = md5(uniqid(time()));
        $name = basename($file_path);
        $header = "From: $from\r\n";
        $header .= "Reply-To: " . $replyto . "\r\n";
        $header .= "MIME-Version: 1.0\r\n";
        $header .= "Content-Type: multipart/mixed; boundary=\"" . $uid . "\"\r\n\r\n";
        $header .= "This is a multi-part message in MIME format.\r\n";
        $header .= "--" . $uid . "\r\n";
        $header .= "Content-type:text/plain; charset=iso-8859-1\r\n";
        $header .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
        $header .= $message . "\r\n\r\n";
        $header .= "--" . $uid . "\r\n";
        $header .= "Content-Type: application/octet-stream; name=\"" . $filename . "\"\r\n"; // use different content types here
        $header .= "Content-Transfer-Encoding: base64\r\n";
        $header .= "Content-Disposition: attachment; filename=\"" . $filename . "\"\r\n\r\n";
        $header .= $content . "\r\n\r\n";
        $header .= "--" . $uid . "--";
        return mail($to, $subject, $message, $header);
    }

    function sendHTMLAttach($from, $to, $subject, $textMessage, $htmlMessage) {
        $boundary = uniqid($GLOBALS['_SERVER']['SERVER_NAME']);
        $headers = "From: $from\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: multipart/alternative; boundary = $boundary\r\n\r\n";
        $headers .= "This is a MIME encoded message.\r\n\r\n";
        $headers .= "--$boundary\r\n";
        $headers .= "Content-Type: text/plain; charset=ISO-8859-1\r\n";
        $headers .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $headers .= chunk_split(base64_encode($textMessage));
        $headers .= "--$boundary\r\n";
        $headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
        $headers .= "Content-Transfer-Encoding: base64\r\n\r\n";
        $headers .= chunk_split(base64_encode($htmlMessage));
        $headers .= "--$boundary\r\n";
        return mail($to, $subject, "", $headers);
    }


      /*
       mail_attachment("$from", "youremailaddress@gmail.com",
         "subject", "message", ("temp/".$_FILES["filea"]["name"]));
      */
      function mail_attachment ($from , $to, $subject, $message, $attachment){
         $fileatt = $attachment; // Path to the file
         $fileatt_type = "application/octet-stream"; // File Type

         $start = strrpos($attachment, '/') == -1 ?
            strrpos($attachment, '//') : strrpos($attachment, '/')+1;
         // Filename that will be used for the file as the attachment
         $fileatt_name = substr($attachment, $start, strlen($attachment));

         $email_from = $from; // Who the email is from
         $subject = "New Attachment Message";

         $email_subject =  $subject; // The Subject of the email
         $email_txt = $message; // Message that the email has in it
         $email_to = $to; // Who the email is to

         $headers = "From: ".$email_from;
         $file = fopen($fileatt,'rb');
         $data = fread($file,filesize($fileatt));
         fclose($file);

         $msg_txt="\n\n You have recieved a new attachment message from $from";
         $semi_rand = md5(time());
         $mime_boundary = "==Multipart_Boundary_x{$semi_rand}x";
         $headers .= "\nMIME-Version: 1.0\n" . "Content-Type: multipart/mixed;\n" . "
            boundary=\"{$mime_boundary}\"";

         $email_txt .= $msg_txt;

         $email_message .= "This is a multi-part message in MIME format.\n\n" .
            "--{$mime_boundary}\n" . "Content-Type:text/html;
            charset = \"iso-8859-1\"\n" . "Content-Transfer-Encoding: 7bit\n\n" .
            $email_txt . "\n\n";

         $data = chunk_split(base64_encode($data));

         $email_message .= "--{$mime_boundary}\n" . "Content-Type: {$fileatt_type};\n" .
            " name = \"{$fileatt_name}\"\n" .
            "Content-Disposition: attachment;\n" .
            " filename = \"{$fileatt_name}\"\n" . "Content-Transfer-Encoding:
            base64\n\n" . $data . "\n\n" . "--{$mime_boundary}--\n";

         $ok = mail($email_to, $email_subject, $email_message, $headers);

         if($ok) {
            echo "File Sent Successfully.";
            unlink($attachment); // delete a file after attachment sent.
         }else {
            die("Sorry but the email could not be sent. Please go back and try again!");
         }
      }



}

// There are some SMTP servers that work without authentication,
// but if the server requires authentication, there is no way to circumvent that.
// must use a php library
function mail_smtp($to, $subject, $message){
    ini_set("SMTP", $server);
    ini_set("sendmail_from", $mail_from);
    $headers = "From: $mail_from";
    return mail($to, $subject, $message, $headers);
}



// @see phpmailer
class MailDriverSMTP extends MailDriver {

    var $m = null;

    function __construct($conf = []) {
        $conf = array_merge(['is_gmail' => true], $conf);
        $this->m = new phpmailer();
        if ($conf['is_gmail']) {
            $this->m->IsSMTP();
            $this->m->SMTPAuth = true;
            $this->m->SMTPSecure = 'ssl';
            $this->m->Host = 'smtp.gmail.com';
            $this->m->Port = 465;
            $this->m->Username = gs_T_MAIL_SMTP_AUTH_USER;
            $this->m->Password = gs_T_MAIL_SMTP_AUTH_PASSWORD;
        } else {
            $this->m->Mailer = gs_T_MAIL_PROTOCOL;
            $this->m->SMTPDebug = gb_T_MAIL_DEBUG;
            $this->m->Port = gi_T_MAIL_PORT;
            $this->m->Host = gs_T_MAIL_SMTP_SERVER;
            $this->m->SMTPAuth = gb_T_MAIL_SMTP_AUTH;
            $this->m->Username = gs_T_MAIL_SMTP_AUTH_USER;
            $this->m->Password = gs_T_MAIL_SMTP_AUTH_PASSWORD;
        }
    }

    function sendOne($to, $subject, $msg, $opt = []) {
        if (is_string($to)) {
            $this->m->AddAddress($to);
        } elseif (is_array($to)) {
            foreach ($to as $i => $add) {
                $this->m->AddAddress($add);
            }
        }
        if (isset($opt['from']) && $opt['from'] != '') {
            $this->m->From = $opt['from'];
            $this->m->FromName = $opt['from'];
        }
        $this->m->Subject = $subject;
        $this->m->Body = $msg;
        // $m->AddReplyTo("noreply@test.de","Information");
        // $m->IsHTML(true);
        return $this->m->Send();
    }

}

class Mail {

    function send($to, $subject, $msg, $opt = []) {
        $m = new MailDriverPHP();
        $result = $m->sendOne($to, $subject, $msg, $opt);
        return $result;
    }

    // open one connection only
    function sendMass($data, $opt = []) {

    }

    // notify site admins
    function admins($subject, $msg, $opt = []) {

    }

    // notify site managers
    function managers($subject, $msg, $opt = []) {

    }

}
