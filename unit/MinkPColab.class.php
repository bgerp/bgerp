<?php


/**
 *  Клас  'unit_MinkPColab' - PHP тестове за колаборатор
 *
 * @category  bgerp
 * @package   tests
 * @author    Pavlinka Dainovska <pdainovska@gmail.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */

class unit_MinkPColab extends core_Manager {
     
    /**
     * Стартира последователно тестовете от MinkPColab 
     */
    //http://localhost/unit_MinkPColab/Run/
    public function act_Run()
    {
        if (!TEST_MODE) {
            return;
        }
        
        $res = '';
        $res .= "<br>".'MinkPColab';
        $res .= "  1.".$this->act_InstallColab();
        $res .= "  2.".$this->act_AddDistrAddRights();
        $res .= "  3.".$this->act_CreateAgent();
        $res .= "  4.".$this->act_AddAgentRights();
        $res .= "  5.".$this->act_LogAgent();
        $res .= "  6.".$this->act_CreateDistr();
        $res .= "  7.".$this->act_AddDistrRights();
        $res .= "  8.".$this->act_LogDistr();
        return $res;
    }
    
    /**
     * Логване
     */
    public function SetUp()
    {
        $browser = cls::get('unit_Browser');
        //$browser->start('http://localhost/');
        $host = unit_Setup::get('DEFAULT_HOST');
        $browser->start($host);
        //Потребител DEFAULT_USER (bgerp)
        $browser->click('Вход');
        $browser->setValue('nick', unit_Setup::get('DEFAULT_USER'));
        $browser->setValue('pass', unit_Setup::get('DEFAULT_USER_PASS'));
        $browser->press('Вход');
        return $browser;
    }
  
    /**
     * Избор на фирма
     */
    public function SetFirm()
    {
        $browser = $this->SetUp();
        $browser->click('Визитник');
        $browser->click('F');
        $Company = 'Фирма bgErp';
        $browser->click($Company);
        $browser->press('Папка');
        return $browser;
    }
    /**
     * 1. Инсталиране на пакета colab
     */
    //http://localhost/unit_MinkPColab/InstallColab/
    function act_InstallColab()
    {
        // Логване
        $browser = $this->SetUp();
    
        $browser->click('Админ');
        $browser->setValue('search', 'colab');
        $browser->press('Филтрирай');
        //$browser->click('Активирай');
        $browser->open('http://localhost/core_Packs/install/?pack=colab');
        //echo $browser->getHtml();
    }
    
    /**
     * 2. Права на distributor за добавяне на артикули в продажба
     */
    //http://localhost/unit_MinkPColab/AddDistrAddRights/
    function act_AddDistrAddRights()
    {
        // Логване
        $browser = $this->SetUp();
    
        $browser->click('Админ');
        $browser->setValue('search', 'sales');
        $browser->press('Филтрирай');
        $browser->click('Настройки');
        $browser->setValue('distributor', True);
        $browser->press('Запис');
        //echo $browser->getHtml();
    }
     
    /**
     * 3. Създаване на Partner-agent и потребител към него
     */
    //http://localhost/unit_MinkPColab/CreateAgent/
    function act_CreateAgent()
    {
        // Логване
        $browser = $this->SetUp();
    
        // Създаване на лице
        $browser->click('Визитник');
        $browser->click('Лица');
        $browser->press('Ново лице');
        $browser->setValue('name', 'Агент на Фирма bgErp');
        $browser->setValue('buzCompanyId', '3');
        //$browser->setValue('groupListInput[9]', True);
        $browser->setValue('Потребители', True);
        $browser->press('Запис');
        // Добавяне на потребител
        $browser->press('Потребител');
        $browser->setValue('nick', 'agent1');
        $browser->setValue('passNew', '123456');
        $browser->setValue('passRe', '123456');
        $browser->setValue('email', 'agent1@abv.bg');
        //$browser->setValue('Headquarter', False);
        //$browser->setValue('partner', True);
        $browser->setValue('roleRank', 'partner');
        $browser->refresh('Запис');
        $browser->setValue('agent', True);
        //Повтаряне на паролите,
        $browser->setValue('passNew', '123456');
        $browser->setValue('passRe', '123456');
        $browser->press('Запис');
        //return $browser->getHtml();
        
    }
    
    /**
     * 4. Даване на права до папката на фирмата и изход
     */
    //http://localhost/unit_MinkPColab/AddAgentRights/
    function act_AddAgentRights()
    {
        // Логване
        $browser = $this->SetUp();
        $browser->click('Визитник');
        $browser->click('F');
        $Company = 'Фирма bgErp';
        $browser->click($Company);
        $browser->click('Права');
        $browser->click('Свързване на партньор към папката на обекта');
        $browser->setValue('contractorId', 'Агент на Фирма bgErp');
        $browser->press('Запис');
        $browser->click('Изход');
        //Да се затвори браузъра
        
    }  
    
    /**
     * 5. Логване на agent и запитване
     */
    //http://localhost/unit_MinkPColab/LogAgent/
    function act_LogAgent()
    {
        // Логване
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
    
        //Потребител colab1
        $browser->click('Вход');
        $browser->setValue('nick', 'agent1');
        $browser->setValue('pass', '123456');
        $browser->press('Вход');
        //Запитване
        $browser->click('Теми');
        $browser->press('Запитване');
        $browser->setValue('title', 'Запитване от агент');
        $browser->setValue('inqDescription', 'Чували');
        $browser->setValue('measureId', 'брой');
        $browser->press('Запис');
      
    }
    /**
     * 6. Създаване на Partner-distributor и потребител към него
     */
    //http://localhost/unit_MinkPColab/CreateDistr/
    function act_CreateDistr()
    {
        // Логване
        $browser = $this->SetUp();
    
        // Създаване на лице
        $browser->click('Визитник');
        $browser->click('Лица');
        $browser->press('Ново лице');
        $browser->setValue('name', 'Представител на Фирма bgErp');
        $browser->setValue('buzCompanyId', '3');
        $browser->setValue('Потребители', True);
        $browser->press('Запис');
       
        // Добавяне на потребител
        $browser->press('Потребител');
        $browser->setValue('nick', 'distr1');
        $browser->setValue('passNew', '123456');
        $browser->setValue('passRe', '123456');
        $browser->setValue('email', 'distr1@abv.bg');
        //$browser->setValue('Headquarter', False);
        //$browser->setValue('partner', True);
        $browser->setValue('roleRank', 'partner');
        $browser->refresh('Запис');
        $browser->setValue('distributor', True);
        //Повтаряне на паролите,
        $browser->setValue('passNew', '123456');
        $browser->setValue('passRe', '123456');
        $browser->press('Запис');
    
    }
    
    /**
     * 7. Даване на права до папката на фирмата и изход
     */
    //http://localhost/unit_MinkPColab/AddDistrRights/
    function act_AddDistrRights()
    {
        // Логване
        $browser = $this->SetUp();
        $browser->click('Визитник');
        $browser->click('F');
        $Company = 'Фирма bgErp';
        $browser->click($Company);
        $browser->click('Права');
        $browser->click('Свързване на партньор към папката на обекта');
        $browser->setValue('contractorId', 'Представител на Фирма bgErp');
        $browser->press('Запис');
        $browser->click('Изход');
        //Да се затвори браузъра
    
    }
    
    /**
     * 8. Логване на distr, запитване и продажба
     */
    //http://localhost/unit_MinkPColab/LogDistr/
    function act_LogDistr()
    {
        // Логване
        $browser = cls::get('unit_Browser');
        $browser->start('http://localhost/');
        
        //Потребител colab1
        $browser->click('Вход');
        $browser->setValue('nick', 'distr1');
        $browser->setValue('pass', '123456');
        $browser->press('Вход');
        $browser->click('Теми');
        
        //Продажба 
        $browser->click('Теми');
        $browser->press('Продажба');
        $browser->setValue('reff', 'от distr');
        $browser->press('Запис');
        
        // Добавяне на артикул
        //За целта трябва distributor да има права за добавяне на артикули в продажба
        $browser->press('Артикул');
        $browser->setValue('productId', 'Чувал голям 50 L');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '100');
        $browser->press('Запис');
        $browser->press('Заявка');
        //return $browser->getHtml();
       
    }
    
}