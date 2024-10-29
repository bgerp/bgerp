<?php


/**
 * Прокси клас 'doc_Thread' - Теми в документната система
 *
 *
 * @category  bgerp
 * @package   doc
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2024 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class doc_ThreadsProxy extends core_Manager
{


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin';


    /**
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->forceProxy('doc_Threads');
    }
}
