<?php 


/**
 * Данните от рутиране за ServiceRules
 *
 * @category  bgerp
 * @package   email
 *
 * @author    Yusein Yuseinov <y.yuseinov@gmail.com>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class email_ServiceRulesData extends email_ServiceEmails
{
    /**
     * Плъгини за работа
     */
    public $loadList = 'email_Wrapper, plg_Sorting, plg_Search';
    
    public $searchFields = 'serviceId, accountId, uid, createdOn, data';
    
    
    /**
     * Заглавие на таблицата
     */
    public $title = 'Данни от сервизните имейли';
    
    
    /**
     * Кои полета да се показват в листовия изглед
     */
    public $listFields = 'id,msg=Имейл,files,createdOn';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->addFields();
        $this->FLD("serviceId", 'key(mvc=email_ServiceRules, allowEmpty)', "caption=Правила");
        
        $this->FLD('files', 'fileman_type_Files(align=vertical)', 'caption=Файлове, input=none');
    }
    
    
    /**
     * Добавя запис в модела
     * 
     * @param email_Mime  $mime
     * @param integer $accId
     * @param integer $uid
     * @param integer $serviceId
     * 
     * @return integer
     */
    public static function add($mime, $accId, $uid, $serviceId)
    {
        $rec = new stdClass();
        
        // Само първите 100К от писмото
        $rec->data = substr($mime->getData(), 0, 100000);
        $rec->accountId = $accId;
        $rec->uid = $uid;
        $rec->createdOn = dt::verbal2mysql();
        $rec->serviceId = $serviceId;
        
        // Подсигуряваме се, че сме записали файловете
        $mime->saveFiles();
        $rec->files = $mime->getFiles();
        
        return self::save($rec);
    }
}
