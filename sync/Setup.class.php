<?php


/**
 * Експортиране на фирми->Група
 */
defIfNot('SYNC_COMPANY_GROUP', '');

/**
 * Име на собствената компания (тази за която ще работи bgERP)
 */
defIfNot('SYNC_ESHOP_GROUPS', '');


/**
 * Държавата на собствената компания (тази за която ще работи bgERP)
 */
defIfNot('SYNC_EXPORT_URL', '');


/**
 * Експортиране на групи на артикулите->Групи
 */
defIfNot('SYNC_PROD_GROUPS', '');


/**
 * Позволени IP-та за експорт
 */
defIfNot('SYNC_EXPORT_ADDR', '');


/**
 * Колко процента да е себестойноста на импортирания артикул спрямо оферираната му цена
 */
defIfNot('SYNC_IMPORTED_PRODUCT_PRIMECOST_DISCOUNT', '');


/**
 * Клас 'sync_Setup'  
 *
 *
 * @category  bgerp
 * @package   sync
 *
 * @author    Milen Georgiev <milen@experta.bg>
 * @copyright 2006 - 2020 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class sync_Setup extends core_ProtoSetup
{
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'sync_Map';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
        
    /**
     * Описание на модула
     */
    public $info = 'Синхронизиране на данните между две bgERP системи';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        'SYNC_EXPORT_URL' => array('url', 'caption=Импортиране->URL'),
        'SYNC_EXPORT_ADDR' => array('varchar', 'caption=Позволени IP-та за експорт->IP'),
        'SYNC_COMPANY_GROUP' => array('key(mvc=crm_Groups, allowEmpty)', 'caption=Експортиране на фирми->Група'),
        'SYNC_PROD_GROUPS' => array('keylist(mvc=cat_Groups, select=name, allowEmpty)', 'caption=Експортиране на групи на артикулите->Групи'),
        'SYNC_IMPORTED_PRODUCT_PRIMECOST_DISCOUNT' => array('percent(min=0,max=1)', 'caption=Колко % под офертната цена да е себестойността на импортирания артикул->Процент'),
    );
    
   
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'sync_Map',
    );
    
    
    /**
     * Връща описанието на web-константите
     *
     * @return array
     */
    public function getConfigDescription()
    {
        $description = parent::getConfigDescription();
        if (core_Packs::isInstalled('eshop')) {
            $description['SYNC_ESHOP_GROUPS'] = array('keylist(mvc=eshop_Groups, select=name, allowEmpty)', 'caption=Експортиране на е-магазин->Групи');
        }
        
        return $description;
    }
    
    
    /**
     * Инсталиране на пакета
     */
    public function install()
    {
        $html = parent::install();
        
        $Bucket = cls::get('fileman_Buckets');
        $html .= $Bucket->createBucket('importedProductFiles', 'Файлове от импортирани артикули', null, '1GB', 'user', 'user');
        
        return $html;
    }
}
