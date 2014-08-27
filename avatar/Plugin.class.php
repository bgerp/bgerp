<?php





/**
 * Клас 'avatar_Plugin' -
 *
 *
 * @category  vendors
 * @package   avatar
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @todo:     Да се документира този клас
 */
class avatar_Plugin extends core_Plugin
{
    
    
    /**
     * Извиква се след описанието на модела
     */
    function on_AfterDescription(&$mvc)
    {
        if(!$mvc->fields['avatar']) {
            $mvc->FLD('avatar', 'fileman_FileType(bucket=Avatars)', 'caption=Аватар');
        }
    }
    
    
    /**
     * Извиква се преди извличането на вербална стойност за поле от запис
     */
    function on_BeforeGetVerbal($mvc, &$avatar, $rec, $field)
    {
        if($field == 'avatar') {
            $avatar = self::getImg($rec->id);
            
            return FALSE;
        }
    }
    
    
    /**
     * Извиква се след подготовката на колоните ($data->listFields)
     */
    function on_AfterPrepareListFields($mvc, $data)
    {
        $data->listFields = $this->insertAfter($data->listFields, 'id', 'avatar', 'Аватар');
    }
    
    
    /**
     * @todo Чака за документация...
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
    
    
    /**
     * Връща html <img> елемент, отговарящ на аватара на потребителя
     */
    static function getImg($userId, $email = NULL, $width = NULL)
    {
        if(!$width) {
            $width = Mode::is('screenMode', 'narrow') ? 48 : 100;
        }
        if($userId < 0) {
            // Ако става дума за системния потребител
             $imgUrl = sbf('img/100/system.png', '');
        } elseif($userId > 0) {
            // Ако се търси аватара на потребител на системата
            $userRec = core_Users::fetch($userId);
            
            if($userRec->avatar) {
                $key = md5($userId . "@/@" . EF_SALT) . "_{$width}.png";
                $attr['baseName'] = $key;
	            $img = new img_Thumb(array($userRec->avatar, $width, round($width * 1.5), 'fileman', 'isAbsolute' => FALSE, 'mode' => 'small-no-change', 'verbalName' => $key));
	            $imgUrl = $img->getUrl('forced');
            } else {
                $imgUrl = avatar_Gravatar::getUrl($userRec->email, $width);
            }
        } elseif($email = strtolower(trim($email))) {
            $imgUrl = avatar_Gravatar::getUrl($email, $width);
        }
        
        if(!$imgUrl) {
            $imgUrl = sbf('img/100/noavatar.png', '');
        }
        
        $attr['width'] = $width;
        $attr['src']   = $imgUrl;
        $attr['alt']   = '';
        unset($attr['baseName']);
        
        unset($attr['isAbsolute']);

        $img = ht::createElement('img', $attr);
        
        return $img;
    }
    
    
    /**
     * Изпълнява се след създаване на формата за добавяне/редактиране
     */
    static function on_AfterPrepareEditForm($mvc, $data)
    {
        // При добавяне на първия потребител в core_Users
        if(($mvc->className == 'core_Users') && (!$mvc->fetch('1=1'))) {
            
            // Да не се показва полето за аватар
            $data->form->setField("avatar", 'input=none');
        }
    }
}