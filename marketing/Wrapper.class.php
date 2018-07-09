<?php


/**
 * Маркетинг - опаковка
 *
 *
 * @category  bgerp
 * @package   marketing
 *
 * @author    Ivelin Dimov <ivelin_pdimov@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class marketing_Wrapper extends plg_ProtoWrapper
{
    /**
     * Описание на табовете
     */
    public function description()
    {
        $this->TAB('marketing_Inquiries2', 'Запитвания', 'ceo,marketing');
        $this->TAB('marketing_Bulletins', 'Бюлетин', 'ceo,marketing');
        $this->TAB('marketing_BulletinSubscribers', 'Абонирани', 'ceo,marketing');
        
        $this->title = 'Маркетинг « Търговия';
        Mode::set('menuPage', 'Търговия:Маркетинг');
    }
}
