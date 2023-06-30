<?php


/**
 *
 *
 * @category  bgerp
 * @package   openai
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2023 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class openai_Prompt extends core_Manager
{


    /**
     * @var string
     */
    public static $extractContactDataBg = 'extract-contact-data';


    /**
     * @var string
     */
    public static $extractContactDataEn = 'extract-contact-data-en';

    /**
     * Заглавие на мениджъра
     */
    public $title = 'Въпроси';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Created, plg_Modified, openai_Wrapper, plg_Sorting, plg_Search, plg_RowTools2';


    /**
     * Кой има право да го променя?
     */
    public $canEdit = 'openai, admin';


    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'openai, admin';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'openai, admin';


    /**
     * Кой има право да изтрива?
     */
    public $canDelete = 'openai, admin';


    /**
     * Кой може да редактира системните роли
     */
    public $canEditsysdata = 'openai, admin';


    /**
     * Кой има право да изтрива потребителите, създадени от системата?
     */
    public $canDeletesysdata = 'openai, admin';


    /**
     * Полета по които се прави пълнотекстово търсене от плъгина plg_Search
     */
    public $searchFields = 'systemId, prompt';


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('systemId', 'varchar(64)', 'caption=Ключ');
        $this->FLD('prompt', 'text', 'caption=Въпрос');
        $this->FLD('emailIgnoreWords', 'text', 'caption=Думи за игнориране->От имейла');
        $this->FLD('ignoreWords', 'text', 'caption=Думи за игнориране->От отговора');

        $this->setDbUnique('systemId');
    }


    /**
     * Връща въпроса за systemId
     *
     * @param $systemId
     *
     * @return boolean|string
     */
    public static function getPromptBySystemId($systemId)
    {

        return self::fetchField(array("#systemId = '[#1#]'", $systemId), 'prompt');
    }


    /**
     * @param $mvc
     * @param $data
     * @return void
     */
    protected static function on_AfterPrepareEditForm($mvc, &$data)
    {
        if ($data->form->rec->id) {
            $data->form->setReadOnly('systemId');
        }
    }


    /**
     * Изпълнява се след подготвянето на формата за филтриране
     */
    protected static function on_AfterPrepareListFilter($mvc, &$res, $data)
    {
        $data->listFilter->view = 'horizontal';
        $data->listFilter->showFields = 'search';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');

        $data->query->orderBy('modifiedOn', 'DESC');
        $data->query->orderBy('id', 'DESC');
    }


    /**
     * Само за преход между старата версия
     */
    public static function on_AfterSetupMVC($mvc, &$res)
    {
        self::addDefaultParams();
    }


    /**
     * Помощна функция за добавяне на дефолтни параметри
     */
    public static function addDefaultParams()
    {
        $recBg = self::fetch(array("#systemId = '[#1#]'", static::$extractContactDataBg));

        if (!$recBg) {
            $recBg = new stdClass();
            $recBg->systemId = static::$extractContactDataBg;
            $recBg->prompt = "Извлечи следните контактни данни на изпращача от по-долния имейл и от резултата премахни редовете без съвпадение.\n";
            $recBg->prompt .= "Име на фирмата->company\n";
            $recBg->prompt .= "Име на лицето->attn\n";
            $recBg->prompt .= "Населено място->place\n";
            $recBg->prompt .= "Пощенски код->pcode\n";
            $recBg->prompt .= "Адрес->address\n";
            $recBg->prompt .= "Имейл->email\n";
            $recBg->prompt .= "Телефон->tel\n";
            $recBg->prompt .= "Мобилен->pMobile\n";
            $recBg->prompt .= "Данъчен номер->vatNo\n";
            $recBg->prompt .= "Уеб сайт->web\n";
            $recBg->prompt .= "\n\n";
            $recBg->prompt .= "[#subject#]";
            $recBg->prompt .= "\n";
            $recBg->prompt .= "От: [#fromEmail#] ([#from#])";
            $recBg->prompt .= "\n";
            $recBg->prompt .= "[#email#]";
            $recBg->ignoreWords = implode("\n", array('-', 'none', 'N/A', 'Unknown', 'Not Specified', '*not provided*'));

            self::save($recBg);
        }

        $recEn = self::fetch(array("#systemId = '[#1#]'", static::$extractContactDataEn));
        if (!$recEn) {
            $recEn = new stdClass();
            $recEn->systemId = static::$extractContactDataEn;
            $recEn->prompt = "Please extract the following sender's contact details from the email below and remove the unmatched lines from the result.\n";
            $recEn->prompt .= "Person name->attn\n";
            $recEn->prompt .= "Person gender\n";
            $recEn->prompt .= "Job position,\n";
            $recEn->prompt .= "Mobile->pMobile\n";
            $recEn->prompt .= "Company->company\n";
            $recEn->prompt .= "Country->country\n";
            $recEn->prompt .= "Postal code->pcode\n";
            $recEn->prompt .= "Place->place\n";
            $recEn->prompt .= "Street address->address\n";
            $recEn->prompt .= "Company telephone->tel\n";
            $recEn->prompt .= "Web site->web\n";
            $recEn->prompt .= "VAT number->vatNo\n";
            $recEn->prompt .= "Email->email\n";
            $recEn->prompt .= "\n\n";
            $recEn->prompt .= "[#subject#]";
            $recEn->prompt .= "\n";
            $recEn->prompt .= "From: [#fromEmail#] ([#from#])";
            $recEn->prompt .= "\n";
            $recEn->prompt .= "[#email#]";
            $recEn->ignoreWords = implode("\n", array('-', 'none', 'N/A', 'Unknown', 'Not Specified', '*not provided*'));

            self::save($recEn);
        }
    }
}
