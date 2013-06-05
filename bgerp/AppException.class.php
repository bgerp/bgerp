<?php
class bgerp_AppException extends Exception
{
    public $options;

    public function __construct($message, $options = array())
    {
        parent::__construct($message);

        $this->options = $options;
    }

    public function __toString()
    {
        if (!empty($this->options['redirect'])) {
            $redirect = $this->options['redirect'];
            unset($this->options['redirect']);
        }
        
        $message = $this->getMessage() . ': ' . json_encode($this->options);
        
        if (empty($redirect)) {
            core_Message::redirect($message, 'page_Error');
        } else {
            core_App::redirect($redirect, FALSE, $message, 'error');
        }
    }
}
