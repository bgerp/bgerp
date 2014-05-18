<?php




/**
 * Линкове за телефонни номера и факсове
 *
 *
 * @category  bgerp
 * @package   crm
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class crm_Formatter extends core_Manager
{
   
	
    /**
     * Заглавие
     */
    var $title = "Линкове на телефон и факс";
    
    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'plg_Created, plg_RowTools, crm_Wrapper, plg_State2,
    				 plg_Rejected, plg_Search, plg_Translate';

    
    /**
     * Права
     */
    var $canWrite = 'powerUser';
    
    
    /**
     * Кой има право да чете?
     */
    var $canRead = 'powerUser';
    
    
    /**
	 * Кой може да го разглежда?
	 */
	var $canList = 'powerUser';


	/**
	 * Кой може да разглежда сингъла на документите?
	 */
	var $canSingle = 'powerUser';
    
    
    /**
     * Описание на модела
     */
    function description()
    {
    	
    }

    
    /**
     * Рендиране на телефонен номер
     * 
     * @param drdata_PhoneType $numbers
     * @param string $prefix
     * @param int $countryId
     */
    public static function renderTel($numbers, $prefix = NULL, $countryId = NULL)
    {
    	
        if (Mode::is('screenMode', 'wide') && $prefix != NULL) {
        	// Дали линка да е абсолютен - когато сме в режим на принтиране и/или xhtml 
        	$isAbsolute = Mode::is('text', 'xhtml') || Mode::is('printing');
        
        	// Иконата на класа
        	$icon = sbf("img/16/telephone2.png", '', $isAbsolute);
        	
        	$res = "<img class='communicationImg' src='{$icon}' />{$prefix}<span class='communication'>" . " ". $numbers ."</span>";
        	//$res = "<span 'class' => 'linkWithIcon', 'style' => 'background-image:url(' . sbf('img/16/telephone2.png') . ');'>" . $prefix. " ". $numbers . "</span>";
        } elseif (Mode::is('screenMode', 'narrow') && $prefix != NULL) {
        	$res = $prefix. "<span class='communication'>" . " ". $numbers ."</span>";
        } else {
        	$res = "<img class='communicationImg' src='{$icon}' /><span class='communication'>". $numbers ."</span>";
        }
   
        return $res;
    }
 

    /**
     * Рендиране на факс номер
     * 
     * @param drdata_PhoneType $numbers
     * @param string $prefix
     * @param int $countryId
     */
    public static function renderFax($numbers, $prefix = NULL, $countryId = NULL)
    {
    	if (Mode::is('screenMode', 'wide') && $prefix != NULL) {
        	// Дали линка да е абсолютен - когато сме в режим на принтиране и/или xhtml 
        	$isAbsolute = Mode::is('text', 'xhtml') || Mode::is('printing');
        
        	// Иконата на класа
        	$icon = sbf("img/16/fax2.png", '', $isAbsolute);
        	
        	$res = "<img class='communicationImg' src='{$icon}' />{$prefix}<span class='communication'>" . " ". $numbers ."</span>";
        	//$res = "<span 'class' => 'linkWithIcon', 'style' => 'background-image:url(' . sbf('img/16/telephone2.png') . ');'>" . $prefix. " ". $numbers . "</span>";
        } elseif (Mode::is('screenMode', 'narrow') && $prefix != NULL) {
        	$res = $prefix. "<span class='communication'>" . " ". $numbers ."</span>";
        } else {
        	$res = "<img class='communicationImg' src='{$icon}' /><span class='communication'>". $numbers ."</span>";
        }
   
        return $res;
    }
    
    
    /**
     * Превръщане на телефонните номера и факсове в линкове
     * 
     * @param varchar $verbal
     * @param drdata_PhoneType $canonical
     * @param boolean $isFax
     */
    public static function getLink_($verbal, $canonical, $isFax = FALSE)
    {
        $conf = core_Packs::getConfig('crm'); 
        
        $PhonesCanonical = cls::get('drdata_PhoneType');
        
        if (Mode::is('screenMode', 'wide') && $conf->CRM_TEL_LINK_WIDE == 'yes') {
        	$res = $PhonesCanonical->toVerbal($canonical) ;
        }
        
    	if (Mode::is('screenMode', 'narrow') && $conf->CRM_TEL_LINK_NARROW == 'yes') {
        	$res = $PhonesCanonical->toVerbal($canonical) ;
        }

        return $res;
    }
   
}