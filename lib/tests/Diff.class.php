<?php


/**
 * Клас  'lib_tests_Diff' - Тестове за lib_Diff
 *
 * @category  ef
 * @package   lib
 *
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2013 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 * @link
 */
class lib_tests_Diff extends unit_Class
{
    /**
     * От стара и нова версия на HTML, генерира изглед с оцветветени разлики между тях
     *
     * @param old string star HTML
     * @param new string нов HTML
     */
    public static function test_getDiff($mvc)
    {
        // Масив с старите стойности
        $oldArr = array();
        
        // Масив с новите стойности
        $newArr = array();
        
        // Очаквания резултата
        $expectArr = array();
        
        // Попълваме масивите
        $oldArr[0] = '';
        $newArr[0] = 'Text текст';
        $expectArr[0] = '<span class="ins">Text текст</span>';
        
        $oldArr[1] = null;
        $newArr[1] = 'Text текст';
        $expectArr[1] = '<span class="ins">Text текст</span>';
        
        $oldArr[2] = 'Text текст';
        $newArr[2] = 'Text текст';
        $expectArr[2] = 'Text текст';
        
        $oldArr[3] = 'Text текст';
        $newArr[3] = 'Текст text';
        $expectArr[3] = '<span title="Text текст" class="cng">Текст text</span>';
        
        $oldArr[4] = 'Text текст текст3';
        $newArr[4] = 'Текст text';
        $expectArr[4] = '<span title="Text" class="cng">Текст</span><span class="del">&nbsp;текст</span>&nbsp;<span title="текст3" class="cng">text</span>';
        
        $oldArr[5] = 'Text текст';
        $newArr[5] = '';
        $expectArr[5] = '<span class="del">Text текст</span>';
        
        $oldArr[6] = 'Text текст';
        $newArr[6] = null;
        $expectArr[6] = '<span class="del">Text текст</span>';
        
        $oldArr[7] = 'Text текст';
        $newArr[7] = 'Text текст deleted';
        $expectArr[7] = 'Text текст<span class="ins">&nbsp;deleted</span>';
        
        $oldArr[8] = 'Text текст';
        $newArr[8] = 'Addeded Text текст addeded';
        $expectArr[8] = '<span class="ins">Addeded </span>Text текст<span class="ins">&nbsp;addeded</span>';
        
        $oldArr[9] = 'Deleted Text текст deleted';
        $newArr[9] = 'Text текст';
        $expectArr[9] = '<span class="del">Deleted </span>Text текст<span class="del">&nbsp;deleted</span>';
        
        $oldArr[10] = 'Deleted Text текст';
        $newArr[10] = 'Text текст addeded';
        $expectArr[10] = '<span class="del">Deleted </span>Text текст<span class="ins">&nbsp;addeded</span>';
        
        $oldArr[11] = 'Text текст deleted';
        $newArr[11] = 'Addeded Text текст';
        $expectArr[11] = '<span class="ins">Addeded </span>Text текст<span class="del">&nbsp;deleted</span>';
        
        $oldArr[12] = 'Text original текст';
        $newArr[12] = 'Text changed текст';
        $expectArr[12] = 'Text <span title="original" class="cng">changed</span>&nbsp;текст';
        
        $oldArr[13] = 'Text text';
        $newArr[13] = 'AllChanged';
        $expectArr[13] = '<span title="Text" class="cng">AllChanged</span><span class="del">&nbsp;text</span>';
        
        $oldArr[14] = 'Text text';
        $newArr[14] = 'All Changed';
        $expectArr[14] = '<span title="Text text" class="cng">All Changed</span>';
        
        $oldArr[15] = null;
        $newArr[15] = null;
        $expectArr[15] = '';
        
        $oldArr[16] = '';
        $newArr[16] = '';
        $expectArr[16] = '';
        
        // Край на масивите
        
        // Отварящ таг
        $tagOpen = '<div>';
        
        // Затварящ таг
        $tagClose = '</div>';
        
        // Обхождаме масива
        foreach ($oldArr as $key => $old) {
            
            // Вземаме резултата
            $res = lib_Diff::getDiff($oldArr[$key], $newArr[$key]);
            
            // Очакваме да е същия
            ut::expectEqual($expectArr[$key], $res);
            
            // Добавяме тагове към стойностите
            
            $oldDiv = $tagOpen . $oldArr[$key] . $tagClose;
            $newDiv = $tagOpen . $newArr[$key] . $tagClose;
            
            // Добавяме тагове към очаквания резултата
            $expectDiv = $tagOpen . $expectArr[$key] . $tagClose;
            
            // Вземаме резултата
            $resDiv = lib_Diff::getDiff($oldDiv, $newDiv);
            
            // Очакваме да са еднакви
            ut::expectEqual($expectDiv, $resDiv);
        }
    }
}
