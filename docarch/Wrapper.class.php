<?php


/**
 * Клас 'docarch_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'docarch'
 *
 *
 * @category  bgerp
 * @package   docarch
 *
* @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2019 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class docarch_Wrapper extends plg_ProtoWrapper
{
    /**
     * Описание на табовете
     */
    public function description()
    {
       
        $this->TAB('docarch_Archives', 'Архиви','ceo,docarch');
        
        $this->TAB('docarch_Volumes', 'Томове','ceo,docarch');

        $this->TAB('docarch_Movements', 'Движения','ceo,docarch');
        
        $this->title = 'Архивиране';
    }
}
