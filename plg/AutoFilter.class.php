<?php
/**
 * Клас 'plg_AutoFilter' - Автоматизира действието на полета при филтриране, които имат атрибут autoFilter
 *
 *
 * @category  ef
 * @package   plg
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class plg_AutoFilter extends core_Plugin
{
    const EMPTY_STR = '__EMPTY__';
    
    /**
     * Преди показване на форма за добавяне/промяна.
     *
     * Поставя предишните стойности на всички autoField полета, ако има такива
     *
     * @param core_Manager $mvc
     * @param stdClass     $data
     */
    public static function on_AfterPrepareEditForm($mvc, &$res, $data)
    {
        $form = $data->form;
        $rec = $form->rec;

        $autoFields = $mvc->selectFields('autoFilter');
        
        if (!$rec->id) {
            foreach ($autoFields as $name => $field) {
                $modeName = 'lastAutoFielter_' . $name;
                if (!$rec->{$name} && ($lastValue = Mode::get($modeName))) {
                    if ($lastValue == md5(self::EMPTY_STR . Mode::getPermanentKey())) {
                        $lastValue = '';
                    }
                    $form->setDefault($name, $lastValue);
                }
            }
        }
    }

    
    /**
     * Изпълнява се след подготовката на формата за филтриране
     */
    public function on_AfterPrepareListFilter($mvc, $data)
    {
        $form = $data->listFilter;
        $rec = $form->rec;

        $autoFields = $form->selectFields('#autoFilter');
        foreach ($autoFields as $name => &$field) {
            $attr = &$field->attr;
            if (!$attr['onchange']) {
                $attr['onchange'] = 'this.form.submit();';
            }
            
            $modeName = 'lastAutoFielter_' . $name;

            if (($value = Request::get($name)) !== false) {
                $valueInt = $field->type->fromVerbal($value);
                if (!$field->type->error) {
                    if ($value == '') {
                        $value = md5(self::EMPTY_STR . Mode::getPermanentKey());
                    }
                    Mode::setPermanent($modeName, $value);
                }
            } else {
                if ($value = Mode::get($modeName)) {
                    if ($value == '') {
                        return;
                    }
                       
                    Request::push(array($name => $value));
                }
            }
        }
    }
}
