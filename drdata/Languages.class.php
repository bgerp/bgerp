<?php



/**
 * Клас 'drdata_Languages' -
 *
 *
 * @category  vendors
 * @package   drdata
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @data      http://en.wikipedia.org/wiki/List_of_ISO_639-1_codes
 * @todo:     Да се документира този клас
 */
class drdata_Languages extends core_Manager {
    
    
    /**
     * Заглавие
     */
    var $title = 'ISO информация за езиците по света';

    
    /**
     * Плъгини за зареждане
     */
    var $loadList = 'drdata_Wrapper,plg_Sorting';

    
    /**
     * Кой може да го разглежда?
     */
    var $canList = 'admin';
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD('languageName', 'varchar', 'caption=Име->Език');
        $this->FLD('nativeName', 'varchar', 'caption=Име->Собствено');
        $this->FLD('code', 'varchar(2)', 'caption=ISO 639-1->2 буквен код,rem=ISO 639-1 2 буквен код');
      
        $this->load('plg_RowTools');
        
        $this->setDbUnique('code');
       
    }

    
    /**
     * Извиква се след SetUp-а на таблицата за модела
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
        // Подготвяме пътя до файла с данните
        $file = "drdata/data/languagesList.csv";

        // Кои колонки ще вкарваме
        $fields = array(
            0 => "languageName",
            1 => "nativeName",
            2 => "code",
          
        );
        
        // Импортираме данните от CSV файла. 
        // Ако той не е променян - няма да се импортират повторно
        $cntObj = csv_Lib::importOnce($mvc, $file, $fields);

        // Записваме в лога вербалното представяне на резултата от импортирането
        $res .= $cntObj->html;
    }
    
}