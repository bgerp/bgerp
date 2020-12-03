<?php


/**
 * 
 * 
 * @category  bgerp
 * @package   pami
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class pami_Wrapper extends plg_ProtoWrapper
{
    /**
     * Описание на табовете
     */
    public function description()
    {
        $this->TAB('pami_Logs', 'Логове', 'ceo,admin,pami');
        
        $this->title = 'PAMI';
    }
}
