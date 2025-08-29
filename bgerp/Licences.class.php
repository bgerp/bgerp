<?php


/**
 *
 *
 * @category  bgerp
 * @package   bgerp
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2025 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class bgerp_Licences extends core_Manager
{


    /**
     * Заглавие на мениджъра
     */
    public $title = 'Лицензи';


    /**
     * Плъгини за зареждане
     */
    public $loadList = 'plg_Search';


    /**
     * Кой има право да го променя?
     */
    public $canEdit = 'no_one';


    /**
     * Кой има право да добавя?
     */
    public $canAdd = 'no_one';


    /**
     * Кой може да го разглежда?
     */
    public $canList = 'debug';


    /**
     * Кой има право да изтрива?
     */
    public $canDelete = 'no_one';


    /**
     * Полета за търсене
     */
    public $searchFields = 'feature, licenseCode';


    /**
     * Кой има право да променя системните данни?
     */
    public $canDeletesysdata = 'no_one';


    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('feature', 'varchar(64)', 'caption=Услуга');
        $this->FLD('licenseCode', 'varchar(16)', 'caption=Лицензен код');
        $this->FLD('validUntil', 'datetime(format=smartTime)', 'caption=Валиден до');

        $this->setDbIndex('feature');
    }


    /**
     * Метод addLicense($feature) който добавя feature в таблицата, ако го няма.
     * Лицензния код се генерира от първите 16 символа на MD5 на името на фичъра в малки букви.
     *
     * @param string $feature - името на функцията
     * @param null|string $validUntil - до кога е валиден лиценза
     * @param null|string $salt - сол за генериране на лицензионния код
     *
     * @return integer
     */
    public static function addLicense($feature, $validUntil = null, $salt = null)
    {
        $feature = trim($feature);

        expect($feature);

        $query = self::getQuery();
        $query->where(array("#feature = '[#1#]'", $feature));
        if (isset($validUntil)) {
            $query->where(array("#validUntil >= '[#1#]'", $validUntil));
        }
        $query->limit(1);
        $rec = $query->fetch();

        if ($rec) {

            return $rec;
        }

        $rec = new stdClass();
        $rec->feature = $feature;
        $rec->licenseCode = self::getLicenseCode($rec->feature, $salt);
        $rec->validUntil = $validUntil;

        return self::save($rec, null, 'IGNORE');
    }


    /**
     * Проверява лиценза за дадена функция
     *
     * @param string $feature - името на функцията
     * @param boolean $beautify - разкрасява ли кода с тирета
     * @param null|string $salt - сол за генериране на лицензионния код
     *
     * @return string|false - връща лицензионния код или false
     */
    public static function checkLicense($feature, $beautify = true, $salt = null)
    {
        $rec = self::fetch(array("#feature = '[#1#]'", $feature));
        if (!$rec) {

            return false;
        }

        $expectedCode = self::getLicenseCode($feature, $salt);

        if ($rec->licenseCode === $expectedCode && (!isset($rec->validUntil) || ($rec->validUntil > dt::now()))) {
            if ($beautify) {

                return self::beautifyLicenseCode($rec->licenseCode);
            } else {

                return mb_strtoupper($rec->licenseCode);
            }
        } else {

            return false;
        }
    }


    /**
     * След преобразуване на записа в четим за хора вид.
     *
     * @param core_Mvc $mvc
     * @param stdClass $row Това ще се покаже
     * @param stdClass $rec Това е записа в машинно представяне
     */
    public static function on_AfterRecToVerbal($mvc, &$row, $rec, $fields = array())
    {
        $row->licenseCode = $mvc->beautifyLicenseCode($rec->licenseCode);
        if (!$row->validUntil) {
            $row->validUntil = tr('Безсрочно');
        }
    }


    /**
     * Подготовка на филтър формата
     *
     * @param core_Mvc $mvc
     * @param StdClass $data
     */
    protected static function on_AfterPrepareListFilter($mvc, &$data)
    {
        $data->listFilter->showFields = 'search';
        $data->listFilter->view = 'horizontal';
        $data->listFilter->toolbar->addSbBtn('Филтрирай', array($mvc, 'list'), 'id=filter', 'ef_icon = img/16/funnel.png');
    }


    /**
     * Форматира лицензионния код за по-добра четимост
     *
     * @param string $code - лицензионния код
     *
     * @return string - връща форматирания код
     */
    protected static function beautifyLicenseCode($code)
    {

        return implode("-", str_split(mb_strtoupper($code), 4));
    }


    /**
     * Генерира лицензионен код за дадена функция
     *
     * @param string $feature - името на функцията
     *
     * @return string - връща лицензионния код
     */
    protected static function getLicenseCode($feature, $salt = null)
    {
        setIfNot($salt, core_Setup::getBGERPUniqId());

        return substr(md5($salt . '|' . strtolower(trim($feature))), 0, 16);
    }
}
