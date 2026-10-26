<?php


/**
 * Дефолтен шаблон за декларации на български
 */
defIfNot('DEC_DEF_TPL_BG', '');


/**
 * Дефолтен шаблон за декларации на английски
 */
defIfNot('DEC_DEF_TPL_EN', '');

/**
 * Дефолтна константа за параметри на продуктите
 */
defIfNot('DEC_PARAM_PROD', '');


/**
 * class dec_Setup
 *
 * Инсталиране/Деинсталиране на
 * мениджъри свързани с декларациите за съответствия
 *
 *
 * @category  bgerp
 * @package   dec
 *
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2026 Experta EOOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class dec_Setup extends core_ProtoSetup
{
    /**
     * Версия на пакета
     */
    public $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    public $startCtr = 'dec_Declarations';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    public $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    public $info = 'Декларации за съответствие, твърденията в тях, материали и технически спецификации (TDS) на артикулите';
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'dec_Declarations',
        'dec_Statements',
        'dec_Materials',
        'dec_Tds',
        'migrate::fixDuplicateTitle2116',
        'migrate::updateStatementLang2521',
        'migrate::migrateTdsDeclarations2643',
        'migrate::migrateTdsProductData2644',
        'migrate::setTdsProductDataOn2644',
    );
    
    
    /**
     * Роли за достъп до модула
     */
    public $roles = 'dec';
    
    
    /**
     * Описание на конфигурационните константи
     */
    public $configDescription = array(
        
        'DEC_DEF_TPL_BG' => array('key(mvc=doc_TplManager,allowEmpty)', 'caption=Декларация за съответствие->Български,optionsFunc=dec_Declarations::getTemplateBgOptions'),
        'DEC_DEF_TPL_EN' => array('key(mvc=doc_TplManager,allowEmpty)', 'caption=Декларация за съответствие->Английски,optionsFunc=dec_Declarations::getTemplateEnOptions'),
        'DEC_PARAM_PROD' => array('key(mvc=cat_Params,allowEmpty)', 'caption=Параметър на продуктите'),
    
    );
    
    
    /**
     * Зареждане на данни
     */
    public function loadSetupData($itr = '')
    {
        $res = parent::loadSetupData($itr);
        
        // Ако няма посочени от потребителя сметки за синхронизация
        $config = core_Packs::getConfig('dec');
        
        // Поставяме първия намерен шаблон на български за дефолтен на Декларация за съответствие
        if (strlen($config->DEC_DEF_TPL_BG) === 0) {
            $key = key(dec_Declarations::getTemplateBgOptions());
            core_Packs::setConfig('dec', array('DEC_DEF_TPL_BG' => $key));
        }
        
        // Поставяме първия намерен шаблон на английски за дефолтен на Декларация за съответствие
        if (strlen($config->DEC_DEF_TPL_EN) === 0) {
            $key = key(dec_Declarations::getTemplateEnOptions());
            core_Packs::setConfig('dec', array('DEC_DEF_TPL_EN' => $key));
        }
        
        return $res;
    }


    /**
     * Поправка на имената на тврърденията, за да може да са еднакви
     */
    public static function fixDuplicateTitle2116()
    {
        $query = dec_Statements::getQuery();
        $query->orderBy('id', 'ASC');
        $dArr = array();
        while ($rec = $query->fetch()) {
            if (isset($dArr[$rec->title])) {
                $n = 1000;
                $i = 1;
                while (true) {
                    $newTitle = $rec->title . '_' . $i++;
                    if (!dec_Statements::fetch(array("#title = '[#1#]'", $newTitle))) {

                        $rec->title = $newTitle;

                        dec_Statements::save($rec, 'title');

                        break;
                    }

                    if (!$n--) {

                        break;
                    }
                }
            }

            $dArr[$rec->title] = $rec->id;
        }

        return cls::get('dec_Statements')->setupMvc();
    }


    /**
     * Миграция на езика на твърденията
     */
    public function updateStatementLang2521()
    {
        $query = dec_Statements::getQuery();
        $query->where("#lg IS NULL OR #lg = ''");

        while($rec = $query->fetch()){
            if (preg_match('/[\p{Cyrillic}]/u', $rec->title)) {
                $rec->lg = 'bg';
            } else {
                $rec->lg = 'en';
            }

            cls::get('dec_Statements')->save_($rec, 'lg');
        }
    }


    /**
     * Старите декларации в TDS стават твърдения (dec_Statements), а нечерновите пазят досегашния си вид
     */
    public function migrateTdsDeclarations2643()
    {
        $Tds = cls::get('dec_Tds');
        $Tds->setupMvc();
        $tbl = $Tds->dbTableName;
        $oldCol = str::phpToMysqlName('complianceDeclarations');
        if (!$Tds->db->isFieldExists($tbl, $oldCol)) {

            return;
        }

        // Твърденията се натрупват: контактът с храни включва PPWR, а то - общото качество
        $titles = array(
            'bg' => array(
                'plastic' => 'Пластмасови материали за контакт с храни',
                'paper' => 'Хартия и картон за контакт с храни',
                'ppwr' => 'Опаковки - PPWR',
                'quality' => 'Общо качество',
            ),
            'en' => array(
                'plastic' => 'Plastic materials intended for food contact',
                'paper' => 'Paper and board intended for food contact',
                'ppwr' => 'Packaging - PPWR',
                'quality' => 'General Quality',
            ),
        );

        // Производителят вече е в productData, а тук е още в старата колона
        $manufacturerCol = str::phpToMysqlName('manufacturerSnapshot');
        $manufacturerSelect = $Tds->db->isFieldExists($tbl, $manufacturerCol) ? "`{$manufacturerCol}`" : 'NULL';

        $oldValues = $manufacturers = array();
        $dbRes = $Tds->db->query("SELECT `id`, `{$oldCol}` AS `old`, {$manufacturerSelect} AS `manufacturer` FROM `{$tbl}` WHERE `{$oldCol}` IS NOT NULL AND `{$oldCol}` != ''");
        while ($dbRec = $Tds->db->fetchObject($dbRes)) {
            $oldValues[$dbRec->id] = $dbRec->old;
            $manufacturers[$dbRec->id] = $dbRec->manufacturer;
        }

        foreach ($oldValues as $id => $old) {
            $rec = $Tds->fetch($id);
            if (!$rec) {
                continue;
            }

            $lang = (!empty($rec->template) && doc_TplManager::fetchField($rec->template, 'lang') == 'bg') ? 'bg' : 'en';
            $keys = type_Set::toArray($old);

            // Активираните (и след това затворени или оттеглени) пазят досегашния вид на декларациите
            $state = ($rec->state == 'rejected') ? ($rec->brState ?? null) : $rec->state;
            if (!isset($rec->declarationSnapshot) && in_array($state, array('active', 'closed'))) {
                $manufacturer = dec_Tds::getManufacturer($lang, $manufacturers[$id] ?? null);
                $rec->declarationSnapshot = static::getOldTdsDeclarationsHtml($keys, $lang, $manufacturer);
                $Tds->save_($rec, 'declarationSnapshot');
            }

            if (!empty($rec->complianceDeclarationIds)) {
                continue;
            }

            $groups = array();
            if (isset($keys['foodContactPaper'])) {
                $groups[] = 'paper';
            }
            if (isset($keys['foodContactPlastic']) || (!countR($groups) && (isset($keys['pfasFood']) || isset($keys['bisphenols'])))) {
                $groups[] = 'plastic';
            }
            if (!countR($groups) && (isset($keys['ppwr']) || isset($keys['heavyMetals']) || isset($keys['technicalDocumentation']))) {
                $groups[] = 'ppwr';
            }
            if (!countR($groups) && (isset($keys['quality']) || isset($keys['reach']) || isset($keys['pops']))) {
                $groups[] = 'quality';
            }

            $statementIds = array();
            foreach ($groups as $group) {
                $statementId = dec_Statements::fetchField(array("#title = '[#1#]'", $titles[$lang][$group]), 'id');
                if ($statementId) {
                    $statementIds[$statementId] = $statementId;
                }
            }

            if (countR($statementIds)) {
                $rec->complianceDeclarationIds = keylist::fromArray($statementIds);
                $Tds->save_($rec, 'complianceDeclarationIds');
            }
        }
    }


    /**
     * Снимката на артикула в TDS отива в едно поле, а черновите остават без снимка; кешът на документите се чисти
     */
    public function migrateTdsProductData2644()
    {
        $Tds = cls::get('dec_Tds');
        $Tds->setupMvc();
        if (!$Tds->db->isFieldExists($Tds->dbTableName, str::phpToMysqlName('productSnapshot'))) {

            return;
        }

        $query = $Tds->getQuery();
        $query->FLD('productNameSnapshot', 'varchar(255)');
        $query->FLD('productCodeSnapshot', 'varchar(64)');
        $query->FLD('manufacturerSnapshot', 'varchar(255)');
        $query->FLD('productSnapshot', 'blob(1000000)');
        $query->where('#productData IS NULL');
        while ($rec = $query->fetch()) {

            // Черновите вече са на живо, затова кешът се чисти на всички
            if (!empty($rec->containerId)) {
                doc_DocumentCache::cacheInvalidation($rec->containerId);
            }

            // Снимка пазят само активираните (и след това затворени или оттеглени)
            $state = ($rec->state == 'rejected') ? ($rec->brState ?? null) : $rec->state;
            if (!in_array($state, array('active', 'closed')) || (empty($rec->productSnapshot) && empty($rec->productNameSnapshot))) {
                continue;
            }

            $productData = new stdClass();
            $productData->name = !empty($rec->productNameSnapshot) ? $rec->productNameSnapshot : cat_Products::getTitleById($rec->productId);
            $productData->code = !empty($rec->productCodeSnapshot) ? $rec->productCodeSnapshot : 'Art' . $rec->productId;
            $lang = (!empty($rec->template) && doc_TplManager::fetchField($rec->template, 'lang') == 'bg') ? 'bg' : 'en';
            $productData->manufacturer = dec_Tds::getManufacturer($lang, $rec->manufacturerSnapshot);
            $productData->description = $rec->productSnapshot;

            $rec->productData = $productData;
            $Tds->save_($rec, 'productData');
        }
    }


    /**
     * Датата на снимката на артикула в съществуващите TDS е датата на активирането им; кешът им се чисти
     */
    public function setTdsProductDataOn2644()
    {
        $Tds = cls::get('dec_Tds');
        $Tds->setupMvc();

        $query = $Tds->getQuery();
        $query->where('#productData IS NOT NULL AND #productDataOn IS NULL');
        $query->show('id,containerId,activatedOn,createdOn');
        while ($rec = $query->fetch()) {
            $rec->productDataOn = !empty($rec->activatedOn) ? $rec->activatedOn : $rec->createdOn;
            $Tds->save_($rec, 'productDataOn');

            // Датата се показва в документа
            if (!empty($rec->containerId)) {
                doc_DocumentCache::cacheInvalidation($rec->containerId);
            }
        }
    }


    /**
     * HTML на декларациите в TDS във вида преди твърденията
     *
     * @param array  $keys         - избраните стари ключове
     * @param string $language     - bg|en
     * @param string $manufacturer - производителят от документа
     *
     * @return string
     */
    private static function getOldTdsDeclarationsHtml($keys, $language, $manufacturer)
    {
        if (!countR($keys)) {

            return '';
        }

        $declarations = static::getOldTdsDeclarationItems($language, $manufacturer);
        $html = '';
        if (isset($keys['quality'])) {
            $html .= "<div class='tdsComplianceIntro'>" . ht::escapeAttr($declarations['quality']['text']) . '</div>';
        }

        $items = '';
        foreach ($declarations as $key => $declaration) {
            if ($key == 'quality' || !isset($keys[$key])) {
                continue;
            }

            $title = ht::escapeAttr($declaration['title']);
            $text = ht::escapeAttr($declaration['text']);
            $items .= "<li class='tdsComplianceItem'><strong>{$title}</strong><span>{$text}</span></li>";
        }

        if ($items) {
            $requestText = ($language == 'bg')
                ? 'Продуктът отговаря на приложимите изисквания, посочени по-долу. При поискване производителят издава отделни декларации за съответствие за конкретния продукт.'
                : 'The product complies with the applicable requirements listed below. Upon request, the manufacturer issues separate declarations of compliance for the specific product.';
            $html .= "<div class='tdsComplianceRequest'>" . ht::escapeAttr($requestText) . '</div>';
            $html .= "<ul class='tdsComplianceList'>{$items}</ul>";
        }

        return $html;
    }


    /**
     * Текстовете на декларациите в TDS преди твърденията
     *
     * @param string $language     - bg|en
     * @param string $manufacturer - производителят от документа
     *
     * @return array
     */
    private static function getOldTdsDeclarationItems($language, $manufacturer)
    {
        if ($language == 'bg') {
            return array(
                'quality' => array(
                    'title' => 'Фирмени стандарти и системи за управление',
                    'text' => "Продуктът е произведен по утвърдена техническа спецификация и производствена рецептура в рамките на системите за управление на {$manufacturer}: ISO 9001:2015 и ISO 14001:2015.",
                ),
                'reach' => array(
                    'title' => 'REACH / SVHC',
                    'text' => 'Регламент (ЕО) № 1907/2006 - Приложение XVII и Candidate List.',
                ),
                'pops' => array(
                    'title' => 'POPs',
                    'text' => 'Регламент (ЕС) 2019/1021 относно устойчивите органични замърсители.',
                ),
                'ppwr' => array(
                    'title' => 'PPWR',
                    'text' => 'Регламент (ЕС) 2025/40 - приложимите изисквания към датата на пускане на пазара.',
                ),
                'heavyMetals' => array(
                    'title' => 'Тежки метали',
                    'text' => 'Олово, кадмий, живак и шествалентен хром - общ лимит 100 mg/kg.',
                ),
                'technicalDocumentation' => array(
                    'title' => 'Техническа документация',
                    'text' => 'Материален състав, проследимост на суровините, декларации на доставчиците и приложими изпитвания.',
                ),
                'foodContactPlastic' => array(
                    'title' => 'Контакт с храни',
                    'text' => 'Регламенти (ЕО) № 1935/2004 и № 2023/2006; Регламент (ЕС) № 10/2011; FSSC 22000.',
                ),
                'foodContactPaper' => array(
                    'title' => 'Контакт с храни',
                    'text' => 'Регламенти (ЕО) № 1935/2004 и № 2023/2006; FSSC 22000.',
                ),
                'pfasFood' => array(
                    'title' => 'PFAS за хранителни опаковки',
                    'text' => 'Регламент (ЕС) 2025/40 - ограничения за PFAS при опаковки за контакт с храни.',
                ),
                'bisphenols' => array(
                    'title' => 'BPA и други бисфеноли',
                    'text' => 'Регламент (ЕС) 2024/3190.',
                ),
            );
        }

        return array(
            'quality' => array(
                'title' => 'Company standards and management systems',
                'text' => "The product is manufactured to an approved technical specification and production recipe under the {$manufacturer} management systems certified to ISO 9001:2015 and ISO 14001:2015.",
            ),
            'reach' => array(
                'title' => 'REACH / SVHC',
                'text' => 'Regulation (EC) No 1907/2006 - Annex XVII and the Candidate List.',
            ),
            'pops' => array(
                'title' => 'POPs',
                'text' => 'Regulation (EU) 2019/1021 on persistent organic pollutants.',
            ),
            'ppwr' => array(
                'title' => 'PPWR',
                'text' => 'Regulation (EU) 2025/40 - requirements applicable on the date the product is placed on the market.',
            ),
            'heavyMetals' => array(
                'title' => 'Heavy metals',
                'text' => 'Lead, cadmium, mercury and hexavalent chromium - combined limit of 100 mg/kg.',
            ),
            'technicalDocumentation' => array(
                'title' => 'Technical documentation',
                'text' => 'Material composition, raw-material traceability, supplier declarations and applicable testing.',
            ),
            'foodContactPlastic' => array(
                'title' => 'Food contact',
                'text' => 'Regulations (EC) No 1935/2004 and No 2023/2006; Regulation (EU) No 10/2011; FSSC 22000.',
            ),
            'foodContactPaper' => array(
                'title' => 'Food contact',
                'text' => 'Regulations (EC) No 1935/2004 and No 2023/2006; FSSC 22000.',
            ),
            'pfasFood' => array(
                'title' => 'PFAS for food packaging',
                'text' => 'Regulation (EU) 2025/40 - PFAS restrictions for food-contact packaging.',
            ),
            'bisphenols' => array(
                'title' => 'BPA and other bisphenols',
                'text' => 'Regulation (EU) 2024/3190.',
            ),
        );
    }
}
