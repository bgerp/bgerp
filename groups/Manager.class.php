<?php


/**
 * Базов клас за мениджъри на групи
 *
 *
 * @category  bgerp
 * @package   groups
 *
 * @author    Stefan Stefanov <stefan.bg@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @see       https://github.com/bgerp/bgerp/issues/336
 */
class groups_Manager extends core_Manager
{
    /**
     * Описатели на допустимите разширители (екстендери) на обектите от група
     *
     * Всеки наследник може да зададе базов списък с екстендери предефинирайки $extendersArr.
     * Освен това всеки плъгин, може да допълва / изменя списъка с екстендери чрез метода
     *
     *     on_AfterGetAllowedExtenders($mvc, &$extenders)
     *
     * @var array
     */
    protected $extendersArr = array(
        
        /*
        'ключ' => array(
            'className' => ..., // Име на класа на екстендера
            'prefix'    => ..., // Префикс на екстендера, който дефинира имената на двойката
                                // методи {$className}::{$prefix}Prepare и {$className}::{$prefix}Render
            'title'     => ..., // Заглавие на екстендера, което да се използва в генерирането
                                // масива за типа 'set'
        ),
        'друг ключ' => array( ... ),
        ...
        */
    );
    
    
    /**
     * Кой може да манипулира набора от екстендери на група
     *
     * @var string
     */
    public $canExtend = 'admin';
    
    
    /**
     * Базово описание на модела; наследниците ще добавят собствени полета
     */
    public static function on_AfterDescription(groups_Manager $mvc)
    {
        $mvc->FLD('extenders', 'set', 'caption=Разширения, input=none');
    }
    
    
    /**
     * Добавя поле за избор на екстендери във формата за създ/редактиране на група
     *
     * @param groups_Manager $mvc
     * @param stdClass       $data
     */
    public static function on_AfterPrepareEditForm(groups_Manager $mvc, $data)
    {
        if (!$mvc->haveRightFor('extend', $data->rec)) {
            // Текущия потребител няма право да пипа екстендерите тази група
            return;
        }
        
        /* @var $form core_Form */
        $form = $data->form;
        $allowedExtenders = $mvc->getAllowedExtenders();
        
        if (!empty($allowedExtenders)) {
            $suggestions = array();
            foreach ($allowedExtenders as $key => $ext) {
                $suggestions[$key] = $ext['title'];
            }
            $form->getField('extenders')->type->suggestions = $suggestions;
            $form->setField('extenders', 'input');
        } else {
            $form->setField('extenders', 'input=none');
        }
    }
    
    
    /**
     * Всички допустими екстендери на обекти, които могат да бъдат в група на този мениджър
     *
     *  @return array
     */
    public function getAllowedExtenders_()
    {
        return $this->extendersArr;
    }
    
    
    public function addExtender($name, $params)
    {
        $this->extendersArr[$name] = $params;
    }
    
    
    /**
     * Обединението на екстендерите, прикачени към поне една от зададените групи
     *
     * @param array $groupIds масив от ид-та на групи
     */
    public function getExtenders($groupIds)
    {
        $extenderKeys = array();
        
        foreach ((array) $groupIds as $id) {
            $extenderKeys += type_Set::toArray(static::fetchField($id, 'extenders'));
        }
        
        $extenders = array();
        
        if (!empty($extenderKeys)) {
            $allExtenders = $this->getAllowedExtenders();
            
            foreach ($extenderKeys as $key) {
                if (isset($allExtenders[$key])) {
                    $extenders[$key] = $allExtenders[$key];
                }
            }
        }
        
        return $extenders;
    }
}
