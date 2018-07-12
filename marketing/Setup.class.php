<?php


/**
 * Имейл от който да се изпрати нотифициращ имейл, че е направено запитване
 */
defIfNot('MARKETING_INQUIRE_FROM_EMAIL', '');


/**
 * Имейл на който да се изпрати нотифициращ имейл, че е направено запитване
 */
defIfNot('MARKETING_INQUIRE_TO_EMAIL', '');


/**
 * Маркетинг - инсталиране / деинсталиране
 *
 *
 * @category  bgerp
 * @package   marketing
 *
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg> и Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class marketing_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'marketing_Inquiries2';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Маркетинг и реклама';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'MARKETING_INQUIRE_FROM_EMAIL' => array('key(mvc=email_Inboxes,select=email,allowEmpty)', 'caption=Изпращане на запитването по имейл->Имейл \'От\''),
        'MARKETING_INQUIRE_TO_EMAIL' => array('emails', 'caption=Изпращане на запитването по имейл->Имейл \'Към\''),
    );
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'marketing_Inquiries2',
        'marketing_Bulletins',
        'marketing_BulletinSubscribers',
    );
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = 'marketing';
    
    
    /**
     * Връзки от менюто, сочещи към модула
     */
    public $menuItems = array(
        array(3.1, 'Търговия', 'Маркетинг', 'marketing_Inquiries2', 'default', 'ceo, marketing'),
    );
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        // Добавяне на кофа за файлове свързани със задаията
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('InquiryBucket', 'Запитвания', '', '10MB', 'every_one', 'every_one');
        
        $Plugins = cls::get('core_Plugins');
        $html .= $Plugins->forcePlugin('Бюлетин за външната част', 'marketing_BulletinPlg', 'cms_page_External', 'private');
        
        return $html;
    }
}
