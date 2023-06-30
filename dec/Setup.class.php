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
 * @copyright 2006 - 2015 Experta OOD
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
    public $info = 'Декларации за съответствия';
    
    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
    public $managers = array(
        'dec_Declarations',
        'dec_Statements',
        'dec_Materials',
        'migrate::fixDuplicateTitle2116',
        'migrate::updateStatementLang2521',
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
            if ($dArr[$rec->title]) {
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
}
