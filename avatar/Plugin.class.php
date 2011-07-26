<?php


/**
 * Клас 'avatar_Plugin' -
 *
 * @todo: Да се документира този клас
 *
 * @category   Experta Framework
 * @package    avatar
 * @author
 * @copyright  2006-2011 Experta OOD
 * @license    GPL 2
 * @version    CVS: $Id:$\n * @link
 * @since      v 0.1
 */
class avatar_Plugin extends core_Plugin
{
    
    
    /**
     *  Извиква се след описанието на модела
     */
    function on_AfterDescription(&$mvc)
    {
        if(!$mvc->fields['avatar']) {
            $mvc->FLD('avatar', 'fileman_FileType(bucket=Avatars)', 'caption=Аватар');
        }
    }
    
    
    /**
     *  Извиква се преди извличането на вербална стойност за поле от запис
     */
    function on_BeforeGetVerbal($mvc, &$avatar, $rec, $field)
    {
        if($field == 'avatar') {
            if($rec->avatar) {
            } else {
                $emailHash = md5(strtolower(trim($rec->email)));
                $avatar = "<img src=\"http://www.gravatar.com/avatar/{$emailHash}?d=wavatar\" />";
            }
            
            return FALSE;
        }
    }
    
    
    /**
     *  Извиква се след поготовката на колоните ($data->listFields)
     */
    function on_AfterPrepareListFields($mvc, $data)
    {
        $data->listFields = $this->insertAfter($data->listFields, 'id', 'avatar', 'Аватар');
    }
    
    
    /**
     *  @todo Чака за документация...
     */
    function insertAfter($sourceArr, $afterField, $key, $value)
    {
        foreach($sourceArr as $k => $v) {
            $destArr[$k] = $v;
            
            if($k == $afterField) {
                $destArr[$key] = $value;
            }
        }
        
        return $destArr;
    }
}