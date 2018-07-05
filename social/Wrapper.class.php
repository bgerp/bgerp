<?php



/**
 * Клас 'social_Wrapper'
 *
 * Поддържа системното меню и табове-те на пакета 'social'
 *
 *
 * @category  bgerp
 * @package   social
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class social_Wrapper extends plg_ProtoWrapper
{
    /**
     * Описание на табовете
     */
    public function description()
    {
        $this->TAB('social_Sharings', 'Споделяне->Бутони', 'cms, social, admin, ceo');
        $this->TAB('social_SharingCnts', 'Споделяне->Броячи', 'cms, social, admin, ceo');
        $this->TAB('social_Followers', 'Проследяване', 'cms, social, admin, ceo');
                
        $this->title = 'SNM « Сайт';
        Mode::set('menuPage', 'Сайт:SNM');
    }
}
