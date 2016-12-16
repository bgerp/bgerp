<?php



/**
 * Клас 'avatar_Plugin' -
 *
 *
 * @category  vendors
 * @package   avatar
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class avatar_Plugin extends core_Plugin
{
    
    
    /**
     * Извиква се след описанието на модела
     */
    function on_AfterDescription(&$mvc)
    {
        if(!$mvc->fields['avatar']) {
            $mvc->FLD('avatar', 'fileman_FileType(bucket=Avatars)', 'caption=Лице->Аватар,after=email');
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
        $data->listFields = array('avatar' => 'Аватар') + $data->listFields;
    }
        
    
    /**
     * Връща html <img> елемент, отговарящ на аватара на потребителя
     */
    static function getImg($userId, $email = NULL, $width = NULL, $minHeight = NULL)
    {
        if (!$width) {
            $width = Mode::is('screenMode', 'narrow') ? 60 : 100;
        }
        
        $attr = array();
        
        $attr['width'] = $width;

        if (!$minHeight) {
            $minHeight = $width . 'px';
        }
        $attr['style'] = "min-height: {$minHeight}";
        
        $url = self::getUrl($userId, $email, $width);
        $attr['src']   = $url;
        
        // За случаите, когато имаме дисплей с по-висока плътност
        if(log_Browsers::isRetina()) {
            $urlX2 = self::getUrl($userId, $email, $width * 2);
            $attr['srcset']   = "{$urlX2} 2x";
        }

        $attr['alt']   = '';
        unset($attr['baseName']);
        
        unset($attr['isAbsolute']);
        
        $img = ht::createElement('img', $attr);
        
        return $img;
    }


    /**
     * Връща URL към аватара с посочените параметри
     */
    public static function  getUrl($userId, $email = NULL, $width = NULL)
    {
        if($userId < 0) {
            // Ако става дума за системния потребител
            $imgUrl = sbf('img/100/system.png', '');
        } elseif($userId > 0) {
            // Ако се търси аватара на потребител на системата
            $userRec = core_Users::fetch($userId);
            
            if($userRec->avatar) {
                $key = md5($userId . "@/@" . EF_SALT) . "_{$width}.png";
                $imgInst = new thumb_Img(array($userRec->avatar, $width, round($width * 1.5), 'fileman', 'isAbsolute' => FALSE, 'mode' => 'small-no-change', 'verbalName' => $key));
                $imgUrl = $imgInst->getUrl('forced');
            } else {
                $imgUrl = avatar_Gravatar::getUrl($userRec->email, $width);
            }
        } elseif($email = strtolower(trim($email))) {
            $imgUrl = avatar_Gravatar::getUrl($email, $width);
        }
        
        if(!$imgUrl) {
            $imgUrl = sbf('img/100/noavatar.png', '');
        }

        return $imgUrl;
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