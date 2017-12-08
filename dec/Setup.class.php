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
 * @author    Gabriela Petrova <gab4eto@gmail.com>
 * @copyright 2006 - 2015 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class dec_Setup extends core_ProtoSetup
{
    
    
    /**
     * Версия на пакета
     */
    var $version = '0.1';
    
    
    /**
     * Мениджър - входна точка в пакета
     */
    var $startCtr = 'dec_Declarations';
    
    
    /**
     * Екшън - входна точка в пакета
     */
    var $startAct = 'default';
    
    
    /**
     * Описание на модула
     */
    var $info = "Декларации за съответствия";

    
    /**
     * Списък с мениджърите, които съдържа пакета
     */
   var $managers = array(
            'dec_Declarations',
			'dec_Statements',
			'dec_Materials',
        );

        
    /**
     * Роли за достъп до модула
     */
    var $roles = 'dec';

    
    /**
     * Описание на конфигурационните константи
     */
    var $configDescription = array(

        'DEC_DEF_TPL_BG'            => array('key(mvc=doc_TplManager,allowEmpty)', 'caption=Декларация за съответствие->Български,optionsFunc=dec_Declarations::getTemplateBgOptions'),
        'DEC_DEF_TPL_EN'            => array('key(mvc=doc_TplManager,allowEmpty)', 'caption=Декларация за съответствие->Английски,optionsFunc=dec_Declarations::getTemplateEnOptions'),
  
    );
    
    
    /**
     * Де-инсталиране на пакета
     */
    function deinstall()
    {
        // Изтриване на пакета от менюто
        $res = bgerp_Menu::remove($this);
        
        return $res;
    }
    
    
    /**
     * Зареждане на данни
     */
    function loadSetupData($itr = '')
    {
        $res = parent::loadSetupData($itr);
         
        // Ако няма посочени от потребителя сметки за синхронизация
        $config = core_Packs::getConfig('dec');
         
        // Поставяме първия намерен шаблон на български за дефолтен на Декларация за съответствие
        if(strlen($config->DEC_DEF_TPL_BG) === 0){
            $key = key(dec_Declarations::getTemplateBgOptions());
            core_Packs::setConfig('dec', array('DEC_DEF_TPL_BG' => $key));
        }
         
        // Поставяме първия намерен шаблон на английски за дефолтен на Декларация за съответствие
        if(strlen($config->DEC_DEF_TPL_EN) === 0){
            $key = key(dec_Declarations::getTemplateEnOptions());
            core_Packs::setConfig('dec', array('DEC_DEF_TPL_EN' => $key));
        }
        
        return $res;
    }

}