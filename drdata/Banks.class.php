<?php 


/**
 * Клас 'drdata_Banks - Банки'
 *
 *
 * @category  vendors
 * @package   drdata
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class drdata_Banks extends core_Manager
{
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, drdata_Wrapper';
    
    
    /**
     * Полета, които ще се показват в листов изглед
     */
    var $listFields = 'name, bic , tools=Пулт';
    
    
    /**
     * Полето в което автоматично се показват иконките за редакция и изтриване на реда от таблицата
     */
    var $rowToolsField = 'tools';
    
    
    /**
     * Заглавие
     */
    var $title = 'Банки';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'admin, common';
    
    
    /**
     * Кой има право да променя?
     */
    var $canEdit = 'admin, common';
    
    
    /**
     * Кой има право да добавя?
     */
    var $canAdd = 'admin, common';
    
    
    /**
     * Кой може да го изтрие?
     */
    var $canDelete = 'admin, common';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
        $this->FLD('name',  'varchar(255)', 'caption=Име, mandatory');
        $this->FLD('bic',  'varchar(8)', 'caption=BIC/SWIFT, mandatory');
        
        $this->setDbUnique('bic');
    }
    
    
    /**
     *  Подреждаме банките по азбучен ред
     */
	static function on_BeforePrepareListRecs($mvc, &$res, $data)
    {
        $data->query->orderBy('#name');
    }
    
    
    /**
     * Записи за инициализиране на таблицата
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     */
    static function on_AfterSetupMvc($mvc, &$res)
    {
        $data = array(
            array('name' => 'Инвестбанк АД',  'bic' => 'IORTBGSF'),
            array('name' => 'Общинска банка АД', 'bic' => 'SOMBBGSF'),
            array('name' => 'ИНГ Банк Н.В. - кл. София', 'bic' => 'INGBBGSF'),
            array('name' => 'Първа инвестиционна банка АД ', 'bic' => 'FINVBGSF'),
            array('name' => 'Райфайзенбанк (България) ЕАД', 'bic' => 'RZBBBGSF'),
            array('name' => 'Българо-американска кредитна банка АД', 'bic' => 'BGUSBGSF'),
            array('name' => 'Банка Пиреос България АД', 'bic' => 'PIRBBGSF'),
            array('name' => 'МКБ Юнионбанк АД', 'bic' => 'CBUNBGSF'),
            array('name' => 'Обединена българска банка АД', 'bic' => 'UBBSBGSF'),
            array('name' => 'Регионална Инвестиционна банка - клон България', 'bic' => 'RIBRBG22'),
            array('name' => 'Корпоративна търговска банка АД', 'bic' => 'KORPBGSF'),
            array('name' => 'ПроКредит Банк (България) АД', 'bic' => 'PRCBBGSF'),
            array('name' => 'Търговска банка Д АД', 'bic' => 'DEMIBGSF'),
            array('name' => 'Ситибанк Н. А. - клон София', 'bic' => 'CITIBGSF'),
            array('name' => 'Токуда Банк АД', 'bic' => 'CREXBGSF'),
            array('name' => 'Банка ДСК ЕАД', 'bic' => 'STSABGSF'),
            array('name' => 'ТИ БИ АЙ Банк ЕАД', 'bic' => 'WEBKBGSF'),
            array('name' => 'Те-Дже Зираат Банкасъ - клон София', 'bic' => 'TCZBBGSF'),
            array('name' => 'ИШБАНК АГ- клон София КЧТ', 'bic' => 'ISBKBGSF'),
            array('name' => 'Сосиете Женерал Експресбанк АД', 'bic' => 'TTBBBG22'),
            array('name' => 'БНП Париба С. А. - клон София', 'bic' => 'BNPABGSX'),
            array('name' => 'Интернешънъл Асет Банк АД', 'bic' => 'IABGBGSF'),
            array('name' => 'Креди Агрикол България ЕАД', 'bic' => 'BINVBGSF'),
            array('name' => 'Алианц Банк България АД', 'bic' => 'BUINBGSF'),
            array('name' => 'Българска банка за развитие АД',    'bic' => 'NASBBGSF'),
            array('name' => 'УниКредит Булбанк АД', 'bic' => 'UNCRBGSF'),
            array('name' => 'Българска народна банка', 'bic' => 'BNBGBGSF'),
            array('name' => 'Централна кооперативна банка АД', 'bic' => 'CECBBGSF'),
            array('name' => 'СИБАНК ЕАД',    'bic' => 'BUIBBGSF'),
            array('name' => 'Алфа банка - клон България', 'bic' => 'CRBABGSF'),
            array('name' => 'Юробанк И Еф Джи България АД', 'bic' => 'BPBIBGSF'),
            array('name' => 'Българска народна банка (BG)', 'bic' => 'BNBGBGSD'),
            );
            
    	if(!$mvc->fetch("1=1")) {
            
            $nAffected = 0;
            
            foreach ($data as $rec) {
                $rec = (object)$rec;
                
                if (!$mvc->fetch("#bic='{$rec->bic}'")) {
                    if ($mvc->save($rec)) {
                        $nAffected++;
                    }
                }
            }
        }
        
        if ($nAffected) {
            $res .= "<li>Добавени са {$nAffected} записа.</li>";
        }
    }
    
    
    /**
     * Връща името на банката и нейния бик по зададен IBAN
     * @param string $iban
     * @return string $rec->bic or NULL
     */
    static function getBankName($iban)
    {
    	$parts = iban_Type::getParts($iban);
    	
    	if($rec = static::fetch("#bic LIKE '%{$parts->bic}%'")) {
    		return $rec->name;
    	} else {
    		return NULL;
    	}
    }
    
    
     /**
     * Връща името на бика на банката  по зададен IBAN
     * @param string $iban
     * @return string $rec->bic or NULL
     */
    static function getBankBic($iban)
    {
    	if($rec = static::fetch("#bic LIKE '%{$parts->bic}%'")) {
    		return $rec->bic;
    	} else {
    		return NULL;
    	}
    }
}