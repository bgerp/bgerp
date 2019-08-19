<?php


/**
 * Клас 'page_Waiting' - Страница за изчакване. Показва съобщение и анимация за изчакване.
 *
 * @category  ef
 * @package   page
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class page_Waiting extends page_Empty
{
    /**
     * Интерфейсен метод
     *
     * @see core_page_WrapperIntf
     */
    public function prepare()
    {
        $this->appendOnce('Моля изчакайте...', 'PAGE_CONTENT');
        
        $this->appendOnce("\n" . '<meta http-equiv="refresh" content="3">', 'HEAD');
        
        parent::prepare();
    }
}
