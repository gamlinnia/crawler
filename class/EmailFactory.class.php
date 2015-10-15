<?php

class EmailFactory {

    private $smtpInfo;
    private static $emailFactory;

    private function __construct ($smtpInfo) {
        $this->smtpInfo = $smtpInfo;
    }

    static function getEmailFactory ($smtpInfo) {
        //smtpInfo is an array, for example:array("host" => "10.16.11.68","port" => "25","auth" => false);
        if (!isset($EmailFactory)) {
            self::$emailFactory = new EmailFactory($smtpInfo);
        }
        return self::$emailFactory;
    }

    function getEmail ($action, $recipient_array) {
        $email = new Email($action, $recipient_array);
        $email->setSmtpInfo($this->smtpInfo);
        return $email;
    }

}




?>