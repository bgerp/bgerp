<?php


/**
 * Клас 'page_Empty' - Шаблон за празна страница
 *
 * Файлът може да се подмени с друг
 *
 *
 * @category  ef
 * @package   page
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class page_Empty extends page_Html
{
    /**
     * Конструктор
     */
    public function __construct()
    {
        parent::__construct();
        $this->replace('UTF-8', 'ENCODING');
        $this->push('css/common.css', 'CSS');
        jquery_Jquery::enable($this);
        $this->push('js/efCommon.js', 'JS');
    }
    
    
    /**
     * Интерфейсен метод
     *
     * @see core_page_WrapperIntf
     */
    public function prepare()
    {
        parent::prepare();
    }
}
