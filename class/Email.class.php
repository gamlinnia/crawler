<?php

class Email{

    private $subject;
    private $content;

    private $action;
    private $recipient_array;

    private $smtpInfo;

    function __construct ($action, $recipient_array) {
        //decide email type
        $this->setAction($action);
        $this->setSubject($this->getSubjectFromAction());
        $this->setRecipientArray($recipient_array);

        require_once 'Mail.php';
        require_once 'Mail/mime.php';
    }

    public function getSubject() {
        return $this->subject;
    }
    public function setSubject($subject) {
        $this->subject = $subject;
    }

    public function getAction() {
        return $this->action;
    }
    public function setAction($action) {
        $this->action = $action;
    }

    public function getContent() {
        return $this->content;
    }
    public function setContent($content) {
        $this->content = $content;
    }

    public function getRecipientArray() {
        return $this->recipient_array;
    }
    public function setRecipientArray($recipient_array) {
        $this->recipient_array = $recipient_array;
    }

    function setSmtpInfo ($smtpInfo) {
        $this->smtpInfo = $smtpInfo;
    }

    function getSmtpInfo () {
        return $this->smtpInfo;
    }

    function sendMail () {
        isset($this->recipient_array['cc']) ? $recipient_array_cc = $this->recipient_array['cc'] : $recipient_array_cc = array();
        isset($this->recipient_array['bcc']) ? $recipient_array_bcc = $this->recipient_array['bcc'] : $recipient_array_bcc = array();

        $recipients = join(',', $this->recipient_array['to']);
        if (!empty($recipient_array_cc)) {
            $recipients .= ',' . join(',', $recipient_array_cc);
        }
        if (!empty($recipient_array_bcc)) {
            $recipients .= ',' . join(',', $recipient_array_bcc);
        }
        $crlf = "\n";
        //mail content
        $subject = $this->getSubjectFromAction();
        $html = $this->getContent();

        $headers = array(
            "From" => "System@rosewill.com",
            "To" => join(',', $this->recipient_array['to']),
            "Subject" => $subject
        );
        if (!empty($recipient_array_cc)) {
            $headers['Cc'] = join(',', $recipient_array_cc);
        }
        if (!empty($recipient_array_bcc)) {
            $headers['Bcc'] = join(',', $recipient_array_bcc);
        }

        $mime = new Mail_mime($crlf);
        $mime->addHTMLimage('images/rosewilllogo.png', 'image/png');
        $mime->setHTMLBody($html);
        $body = $mime->get();
        $headers = $mime->headers($headers);


        /* Create the mail object using the Mail::factory method */
        $mail_object =& Mail::factory("smtp", $this->smtpInfo);
        /* Ok send mail */
        $mail_object->send($recipients, $headers, $body);
        error_log(currentTime() . $subject . ' ' . 'e-mail has been sent to' . ' ' . $recipients);
    }

    private function  getSubjectFromAction () {
        /*get subject by input of action*/
        $subjectArray = array(
            'Request to Return Merchandise' => 'Request to Return Merchandise',
            'Request to Review Product' => 'Request to Review Product',
            'Sponsorship Request' => 'Sponsorship Request',
            'Vendor or Business Contact' => 'Vendor or Business Contact',
            'Other' => 'Uncategorized Contact',
            'i-reviewed-rosewill' => 'I Reviewed a Rosewill Product!'
        );
//        return $this->getAction();
        if (!isset($subjectArray[$this->getAction()])) {
            return $this->getAction();
        }
        return $subjectArray[$this->getAction()];
    }

}

?>