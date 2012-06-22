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
        $this->boundary1 = rand(0, 9) . "-"
                . rand(10000000000, 9999999999) . "-"
                . rand(10000000000, 9999999999) . "=:"
                . rand(10000, 99999);
        $this->boundary2 = rand(0, 9) . "-" . rand(10000000000, 9999999999) . "-"
                . rand(10000000000, 9999999999) . "=:"
                . rand(10000, 99999);
    }

    public function send($to = null, $subject = null, $message = null, $from = null) {
        $args = $this->getArgs();
        //Here would be great if validator run through email(s) and throw an Exception if wrong
        if ($to === null && isset($args[0]))
            $to = $args[0];
        if (is_array($to))
            $to = implode(", ", $to);
        if (!isset($to)) {
            throw new X3_Exception('Адрес получателя не задан');
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

        $headers = $this->generateHeaders($from, $reply);

        if (X3::app() != null)
            $encoding = X3::app()->encoding;
        else
            $encoding = mb_detect_encoding($args[1]);

        if ($encoding != $this->encoding) {
            $subject = iconv($encoding, $this->encoding, $subject);
            $message = iconv($encoding, $this->encoding, $message);
        }
        $body = $this->generateBody($message);
        return mail($to, $subject, $body, $headers);
    }

    public function setArgs($args) {
        $this->args = $args;
        return self::$instance;
    }

    public function getArgs() {
        return $this->args;
    }

    protected function generateHeaders($from, $reply) {
        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "From: $from\r\n";
        $headers .= "Sender: $from\r\n";
        $headers .= "Reply-To: $reply\r\n";
        foreach ($this->copy as $cc) {
            $headers .= "Cc: $cc\r\n";
        }
        if (empty($this->files))
            $headers .= "Content-type:multipart/alternative; charset=\"$this->encoding\";\r\n";
        else
            $headers .= "Content-type:multipart/mixed; charset=\"$this->encoding\";\r\n";
        $headers .= "boundary=\"$this->boundary1\"";
        return $headers;
    }

    protected function generateBody($message) {
        $textmessage = strip_tags(nl2br($message), "<br>");
        $body = $message;
        if (empty($this->files)) {
            $body = "MIME-Version: 1.0\r\n
                Content-Type: multipart/alternative;\r\n
                --$this->boundary1\r\n
                    Content-Type: text/plain;\r\n
                    charset=\"$this->encoding\"\r\n
                        Content-Transfer-Encoding: quoted-printable\r\n\r\n
                    $textmessage\r\n
                    --$this->boundary1\r\n
                    Content-Type: text/html;\r\n
                        charset=\"$this->encoding\"\r\n
                            Content-Transfer-Encoding: quoted-printable\r\n\r\n
                    $message\r\n\r\n
                        --$this->boundary1--\r\n";
        } else {
            $attachments = '';
            //TODO: check file existance and availability (or check on add...)
            foreach ($this->files as $file) {
                $type = '';
                if (PHP_VERSION_ID >= 50300) {
                    $h = finfo_open(FILEINFO_MIME_TYPE);
                    $type = finfo_file($h, $file);
                    fclose($h);
                }else
                    $type = mime_content_type($file);

                $handle = fopen($file, 'rb');
                $f_contents = fread($handle, filesize($file));
                $A = chunk_split(base64_encode($f_contents));
                fclose($handle);
                $name = basename($file);
                $attachments.="--$this->boundary1\r\n
                    Content-Type: $type;\r\n
                        name=\"$name\"\r\n
                            Content-Transfer-Encoding: base64\r\n
                            Content-Disposition: attachment;\r\n
                            filename=\"$name\"\r\n\r\n
                        $A\r\n\r\n";
                unset($A);
            }
            $body = "This is a multi-part message in MIME format.
                \r\n\r\n
                --$this->boundary1\r\n
                Content-Type: multipart/alternative;\r\n
                boundary=\"$this->boundary2\"\r\n\r\n
                    --$this->boundary2\r\n
                        Content-Type: text/plain;\r\n
                        charset=\"$this->encoding\"\r\n
                            Content-Transfer-Encoding: quoted-printable\r\n\r\n
                            $textmessage\r\n
                                --$this->boundary2\r\n
                                    Content-Type: text/html;\r\n
                                    charset=\"$this->encoding\"\r\n
                                        Content-Transfer-Encoding: quoted-printable\r\n\r\n
                    $message\r\n\r\n
                        --$this->boundary2--\r\n\r\n
                    $attachments\r\n
                        --$this->boundary1--\r\n";
        }
        return $body;
    }

}

