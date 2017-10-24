<?php



/**
 * Масово разпращане - опаковка
 *
 *
 * @category  bgerp
 * @package   blast
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class blast_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    function description()
    {
        $this->TAB('blast_ListDetails', 'Списъци', 'blast,ceo,admin');
        $this->TAB('blast_Lists', 'Списъци', 'blast,ceo,admin');
        $this->TAB('blast_Emails', 'Имейли', 'ceo, blast');
        $this->TAB('blast_Letters', 'Писма', 'ceo, blast');
        $this->TAB('blast_BlockedEmails', 'Блокирани', 'ceo,blast,admin');
        
        $this->title = 'Масово разпращане';
        Mode::set('menuPage', 'Разпращане');
    }
}