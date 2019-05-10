<?php


/**
 * Съкратени URL-та към други класове
 *
 * @category  bgerp
 * @package   bgerp
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class bgerp_B extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Съкратени URL-та към други класове';
    
    
    /**
     * Съкратен екшън за отписване от blast имейлите
     */
    public function act_U()
    {
        return Request::forward(array('Ctr' => 'blast_Emails', 'Act' => 'Unsubscribe'));
    }
    
    
    /**
     * Съкратен екшън за отписване от blast имейлите
     */
    public function act_R()
    {
        $vid = Request::get('id');
        
        return blast_Redirect::doRedirect($vid);
    }
    
    
    /**
     * Проверява контролната сума към id-то, ако всичко е ОК - връща id, ако не е - FALSE
     */
    public function unprotectId_($id)
    {
        $act = strtolower($_GET['Act']);
        $ctr = strtolower($_GET['Ctr']);
        if ($act == 'r' || $ctr != 'b') {
            $this->protectId = false;
        }
        
        return parent::unprotectId_($id);
    }
    
    
    /**
     * Създава пряк път до статиите в блога
     */
    public function act_Default()
    {
        return Request::forward(array('Index'));
    }
}
