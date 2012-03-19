<?php



/**
 * Клас 'core_Browser' - Определя параметрите на потребителския браузър
 *
 *
 * @category  all
 * @package   core
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */
class core_Browser extends core_Manager
{
    
    
    /**
     * Заглавие на мениджъра
     */
    var $title = 'Потребителски браузър';
    
    
    /**
     * Стандартния tpl_Footer извиква този екшън,
     * ако браузърът поддържа JS
     */
    function act_JS()
    {
        Mode::setPermanent('javascript', 'yes');
        Mode::setPermanent('screenWidth', Request::get('w', 'int'));
        Mode::setPermanent('screenHeight', Request::get('h', 'int'));
        Mode::setPermanent('windowWidth', Request::get('winW', 'int'));
        Mode::setPermanent('windowHeight', Request::get('winH', 'int'));
        
        $this->render1x1gif();
        
        die;
    }
    
    
    /**
     * Стандартния tpl_Footer извиква този екшън,
     * ако браузърът не поддържа JS
     */
    function act_NoJS()
    {
        Mode::setPermanent('javascript', 'no');
        $this->render1x1gif();
        
        die;
    }
    
    
    /**
     * Предизвиква затваряне на браузъра
     */
    function act_Close()
    {
        return "<script> opener.focus(); self.close (); </script>";
    }
    
    
    /**
     * Задава широк режим на екрана
     */
    function act_SetWideScreen()
    {
        Mode::setPermanent('screenMode', 'wide');
        followRetUrl();
    }
    
    
    /**
     * Задава тесен режим на екрана
     */
    function act_SetNarrowScreen()
    {
        Mode::setPermanent('screenMode', 'narrow');
        followRetUrl();
    }
    
    
    /**
     * Връща HTML кода за разпознаване параметрите на браузъра
     * В частност се разпознава дали браузърът поддържа Javascript
     */
    function renderBrowserDetectingCode_()
    {
        if (!Mode::is('javascript', 'no')) {
            $url = toUrl(array(
                    $this,
                    'noJs',
                    rand(1, 1000000000)
                ));
            $code .= '<noscript><span><img src="' . $url . '" width="1" height="1"></span></noscript>';
        }
        
        if (!Mode::is('javascript', 'yes')) {
            $url = toUrl(array(
                    $this,
                    'js',
                    rand(1, 1000000000)
                ));
            $code .= '<span><img id="brdet" src="" width="1" height="1"></span><script type="text/javascript"><!-- 
            var winW = 630, winH = 460; if (document.body && document.body.offsetWidth) { winW = document.body.offsetWidth;
            winH = document.body.offsetHeight; } if (document.compatMode=="CSS1Compat" && document.documentElement && 
            document.documentElement.offsetWidth ) { winW = document.documentElement.offsetWidth;
            winH = document.documentElement.offsetHeight; } if (window.innerWidth && window.innerHeight) {
            winW = window.innerWidth; winH = window.innerHeight;}  var brdet=document.getElementById("brdet"); 
            brdet.src="' . $url . '?w=" + screen.width + "&h=" + screen.height + "&winH=" + winH + "&winW=" + winW; 
            //--> </script>';
        }
        
        return $code;
    }
    
    
    /**
     * Изпраща към клиента едно пикселен gif
     */
    function render1x1gif()
    {
        header("Content-type:  image/gif");
        header("Expires: Wed, 11 Nov 1998 11:11:11 GMT");
        header("Cache-Control: no-cache");
        header("Cache-Control: must-revalidate");
        
        // Отпечатва бинарен код, със съдържание едно пикселен gif
        printf("%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c%c",
            71, 73, 70, 56, 57, 97, 1, 0, 1, 0, 128, 255, 0, 192, 192, 192, 0, 0, 0, 33, 249, 4, 1,
            0, 0, 0, 0, 44, 0, 0, 0, 0, 1, 0, 1, 0, 0, 2, 2, 68, 1, 0, 59);
    }
    
    
    /**
     * Проверява дали браузъра е мобилен
     */
    function detectMobile()
    {
        $useragent = $_SERVER['HTTP_USER_AGENT'];
        
        if (preg_match('/android|avantgo|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i', $useragent) || preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|e\-|e\/|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(di|rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|xda(\-|2|g)|yas\-|your|zeto|zte\-/i', substr($useragent, 0, 4))) {
            
            return TRUE;
        }
    }
}