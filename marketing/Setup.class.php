<?php


/**
 * Имейл от който да се изпрати нотифициращ имейл че е направено запитване
 */
defIfNot('MARKETING_INQUIRE_FROM_EMAIL', '');


/**
 * Имейл на който да се изпрати нотифициращ имейл че е направено запитване
 */
defIfNot('MARKETING_INQUIRE_TO_EMAIL', '');


/**
 * Колко количества да се показват по дефолт във запитването
 */
defIfNot('MARKETING_INQUIRY_QUANTITIES', 3);


/**
 * Маркетинг - инсталиране / деинсталиране
 *
 *
 * @category  bgerp
 * @package   marketing
 * @author    Ivelin Dimov <ivelin_pdimov@abv.bg> и Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class marketing_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'marketing_Inquiries2';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Маркетинг и реклама";
    
    
    /**
	 * Описание на конфигурационните константи
	 */
	var $configDescription = array(
			'MARKETING_INQUIRE_FROM_EMAIL'  => array('key(mvc=email_Inboxes,select=email,allowEmpty)', 'caption=Изпращане на запитването по имейл->Имейл \'От\''),
			'MARKETING_INQUIRE_TO_EMAIL'    => array('emails', 'caption=Изпращане на запитването по имейл->Имейл \'Към\''),
			'MARKETING_INQUIRY_QUANTITIES'          => array('int', 'caption=Брой количества във запитването'),
	);
	
	
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    var $managers = array(
    		'marketing_Inquiries2',
            'marketing_Bulletins',
            'marketing_BulletinSubscribers',
            'migrate::updateBulletinsRecs2',
            'migrate::updateBulletinsBrid'
        );

        
    /**
     * Роли за достъп до модула
     */
    var $roles = 'marketing';

    
    /**
     * Връзки от менюто, сочещи към модула
     */
    var $menuItems = array(
            array(3.1, 'Търговия', 'Маркетинг', 'marketing_Inquiries2', 'default', "ceo, marketing"),
        );

    
	/**
     * Инсталиране на пакета
     */
    function install()
    {
    	$html = parent::install();
    	
    	// Добавяне на кофа за файлове свързани със задаията
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('InquiryBucket', 'Снимки', 'jpg,jpeg,image/jpeg,gif,png', '10MB', 'user', 'every_one');
        
        $Plugins = cls::get('core_Plugins');
        $html .= $Plugins->forcePlugin('Бюлетин за външната част', 'marketing_BulletinPlg', 'cms_page_External', 'private');
        
        return $html;
    }
    
    
    /**
     * Миграция за обновява всички записи, за да се обнови кеша
     */
    static function updateBulletinsRecs2()
    {
        $query = marketing_Bulletins::getQuery();
        while ($rec = $query->fetch()) {
            marketing_Bulletins::save($rec);
        }
    }
    
    
    /**
     * Миграция за вземане на brid и ip от стария модел
     */
    static function updateBulletinsBrid()
    {
        if (!cls::load('marketing_Bulletin', TRUE)) continue;
        $mBulletin = cls::get('marketing_Bulletin');
        if($mBulletin->db->tableExists($mBulletin->dbTableName)) {
            $query = $mBulletin->getQuery();
            while ($rec = $query->fetch()) {
                if (!$rec->brid && !$rec->ip) continue;
                
                $sQuery = marketing_BulletinSubscribers::getQuery();
                $sQuery->where(array("#email = '[#1#]'", $rec->email));
                $sQuery->where("#ip IS NULL");
                $sQuery->orWhere("#brid IS NULL");
                
                while ($nRec = $sQuery->fetch()) {
                    
                    $mustSave = FALSE;
                    
                    if (!$nRec->brid) {
                        $nRec->brid = $rec->brid;
                        $mustSave = TRUE;
                    }
                    
                    if (!$nRec->ip) {
                        $nRec->ip = $rec->ip;
                        $mustSave = TRUE;
                    }
                    
                    if ($mustSave) {
                        marketing_BulletinSubscribers::save($nRec, 'ip, brid');
                    }
                }
            }
        }
    }
}
