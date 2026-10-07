<?php


/**
 * Изключение за предвидени грешки в ПОС-а, които се показват на потребителя без да се репортват
 *
 *
 * @category  bgerp
 * @package   pos
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg>
 * @copyright 2006 - 2026 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class pos_TerminalException extends core_exception_Expect
{
    /**
     * Генерира exception от съотв. клас, в случай че зададеното условие не е изпълнено
     *
     * @param mixed  $condition
     * @param string $message
     *
     * @throws pos_TerminalException
     */
    public static function expect($condition, $message)
    {
        if (!(boolean) $condition) {
            throw new pos_TerminalException($message, 'Изключение', array($message));
        }
    }
}
