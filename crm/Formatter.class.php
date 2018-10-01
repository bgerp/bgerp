<?php


/**
 * Линкове за телефонни номера и факсове
 *
 *
 * @category  bgerp
 * @package   crm
 *
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class crm_Formatter extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'Линкове на телефон и факс';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_RowTools, crm_Wrapper, plg_State2,
    				 plg_Rejected, plg_Search, plg_Translate';
    
    
    /**
     * Права
     */
    public $canWrite = 'powerUser';
    
    
    /**
     * Кой има право да чете?
     */
    public $canRead = 'powerUser';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'powerUser';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'powerUser';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
    }
    
    
    /**
     * Рендиране на телефонен номер
     *
     * @param drdata_PhoneType $numbers
     * @param string           $prefix
     * @param int              $countryId
     */
    public static function renderTel($numbers, $prefix = null, $countryId = null)
    {
        // Дали линка да е абсолютен - когато сме в режим на принтиране и/или xhtml
        $isAbsolute = Mode::is('text', 'xhtml') || Mode::is('printing');
        
        $PhonesVerbal = cls::get('drdata_PhoneType');
        
        // парсирваме всеки телефон
        $parsTel = $PhonesVerbal->toVerbal($numbers);
        
        // Стил за иконата на класа
        $style = ht::getIconStyle('img/16/telephone2.png', '');
        
        if ($prefix != null) {
            $res = "<span class='linkWithIcon' style=\"{$style}\">" . $prefix. ' '. $parsTel . '</span>';
        } else {
            $res = "<span class='linkWithIcon' style=\"{$style}\">". $parsTel .'</span>';
        }
        
        return $res;
    }
    
    
    /**
     * Рендиране на факс номер
     *
     * @param drdata_PhoneType $numbers
     * @param string           $prefix
     * @param int              $countryId
     */
    public static function renderFax($numbers, $prefix = null, $countryId = null)
    {
        // Дали линка да е абсолютен - когато сме в режим на принтиране и/или xhtml
        $isAbsolute = Mode::is('text', 'xhtml') || Mode::is('printing');
        
        
        $PhonesVerbal = cls::get('drdata_PhoneType');
        
        // парсирваме всеки телефон
        $parsTels = $PhonesVerbal->toArray($numbers);
        
        if (is_array($parsTels)) {
            foreach ($parsTels as $parsTel) {
                $fax = $PhonesVerbal->getLink($numbers, $parsTel->original, true, null);
            }
        } else {
            $fax = $numbers;
        }
        
        // Стил за иконата на класа
        $style = ht::getIconStyle('img/16/fax2.png', '');
        
        if ($prefix != null) {
            $res = "<span class='linkWithIcon' style=\"{$style}\">" . $prefix. ' '. $fax .'</span>';
        } else {
            $res = "<span class='linkWithIcon' style=\"{$style}\">". $fax .'</span>';
        }
        
        return $res;
    }
    
    
    /**
     * Рендиране на телефонен номер
     *
     * @param drdata_PhoneType $numbers
     * @param string           $prefix
     * @param int              $countryId
     */
    public static function renderMob($numbers, $prefix = null, $countryId = null)
    {
        // Дали линка да е абсолютен - когато сме в режим на принтиране и/или xhtml
        $isAbsolute = Mode::is('text', 'xhtml') || Mode::is('printing');
        
        $PhonesVerbal = cls::get('drdata_PhoneType');
        
        // парсирваме всеки телефон
        $parsTel = $PhonesVerbal->toVerbal($numbers);
        
        // Стил за иконата на класа
        $style = ht::getIconStyle('img/16/mobile2.png', '');
        
        if ($prefix != null) {
            $res = "<span class='linkWithIcon' style=\"{$style}\">" . $prefix. ' '. $parsTel . '</span>';
        } else {
            $res = "<span class='linkWithIcon' style=\"{$style}\">". $parsTel .'</span>';
        }
        
        return $res;
    }
}
