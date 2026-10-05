<?php


/**
 * Заявка за пренасочване, която може да се обработи и без HTTP отговор.
 * Междинните catch (Exception/Throwable) трябва да я предават нагоре;
 * изпращането на отговора е отговорност на външния обработчик в boot.
 *
 * @category   bgerp
 * @package    core
 * @author     Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright  2026 Experta OOD
 * @license    GPL 3
 * @since      2026-10-02
 */
class core_exception_Redirect extends Exception
{
    public $url;
    public $statusMessage;
    public $messageType;
    public $permanent;
    public $ajax;
    public $hitId;
    public $controller;
    public $action;
    public $statusUserId;


    /**
     * Фиксира данните за отговора преди finally блоковете да възстановят контекста.
     *
     * @param string      $url        Готов URL за пренасочването
     * @param string|null $message    Оригинално статус съобщение с преводните маркери
     * @param string      $type       Тип на статус съобщението
     * @param bool        $permanent  Постоянно HTTP пренасочване
     * @param bool        $ajax       JSON отговор вместо HTTP Location
     * @param string|null $hitId      Идентификатор на хита
     * @param string|null $controller Изходният контролер, когато URL е подаден като масив
     * @param string|null $action     Изходното действие
     */
    public function __construct($url, $message = null, $type = 'notice', $permanent = false, $ajax = false, $hitId = null, $controller = null, $action = null)
    {
        parent::__construct($message === null ? 'Redirect' : (string) $message);
        $this->url = $url;
        $this->statusMessage = $message;
        $this->messageType = $type;
        $this->permanent = (bool) $permanent;
        $this->ajax = (bool) $ajax;
        $this->hitId = $hitId;
        $this->controller = $controller;
        $this->action = $action;
    }
}
