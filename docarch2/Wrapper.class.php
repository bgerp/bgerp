<?php


/**
 * Клас 'docarch2_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'docarch'
 *
 *
 * @category  bgerp
 * @package   docarch2
 *
* @author    Angel Trifonov angel.trifonoff@gmail.com
 * @copyright 2006 - 2021 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class docarch2_Wrapper extends plg_ProtoWrapper
{
    /**
     * Описание на табовете
     */
    public function description()
    {
       
        $this->TAB('docarch2_Registers', 'Регистри','ceo,docarch');
        
        $this->TAB('docarch2_Volumes', 'Томове','ceo,docarch');

        $this->TAB('docarch2_Movements', 'Движения','ceo,docarch');

        $this->TAB('docarch2_State', 'Състояния','ceo,docarch');

        $this->TAB('docarch2_ContainerTypes', 'Контейнери','ceo,docarch');
        
        $this->title = 'Архивиране';
    }
}
