<?php
/**
 * Клас 'zenquotes_Quotes' - Цитати
 *
 * Цитати с техните авторите
 *
 *
 * @category  bgerp
 * @package   zenquotes
 *
 * @author    David Dimitriev 
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */

class zenquotes_Quotes extends core_Manager
{
    /**
     * Заглавие на модула
     */
    public $title = 'Цитати от ZenQuotes API';

    /**
     * Зареждане на използваните мениджъри
     */
    public $loadList = 'plg_RowTools2, plg_Created, plg_Modified, plg_Sorting';
    
    /**
     * Кой може да редактира системните данни
     */
    public $canEditsysdata = 'ceo, admin';
    
    /**
     * Кой може да редактира системните данни
     */
    public $canDeletesysdata = 'ceo, admin';
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,acc,admin';
    public $canEdit = 'ceo, admin';
    public $canDelete = 'ceo, admin';
    public $canAdd = 'ceo, admin';

    /**
     * Описание на модела (таблицата)
     */
    public function description()
    {
        // Автор на цитата
        $this->FLD('author', 'varchar(255)', 'caption=Автор');
        // Текст на цитата
        $this->FLD('quote', 'text', 'caption=Цитат');
        //Уникалност на комбинацията автор + цитат,
        $this->setDbUnique('author, quote');
    }

    /**
     * Крон за обновяване на цитатите
     */
    public function cron_getQuotes()
    {
        $apiKey = zenquotes_Setup::get('API_KEY'); // Замени с реалния API ключ
        $jsonRes = @file_get_contents("https://zenquotes.io/api/quotes?apikey={$apiKey}");

        if ($jsonRes === false) {
        $error = error_get_last();
        self::logWarning('HTTP error: ' . $error['message']);
        return;
}
        $quotes = json_decode($jsonRes, true);
        // Обхожда върнатите от API-то записи и ги записва,
        // като 'IGNORE' пропуска записи, които нарушават UNIQUE ограничението
        if (is_array($quotes) && !empty ($quotes)) {
            foreach ($quotes as $quoteData)
                {
                    $rec = new stdClass();
                    $rec->quote = $quoteData['q'];
                    $rec->author = $quoteData['a'];

                    self::save($rec, null, 'IGNORE');
                }
        }
    }
}