<?php

/**
 * Отправляет письмо по заданным параметрам,
 * формирует заголовки и преобразовывает в нужную кодировку
 * 
 * @params:
 * 0 - адрес получателя (обязательный)
 * 1 - тема письма
 * 2 - тело письма
 * 3 - адрес отправителя (по дефолту noreply@instinct.kz)
 * 4 - заголовки
 *
 * @sample:
 * 
 * $params = array(
 *  'to@mail.com',
 *  'It`s theme of letter',
 *  'Hi!<br>Bro, how are you?'
 * );
 * try {
 *      Mailer::getInstance()->setArgs($params)->send();
 * } catch () {
 *      //что-то делаем в случае если письмо не ушло
 * }
 * 
 * @author Eugeny Mineyev
 * 
 * */
class X3_Mailer extends X3_Component {

    protected $args = array();
    protected static $instance = null;
    protected $boundary1 = 0;
    protected $boundary2 = 0;
    protected $copy = array();
    protected $files = array();
    public $email = 'admin@instinct.kz';
    public $encoding = 'KOI8-R';

    public static function getInstance() {
        if (!self::$instance) {
            self::$instance = new X3_Mailer();
        }
        return self::$instance;
    }

    public function __construct($params = array()) {
        $this->boundary1 = md5(time()).rand(0,9).rand(0,9) . "=_?:";
        $this->boundary2 = md5(time()).rand(0,9).rand(0,9) . "=_?:";
    }

    public function send($to = null, $subject = null, $message = null, $from = null) {
        $args = $this->getArgs();
        //Here would be great if validator run through email(s) and throw an Exception if wrong
        if ($to === null && isset($args[0]))
            $to = $args[0];
        if (is_array($to))
            $to = implode(", ", $to);
        if (!isset($to)) {
            throw new X3_Exception('Адрес получателя не задан',500);
        }

        if (is_null($subject) && isset($args[1]))
            $subject = $args[1];

        if (is_null($message) && isset($args[2]))
            $args[2] = $message;
        //oh validate me...
        $from = isset($args[3]) ? $args[3] : $this->email;

        if (is_array($from)) {
            $reply = current($from);
            $from = key($from) . "<" . $reply . ">";
        } elseif (is_string($from)) {
            $reply = $from;
        }
        
        if (X3::app() != null)
            $encoding = X3::app()->encoding;
        else
            $encoding = mb_detect_encoding($args[1]);

        $headers = $this->generateHeaders($from, $reply);
        $body = $this->generateBody($message);
        if ($encoding != $this->encoding) {
            $subject = iconv($encoding, $this->encoding, $subject);
            $body = iconv($encoding, $this->encoding, $body);
        }
        return mail($to, $subject, $body, $headers);
    }

    public function setArgs($args) {
        $this->args = $args;
        return self::$instance;
    }

    public function getArgs() {
        return $this->args;
    }
    
    /**
     * 
     * @param string $src path to a local file
     * @param string $disposition could be 'inline' or 'attachment'
     * @return null|string Content-ID for <img src="cid:..."
     */
    public function addFile($src,$disposition = "inline") {
        $src = realpath($src);
        if($src!==false && is_file($src)){
            $cid = basename($src)."@kansha.kz";
            $this->files[$cid] = array($disposition, $src);
            return $cid;
        }
        return null;
    }

    protected function generateHeaders($from, $reply) {
        $copies = '';
        foreach ($this->copy as $cc) {
            $copies .= "Cc: $cc\r\n";
        }
        $header = '';
        if (empty($this->files))
            $header = "Content-Type: multipart/alternative; boundary=\"$this->boundary1\"";
        else
            $header = "Content-Type: multipart/related; boundary=\"$this->boundary1\"\r\nContent-Transfer-Encoding: quoted-printable\r\nContent-Disposition: inline";
        $headers = <<<HEAD
From: $from 
Sender: $from
Reply-To: $reply
$header
MIME-Version: 1.0

This is a message in Mime Format.  If you see this, your mail reader does not support this format.

HEAD;
        return $headers;
    }

    protected function generateBody($message) {
        $textmessage = strip_tags(nl2br($message), "<br>");
        if (empty($this->files)) {
$body =<<<BODY
MIME-Version: 1.0
Content-Type: multipart/alternative;
    boundary="$this->boundary1"

This is a multi-part message in MIME format.

--$this->boundary1
Content-Type: text/plain;
    charset="$this->encoding"
Content-Transfer-Encoding: 8bit

$textmessage
--$this->boundary1
Content-Type: text/html;
    charset="$this->encoding"
Content-Transfer-Encoding: 8bit

$message
    
--$this->boundary1--
BODY;
        } else {
            $sep = "B-Global-".md5(time()) . "=_?:";
            $attachments = '';
            //TODO: check file existance and availability (or check on add...)
            foreach ($this->files as $cid=>$file) {
                $type = '';
                $disposition = $file[0];
                $file = $file[1];
                if (PHP_VERSION_ID >= 50300) {
                    $h = finfo_open(FILEINFO_MIME_TYPE);
                    $type = finfo_file($h, $file);
                    //fclose($h);
                }else
                    $type = mime_content_type($file);

                //$handle = fopen($file, 'rb');
                $f_contents = file_get_contents($file); //fread($handle, filesize($file));
                $A = chunk_split(base64_encode($f_contents));
                //fclose($handle);
                $name = basename($file);
                $attachments.=<<<ATTA

--$this->boundary1
Content-Type: $type; name="$name"
Content-Transfer-Encoding: base64
Content-ID: <$cid>
Content-Disposition: $disposition; filename="$name"
$A
ATTA;
                unset($A);
            }
            $body = <<<EOBODY
--$this->boundary1
Content-Type: text/html; charset="$this->encoding"
Content-Transfer-Encoding: quoted-printable
Content-Disposition: inline

$message    
$attachments

--$this->boundary1--
EOBODY;
            //$body  = "MIME-Version: 1.0";
            //$body .= "Content-Type: multipart/mixed; boundary=\"$this->boundary1\"\r\n";
            //$body .= "\r\n";
            //$body .= "This is a multi-part message in MIME format.\r\n";
            //$body .= "\r\n";
            //$body .= "--$this->boundary1\r\n";
            //$body .= "Content-Type: text/html; charset=\"$this->encoding\"\r\n";
            //$body .= "Content-Transfer-Encoding: 7-bit\r\n\r\n";
            //$body .= "$message\r\n";
            //$body .= "$attachments\r\n";
            //$body .= "--$this->boundary1--\r\n";
        }
        return $body;
    }

}

