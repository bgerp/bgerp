<?php


/**
 * Клас 'drdata_Languages' -
 *
 *
 * @category  bgerp
 * @package   drdata
 *
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2016 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @data      http://en.wikipedia.org/wiki/List_of_ISO_639-1_codes
 * @todo:     Да се документира този клас
 */
class drdata_Languages extends core_Manager
{
    /**
     * Заглавие
     */
    public $title = 'ISO информация за езиците по света';
    
    
    /**
     * Плъгини за зареждане
     */
    public $loadList = 'drdata_Wrapper,plg_Sorting';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'admin';
    
    
    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        $this->FLD('languageName', 'varchar', 'caption=Име->Език');
        $this->FLD('nativeName', 'varchar', 'caption=Име->Собствено');
        $this->FLD('code', 'varchar(2)', 'caption=ISO 639-1->2 буквен код,rem=ISO 639-1 2 буквен код,tdClass=centerCol');
        
        $this->load('plg_RowTools2');
        
        $this->setDbUnique('code');
    }
    
    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    public static function on_AfterSetupMVC($mvc, &$res)
    {
        // Подготвяме пътя до файла с данните
        $file = 'drdata/data/languagesList.csv';
        
        // Кои колонки ще вкарваме
        $fields = array(
            0 => 'languageName',
            1 => 'nativeName',
            2 => 'code',
        
        );
        
        // Импортираме данните от CSV файла.
        // Ако той не е променян - няма да се импортират повторно
        $cntObj = csv_Lib::importOnce($mvc, $file, $fields);
        
        // Записваме в лога вербалното представяне на резултата от импортирането
        $res .= $cntObj->html;
    }
}
