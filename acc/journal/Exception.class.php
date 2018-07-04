<?php
class acc_journal_Exception extends core_exception_Expect
{
    
    
    /**
     * Генерира exception от съотв. клас, в случай че зададеното условие не е изпълнено
     *
     * @param  boolean $condition
     * @param  string  $message
     * @param  array   $options
     * @throws static
     */
    public static function expect($condition, $message, $options = array())
    {
        if (!(boolean) $condition) {
            throw new static($message, $options);
        }
    }
}
