<?php

require_once 'PHPUnit/Extensions/SeleniumTestCase.php';

class SeleniumTests extends PHPUnit_Extensions_SeleniumTestCase
{
  protected function setUp()
  {
    $this->setBrowser("*chrome");
    $this->setBrowserUrl("http://change-this-to-the-site-you-are-testing/");
  }

  public function testMyTestCase()
  {
    $this->open("http://cloud.bgerp.com/Selenium/core_Users/add/?ret_url=Selenium%2Fcore_Users%2Flogin%2Fret_url%2FSelenium%252Fbgerp_Portal%252FShow");
    try {
        $this->assertFalse($this->isTextPresent("Warning"));
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
        array_push($this->verificationErrors, $e->toString());
    }
    try {
        $this->assertFalse($this->isTextPresent("Error"));
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
        array_push($this->verificationErrors, $e->toString());
    }
    try {
        $this->assertFalse($this->isTextPresent("&nbsp;"));
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
        array_push($this->verificationErrors, $e->toString());
    }
    try {
        $this->assertFalse($this->isTextPresent("Strict Standard"));
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
        array_push($this->verificationErrors, $e->toString());
    }
    // първи ПОТРЕБИТЕЛ
    $this->type("nick", "arsov");
    $this->type("email", "arsov@ep-bags.com");
    $this->type("password", "arsov");
    $this->type("names", "Стефан Арсов");
    $this->click("Cmd[save]");
    $this->waitForPageToLoad("30000");
    try {
        $this->assertFalse($this->isTextPresent("Warning"));
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
        array_push($this->verificationErrors, $e->toString());
    }
    try {
        $this->assertFalse($this->isTextPresent("Error"));
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
        array_push($this->verificationErrors, $e->toString());
    }
    try {
        $this->assertFalse($this->isTextPresent("&nbsp;"));
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
        array_push($this->verificationErrors, $e->toString());
    }
    try {
        $this->assertFalse($this->isTextPresent("Strict Standard"));
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
        array_push($this->verificationErrors, $e->toString());
    }
    $this->type("nick", "arsov");
    $this->type("password", "arsov");
    $this->click("document.forms[0].elements['Cmd[default]'][1]");
    $this->waitForPageToLoad("30000");
    try {
        $this->assertFalse($this->isTextPresent("Warning"));
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
        array_push($this->verificationErrors, $e->toString());
    }
    try {
        $this->assertFalse($this->isTextPresent("Error"));
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
        array_push($this->verificationErrors, $e->toString());
    }
    try {
        $this->assertFalse($this->isTextPresent("&nbsp;"));
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
        array_push($this->verificationErrors, $e->toString());
    }
    try {
        $this->assertFalse($this->isTextPresent("Strict Standard"));
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
        array_push($this->verificationErrors, $e->toString());
    }
    // СИСТЕМА
    // Система-АДМИНИСТРИРАНЕ
    // С-АДМИН-Пакети
    $this->click("link=Система");
    $this->waitForPageToLoad("30000");
    try {
        $this->assertFalse($this->isTextPresent("Warning"));
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
        array_push($this->verificationErrors, $e->toString());
    }
    try {
        $this->assertFalse($this->isTextPresent("Error"));
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
        array_push($this->verificationErrors, $e->toString());
    }
    try {
        $this->assertFalse($this->isTextPresent("&nbsp;"));
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
        array_push($this->verificationErrors, $e->toString());
    }
    try {
        $this->assertFalse($this->isTextPresent("Strict Standard"));
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
        array_push($this->verificationErrors, $e->toString());
    }
    $this->click("link=Ядро");
    $this->waitForPageToLoad("30000");
    try {
        $this->assertFalse($this->isTextPresent("Warning"));
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
        array_push($this->verificationErrors, $e->toString());
    }
    try {
        $this->assertFalse($this->isTextPresent("Error"));
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
        array_push($this->verificationErrors, $e->toString());
    }
    try {
        $this->assertFalse($this->isTextPresent("&nbsp;"));
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
        array_push($this->verificationErrors, $e->toString());
    }
    try {
        $this->assertFalse($this->isTextPresent("Strict Standard"));
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
        array_push($this->verificationErrors, $e->toString());
    }
    $this->click("link=Пакети");
    $this->waitForPageToLoad("30000");
    try {
        $this->assertFalse($this->isTextPresent("Warning"));
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
        array_push($this->verificationErrors, $e->toString());
    }
    try {
        $this->assertFalse($this->isTextPresent("Error"));
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
        array_push($this->verificationErrors, $e->toString());
    }
    try {
        $this->assertFalse($this->isTextPresent("&nbsp;"));
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
        array_push($this->verificationErrors, $e->toString());
    }
    try {
        $this->assertFalse($this->isTextPresent("Strict Standard"));
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
        array_push($this->verificationErrors, $e->toString());
    }
    // Пакета bgerp вече се инсталира при създаването на базата
    // <tr>
    // 	<td>select</td>
    // 	<td>pack</td>
    // 	<td>label=Компонент на приложението &quot;bgerp&quot;</td>
    // </tr>
    // <tr>
    // 	<td>clickAndWait</td>
    // 	<td>document.forms[0].elements['Cmd[default]'][1]</td>
    // 	<td></td>
    // </tr>
    // <tr>
    // 	<td>verifyTextNotPresent</td>
    // 	<td>Warning</td>
    // 	<td></td>
    // </tr>
    // <tr>
    // 	<td>verifyTextNotPresent</td>
    // 	<td>Error</td>
    // 	<td></td>
    // </tr>
    // <tr>
    // 	<td>verifyTextNotPresent</td>
    // 	<td>&amp;nbsp;</td>
    // 	<td></td>
    // </tr>
    // <tr>
    // 	<td>verifyTextNotPresent</td>
    // 	<td>Strict Standard</td>
    // 	<td></td>
    // </tr>
    // <tr>
    // 	<td>clickAndWait</td>
    // 	<td>//div[@id='maincontent']/div/div[2]/div[1]/div[1]/a/b</td>
    // 	<td></td>
    // </tr>

    // замества създаваната от docview роля every_one - ако се върне docview - да се махне
    $this->click("link=Роли");
    $this->waitForPageToLoad("30000");
    $this->click("btnAdd");
    $this->waitForPageToLoad("30000");
    $this->type("role", "every_one");
    $this->select("type", "label=Системна");
    $this->click("Cmd[save]");
    $this->waitForPageToLoad("30000");
    // Създаване и присвояване на роля no_one за достъп до всички бутони и едит форми
    $this->click("btnAdd");
    $this->waitForPageToLoad("30000");
    $this->type("role", "no_one");
    $this->select("type", "label=Системна");
    $this->click("Cmd[save]");
    $this->waitForPageToLoad("30000");
    $this->click("link=Роли");
    $this->waitForPageToLoad("30000");
    $this->click("link=2");
    $this->waitForPageToLoad("30000");
    $this->assertTrue($this->isTextPresent("34??no_one"));
    $this->click("link=Потребители");
    $this->waitForPageToLoad("30000");
    $this->click("//div[@id='maincontent']/div/div[2]/div[2]/div/div[3]/table/tbody/tr[1]/td[1]/div/div[1]/a[1]/img");
    $this->waitForPageToLoad("30000");
    $this->click("roles_34");
    $this->click("Cmd[save]");
    $this->waitForPageToLoad("30000");
    try {
        $this->assertTrue($this->isTextPresent("Headquarter, ceo, admin, no_one, user"));
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
        array_push($this->verificationErrors, $e->toString());
    }
    // За да има време новата роля да влезе в сила
    sleep(15);
    $this->click("link=Пакети");
    $this->waitForPageToLoad("30000");
    // docview липсва - прескачаме инсталацията му
    // <tr>
    // 	<td>select</td>
    // 	<td>pack</td>
    // 	<td>label=Публичен компонент &quot;docview&quot;</td>
    // </tr>
    // <tr>
    // 	<td>clickAndWait</td>
    // 	<td>document.forms[0].elements['Cmd[default]'][1]</td>
    // 	<td></td>
    // </tr>
    // <tr>
    // 	<td>verifyTextNotPresent</td>
    // 	<td>Warning</td>
    // 	<td></td>
    // </tr>
    // <tr>
    // 	<td>verifyTextNotPresent</td>
    // 	<td>Error</td>
    // 	<td></td>
    // </tr>
    // <tr>
    // 	<td>verifyTextNotPresent</td>
    // 	<td>&amp;nbsp;</td>
    // 	<td></td>
    // </tr>
    // <tr>
    // 	<td>verifyTextNotPresent</td>
    // 	<td>Strict Standard</td>
    // 	<td></td>
    // </tr>
    // <tr>
    // 	<td>clickAndWait</td>
    // 	<td>//div[@id='maincontent']/div/div[2]/div[1]/div[1]/a/b</td>
    // 	<td></td>
    // </tr>
    // <!--край docview setup
    $this->select("pack", "label=Публичен компонент \"gen\"");
    $this->click("document.forms[0].elements['Cmd[default]'][1]");
    $this->waitForPageToLoad("30000");
    try {
        $this->assertFalse($this->isTextPresent("Warning"));
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
        array_push($this->verificationErrors, $e->toString());
    }
    try {
        $this->assertFalse($this->isTextPresent("Error"));
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
        array_push($this->verificationErrors, $e->toString());
    }
    try {
        $this->assertFalse($this->isTextPresent("&nbsp;"));
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
        array_push($this->verificationErrors, $e->toString());
    }
    try {
        $this->assertFalse($this->isTextPresent("Strict Standard"));
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
        array_push($this->verificationErrors, $e->toString());
    }
    $this->click("link=Пакети");
    $this->waitForPageToLoad("30000");
    $this->select("pack", "label=Публичен компонент \"vislog\"");
    $this->click("document.forms[0].elements['Cmd[default]'][1]");
    $this->waitForPageToLoad("30000");
    try {
        $this->assertFalse($this->isTextPresent("Warning"));
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
        array_push($this->verificationErrors, $e->toString());
    }
    try {
        $this->assertFalse($this->isTextPresent("Error"));
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
        array_push($this->verificationErrors, $e->toString());
    }
    try {
        $this->assertFalse($this->isTextPresent("&nbsp;"));
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
        array_push($this->verificationErrors, $e->toString());
    }
    try {
        $this->assertFalse($this->isTextPresent("Strict Standard"));
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
        array_push($this->verificationErrors, $e->toString());
    }
    $this->click("link=Пакети");
    $this->waitForPageToLoad("30000");
    $this->select("pack", "label=Публичен компонент \"calendarpicker\"");
    $this->click("document.forms[0].elements['Cmd[default]'][1]");
    $this->waitForPageToLoad("30000");
    try {
        $this->assertFalse($this->isTextPresent("Warning"));
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
        array_push($this->verificationErrors, $e->toString());
    }
    try {
        $this->assertFalse($this->isTextPresent("Error"));
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
        array_push($this->verificationErrors, $e->toString());
    }
    try {
        $this->assertFalse($this->isTextPresent("&nbsp;"));
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
        array_push($this->verificationErrors, $e->toString());
    }
    try {
        $this->assertFalse($this->isTextPresent("Strict Standard"));
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
        array_push($this->verificationErrors, $e->toString());
    }
    $this->click("link=Пакети");
    $this->waitForPageToLoad("30000");
    $this->select("pack", "label=Публичен компонент \"sms\"");
    $this->click("document.forms[0].elements['Cmd[default]'][1]");
    $this->waitForPageToLoad("30000");
    try {
        $this->assertFalse($this->isTextPresent("Warning"));
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
        array_push($this->verificationErrors, $e->toString());
    }
    try {
        $this->assertFalse($this->isTextPresent("Error:"));
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
        array_push($this->verificationErrors, $e->toString());
    }
    try {
        $this->assertFalse($this->isTextPresent("Error!"));
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
        array_push($this->verificationErrors, $e->toString());
    }
    try {
        $this->assertFalse($this->isTextPresent("Error"));
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
        array_push($this->verificationErrors, $e->toString());
    }
    try {
        $this->assertFalse($this->isTextPresent("&nbsp;"));
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
        array_push($this->verificationErrors, $e->toString());
    }
    $this->click("link=Пакети");
    $this->waitForPageToLoad("30000");
    $this->select("pack", "label=Публичен компонент \"avatar\"");
    // Тук беше label=Публичен компонент "hclean", но пакета се инсталира вече директно от пакета bgerp
    $this->click("document.forms[0].elements['Cmd[default]'][1]");
    $this->waitForPageToLoad("30000");
    try {
        $this->assertFalse($this->isTextPresent("Warning"));
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
        array_push($this->verificationErrors, $e->toString());
    }
    try {
        $this->assertFalse($this->isTextPresent("Error"));
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
        array_push($this->verificationErrors, $e->toString());
    }
    try {
        $this->assertFalse($this->isTextPresent("&nbsp;"));
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
        array_push($this->verificationErrors, $e->toString());
    }
    try {
        $this->assertFalse($this->isTextPresent("Strict Standard"));
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
        array_push($this->verificationErrors, $e->toString());
    }
    $this->click("link=Пакети");
    $this->waitForPageToLoad("30000");
    try {
        $this->assertFalse($this->isTextPresent("Warning"));
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
        array_push($this->verificationErrors, $e->toString());
    }
    try {
        $this->assertFalse($this->isTextPresent("Error"));
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
        array_push($this->verificationErrors, $e->toString());
    }
    try {
        $this->assertFalse($this->isTextPresent("&nbsp;"));
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
        array_push($this->verificationErrors, $e->toString());
    }
    try {
        $this->assertFalse($this->isTextPresent("Strict Standard"));
    } catch (PHPUnit_Framework_AssertionFailedError $e) {
        array_push($this->verificationErrors, $e->toString());
    }

// Конфигуриране на пакет email - когато стане автоматично - да се махне!!!!
$this->click("link=Система");
$this->waitForPageToLoad("30000");
$this->click("link=Ядро");
$this->waitForPageToLoad("30000");
$this->click("link=Пакети");
$this->waitForPageToLoad("30000");
$this->click("link=2");
$this->waitForPageToLoad("30000");
$this->click("xpath=(//input[@value='Конфигуриране'])[2]");
$this->waitForPageToLoad("30000");
$this->type("name=EMAIL_MAX_FETCHING_TIME", "30");
$this->type("name=EMAIL_POP3_TIMEOUT", "2");
$this->click("xpath=(//input[@name='Cmd[default]'])[2]");
$this->waitForPageToLoad("30000");
// Конфигуриране на пакет email - КРАЙ - когато стане автоматично - да се махне!!!!
// Вярна парола за имейл акаунта по подразбиране - при нова база е ОК, но тази се създава ежедневно; Изтриване? става ДА
$this->click("link=Документи");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Имейли");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Кутии");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//a[@id='edt1']/img");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("name=password", "m1owx2m2");
$this->select("name=deleteAfterRetrieval", "label=Да");
$this->click("name=Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}

// С-АДМИН-Роли
$this->click("link=Система");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Ядро");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Роли");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("btnAdd");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("role", "Роля <FONT COLOR=RED>!redBUG!</FONT> \" &lt; &#9829; ' [#title#]");
$this->click("inherit_22");
$this->click("inherit_16");
$this->click("inherit_17");
$this->click("inherit_15");
$this->click("inherit_1");
$this->click("inherit_2");
$this->select("type", "label=Длъжност");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=2");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Роля <FONT COLOR=RED>!redBUG!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("35??Роля <FONT COLOR=RED>!redBUG!</FONT> \" &lt; &#9829; ' [#title#]blast, catpr, crm, lab, admin, user"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//a[@id='edt35']/img");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Роля <FONT COLOR=RED>!redBUG!</FONT> \" &lt; &#9829; ' [#title#]", $this->getValue("role"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Роля <FONT COLOR=RED>!redBUG!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Отказ']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Роли");
$this->waitForPageToLoad("30000");
$this->click("btnAdd");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("role", "Роля Екип <b>!ВUG!</b> \" &lt;&#9829; ' [#title#]");
// роля тип Ранг не може да се наследява (вече - липсва раздел Ранг - има го само в списъка на поле Тип:) - преди можеше и тук вместо долния ред следваше ред: ``Command: click`` и ``Target: inherit_5``
try {
$this->assertFalse($this->isTextPresent("*Ранг*Модул Екип Ранг Системна Длъжност"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("inherit_2");
$this->click("inherit_35");
$this->select("type", "label=Екип");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=2");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Роля Екип <b>!ВUG!</b> \" &lt;&#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("36??Роля Екип <b>!ВUG!</b> \" &lt;&#9829; ' [#title#]user, blast, catpr, crm, lab, admin, Роля <FONT COLOR=RED>!redBUG!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("user, blast, catpr, crm, lab, admin, Роля <FONT COLOR=RED>!redBUG!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//a[@id='edt36']/img");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Роля Екип <b>!ВUG!</b> \" &lt;&#9829; ' [#title#]", $this->getValue("role"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Роля Екип <b>!ВUG!</b> \" &lt;&#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Роля <FONT COLOR=RED>!redBUG!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Отказ']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// С-АДМИН-Потребители
$this->click("link=Потребители");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("btnAdd");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("nick", "Nik<b>!BUG!</b>\"&lt;&#9829;'[#title#]");
$this->type("password", "<b>!BUG!</b> \" &lt;&#9829; ' [#title#]");
$this->type("email", "<b>!BUG!</b>\"&lt;&#9829;'[#title#]@ep-bags.com");
$this->type("names", "Име потребител <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->click("roles_2");
$this->click("roles_35");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Ник'!"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Въвели сте недопустима стойност: nik<b>!bug!</b>\"&lt;&#9829;'[#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// имаше Двоен ескейп на лявата ъглова скоба '<' и на амперсанта '&' 
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Имейл'!"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректен имейл"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("nick", "Nik<b>!BUG!</b>");
$this->type("password", "<b>!BUG!</b> \" &lt;&#9829; ' [#title#]");
$this->type("email", "<b>!BUG!</b>@ep-bags.com");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Ник'!"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Въвели сте недопустима стойност: nik<b>!bug!</b>"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// имаше Двоен ескейп на лявата ъглова скоба '<' и на амперсанта '&' 
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Имейл'!"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректен имейл"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("nick", "Nik\"&lt;&#9829;'[#title#]");
$this->type("password", "\" &lt;&#9829; ' [#title#]");
$this->type("email", "\"&lt;&#9829;'[#title#]@ep-bags.com");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Ник'!"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Въвели сте недопустима стойност: nik\"&lt;&#9829;'[#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// Двоен ескейп на лявата ъглова скоба '<' и на амперсанта '&' 
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Имейл'!"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректен имейл"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("nick", "Test");
$this->type("password", "<b>!ВUG!</b> \" &lt;&#9829; ' [#title#]");
$this->type("email", "sarsov@ep-bags.com");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Грешки:"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Потребителя трябва да има точно една роля за ранг! Избрани са 0."));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Потребителя трябва да има поне една роля за екип!"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("password", "test");
// + роля Ранг
$this->click("id=roles_6");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Грешка:"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Потребителя трябва да има поне една роля за екип!"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("password", "test");
// + роля Екип - Екип става ОК;  преди тази роля беше с наследник Ранг и ставаха 2 роли Ранг - сега роля Ранг не може да се наследява, за това за проверка за 2оен ранг кликваме допълнително още една роля ранг с id=5
$this->click("id=roles_36");
$this->click("id=roles_5");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Потребителя трябва да има точно една роля за ранг! Избрани са 2."));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("password", "test");
// махаме единя Ранг (преди оставяхме Ранг само като наследник на друга роля - вече не може)
$this->click("id=roles_6");
$this->click("name=Cmd[save]");
$this->waitForPageToLoad("30000");

try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Ник: Test"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Име потребител <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Роля Екип <b>!ВUG!</b> \" &lt;&#9829; ' [#title#], ceo, user, Роля <FONT COLOR=RED>!redBUG!</FONT> \" &lt; &#9829; ' [#title#], blast, catpr, crm, lab, admin"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//a[@id='edt2']/img");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Име потребител <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]", $this->getValue("names"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Роля <FONT COLOR=RED>!redBUG!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Роля Екип <b>!ВUG!</b> \" &lt;&#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Отказ']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// проверка отразява ли се в потребителя промяна на наследниците на използвана роля с наследяване
$this->click("link=Роли");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=2");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//a[@id='edt35']/img");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// махаме lab и слагаме acc
$this->click("id=inherit_22");
$this->click("id=inherit_3");
$this->click("name=Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("35??Роля <FONT COLOR=RED>!redBUG!</FONT> \" &lt; &#9829; ' [#title#]acc, blast, catpr, crm, admin, userДлъжност"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("36??Роля Екип <b>!ВUG!</b> \" &lt;&#9829; ' [#title#]user, blast, catpr, crm, admin, Роля <FONT COLOR=RED>!redBUG!</FONT> \" &lt; &#9829; ' [#title#], accЕкип"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Потребители");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Име потребител <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]Ник: Test?sarsov@ep-bags.com?Роля Екип <b>!ВUG!</b> \" &lt;&#9829; ' [#title#], ceo, user, Роля <FONT COLOR=RED>!redBUG!</FONT> \" &lt; &#9829; ' [#title#], blast, catpr, crm, admin, acc"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Роля Екип <b>!ВUG!</b> \" &lt;&#9829; ' [#title#], ceo, user, Роля <FONT COLOR=RED>!redBUG!</FONT> \" &lt; &#9829; ' [#title#], blast, catpr, crm, admin, acc"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("acc"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("lab"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// роля да наследи себе си
$this->click("link=Роли");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=2");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//a[@id='edt35']/img");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("id=inherit_35");
$this->click("name=Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Не може да се наследи ролята, която редактирате: 'Роля <FONT COLOR=RED>!redBUG!</FONT> \" &lt; &#9829; ' [#title#]'"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Отказ']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// роля да наследи роля, която я наследява
$this->click("//a[@id='edt35']/img");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("id=inherit_36");
$this->click("name=Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Не може да се наследи роля, която наследява текущата роля: 'Роля Екип <b>!ВUG!</b> \" &lt;&#9829; ' [#title#]'"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Отказ']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// стандартни Потребители
$this->click("id=btnAdd");
$this->waitForPageToLoad("30000");
$this->type("name=nick", "Officer");
$this->type("name=password", "officer");
$this->type("name=email", "officer@ep-bags.com");
$this->type("name=names", "Офис Служител - Счетоводител");
$this->click("id=roles_3");
$this->click("id=roles_29");
$this->click("id=roles_26");
$this->click("id=roles_20");
$this->click("id=roles_25");
$this->click("id=roles_15");
$this->click("id=roles_4");
$this->click("id=roles_19");
$this->click("id=roles_10");
$this->click("id=roles_7");
$this->click("id=roles_2");
$this->click("name=Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertTrue($this->isTextPresent("Офис Служител - СчетоводителНик: Officer?officer@ep-bags.com"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("acc, accda, bank, budget, cash, catpr, currency, hr, Headquarter, officer, user"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}

// С-АДМИН-Интерфейси
$this->click("link=Интерфейси");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("btnAdd");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("name", "Интерфейс <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->type("title", "Заглавие интерфейс <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Интерфейс <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Заглавие интерфейс <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//a[@id='edt22']/img");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Интерфейс <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]", $this->getValue("name"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Заглавие интерфейс <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]", $this->getValue("title"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Отказ']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// С-АДМИН-Класове
$this->click("link=Класове");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("btnAdd");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("name", "Клас: <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->type("title", "Заглавие клас <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->click("interfaces_22");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
// Само за роля no_one системата вече позволява въвеждане на произволни данни като Име на клас
// <tr>
// 	<td>verifyTextNotPresent</td>
// 	<td>Warning</td>
// 	<td></td>
// </tr>
// <tr>
// 	<td>verifyTextNotPresent</td>
// 	<td>Error</td>
// 	<td></td>
// </tr>
// <tr>
// 	<td>verifyTextNotPresent</td>
// 	<td>&amp;nbsp;</td>
// 	<td></td>
// </tr>
// <tr>
// 	<td>verifyTextNotPresent</td>
// 	<td>Strict Standard</td>
// 	<td></td>
// </tr>
// <tr>
// 	<td>verifyTextPresent</td>
// 	<td>Класът не може да се зареди</td>
// 	<td></td>
// </tr>
// <tr>
// 	<td>type</td>
// 	<td>autoElement1</td>
// 	<td>cams_driver</td>
// </tr>
// <tr>
// 	<td>clickAndWait</td>
// 	<td>Cmd[save]</td>
// 	<td></td>
// </tr>
// <tr>
// 	<td>verifyTextNotPresent</td>
// 	<td>Warning</td>
// 	<td></td>
// </tr>
// <tr>
// 	<td>verifyTextNotPresent</td>
// 	<td>Error</td>
// 	<td></td>
// </tr>
// <tr>
// 	<td>verifyTextNotPresent</td>
// 	<td>&amp;nbsp;</td>
// 	<td></td>
// </tr>
// <tr>
// 	<td>verifyTextNotPresent</td>
// 	<td>Strict Standard</td>
// 	<td></td>
// </tr>
// <tr>
// 	<td>verifyTextPresent</td>
// 	<td>Класът не може да се зареди</td>
// 	<td></td>
// </tr>
// <tr>
// 	<td>type</td>
// 	<td>autoElement1</td>
// 	<td>hclean_Purifier</td>
// </tr>
// <tr>
// 	<td>clickAndWait</td>
// 	<td>Cmd[save]</td>
// 	<td></td>
// </tr>
// ...Само за роля no_one системата вече позволява въвеждане на произволни данни като Име на клас
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=2");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Клас: <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Заглавие клас <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Интерфейс <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//a[@id='edt46']/img");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Заглавие клас <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]", $this->getValue("title"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Интерфейс <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Отказ']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// С-АДМИН-Превод
$this->click("link=Превод");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("btnAdd");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("lg", "bg");
$this->type("kstring", "<b>!ВUG!</b>\"&lt;&#9829;'[#title#]");
$this->type("translated", "Превод <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("<b>!ВUG!</b>\"&lt;&#9829;'[#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Превод <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//div[@id='maincontent']/div/div[2]/div[2]/div/div[4]/table/tbody/tr[1]/td[1]/div/div[1]/a[1]/img");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("<b>!ВUG!</b>\"&lt;&#9829;'[#title#]", $this->getValue("kstring"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Превод <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]", $this->getValue("translated"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Отказ']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// С-АДМИН-Логове
$this->click("link=Логове");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error:"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Днес"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// С-АДМИН-Крон
$this->click("link=Крон");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("btnAdd");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("systemId", "Сис ID <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->type("description", "Описание лог <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->type("controller", "Контролер <FONT COLOR=RED>!redBUG!</FONT>\"&lt;&#9829; '[#title#]");
$this->type("action", "Фя<b>В</b>\"&lt;&#9829;'[#title#]");
$this->type("period1", "<b>!ВUG!</b>\"&lt;&#9829;'[#title#]");
$this->type("offset2", "<b>!ВUG!</b>\"&lt;&#9829;'[#title#]");
$this->type("delay", "<b>!ВUG!</b>\"&lt;&#9829;'[#title#]");
$this->type("timeLimit", "<b>!ВUG!</b>\"&lt;&#9829;'[#title#]");
$this->select("state", "label=Спряно");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Период (мин)'!\nНепознат формат за продължителност"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Отместване (мин)'!\nНепознат формат за продължителност"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Закъснение (s)'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Време-лимит (s)'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("autoElement1", "&#9829;");
$this->type("offset2", "<b>!ВUG!</b>");
$this->type("delay", "\"&lt;'");
$this->type("timeLimit", "[#title#]");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Период (мин)'!\nНепознат формат за продължителност"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Отместване (мин)'!\nНепознат формат за продължителност"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Закъснение (s)'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Време-лимит (s)'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("period", "");
$this->type("offset2", "");
$this->type("delay", "");
$this->type("timeLimit", "");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Сис ID <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Описание лог <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("$Контролер <FONT COLOR=RED>!redBUG!</FONT>\"&lt;&#9829; '[#title#]->Фя<b>В</b>\"&lt;&#9829;'[#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//a[@id='edt14']/img");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Сис ID <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]", $this->getValue("systemId"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Описание лог <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]", $this->getValue("description"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Контролер <FONT COLOR=RED>!redBUG!</FONT>\"&lt;&#9829; '[#title#]", $this->getValue("controller"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Фя<b>В</b>\"&lt;&#9829;'[#title#]", $this->getValue("action"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Отказ']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}

// С-АДМИН-Плъгини
$this->click("link=Плъгини");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("btnAdd");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("name", "Име Плъгин <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->type("plugin", "Плъгин <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->type("class", "Клас плъгин <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->select("state", "label=Спряно");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Плъгинът не съществува"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Класът не съществува"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("plugin", "chosen_Plugin");
$this->type("class", "type_Keylist");
$this->select("state", "label=Спряно");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Име Плъгин <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//a[@id='edt22']/img");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Име Плъгин <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]", $this->getValue("name"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Отказ']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// С-АДМИН-Кеш
$this->click("link=Кеш");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Кеширани обекти"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// С-АДМИН-Заключвания
$this->click("link=Заключвания");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Заключвания"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// край С-АДМИНИСТРИРАНЕ

// Система-ДАННИ
$this->click("link=Данни");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// С-ДАННИ-Страни
$this->click("link=Страни");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Afghanistan"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("btnAdd");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("commonName", "Държава име <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->type("formalName", "Държава пълно име <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->type("type", "Държава тип <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->type("sovereignty", "Суверенитет <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->type("capital", "Столица <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->type("currencyCode", "<a>");
$this->type("currencyName", "Валута име <b>!ВUG!</b> \" &lt;&#9829; ' [#title#]");
$this->type("telCode", "<b>");
$this->type("letterCode2", "<c>");
$this->type("letterCode3", "<d>");
$this->type("isoNumber", "<b>!ВUG!</b> \" &lt;&#9829; ' [#title#]");
$this->type("domain", "<e>");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето '3'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("autoElement1", "<b>");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето '3'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("autoElement1", "");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=14");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Държава име <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Държава пълно име <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Държава тип <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Суверенитет <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Столица <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("<a>"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Валута име <b>!ВUG!</b> \" &lt;&#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("<c>"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("<d>"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("<e>"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//div[@id='maincontent']/div/div[2]/div[2]/div/div[4]/table/tbody/tr[13]/td[1]/div/div[1]/a[1]/img");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Държава име <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]", $this->getValue("commonName"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Държава пълно име <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]", $this->getValue("formalName"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Държава тип <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]", $this->getValue("type"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Суверенитет <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]", $this->getValue("sovereignty"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Столица <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]", $this->getValue("capital"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("<a>", $this->getValue("currencyCode"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Валута име <b>!ВUG!</b> \" &lt;&#9829; ' [#title#]", $this->getValue("currencyName"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("<c>", $this->getValue("letterCode2"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("<d>", $this->getValue("letterCode3"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("<e>", $this->getValue("domain"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Отказ']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// С-ДАННИ-Домейни
$this->click("link=Домейни");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("123mail.org"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("btnAdd");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("domain", "<b>!ВUG!</b>\"&lt;&#9829;'[#title#]mail.com");
$this->select("isPublicMail", "label=По дефиниция");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=221");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("<b>!ВUG!</b>\"&lt;&#9829;'[#title#]mail.com"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// С-ДАННИ-Празници
$this->click("link=Празници");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Нова година"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("btnAdd");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("day", "Ден <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->select("base", "label=Февруари");
$this->type("year", "Година <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->type("title", "Празник Заглавие <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->select("type", "label=Празник");
$this->type("name=info", "Празник данни <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Ден'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Година'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("day", "<b>!ВUG!</b>");
$this->type("year", "<b>!ВUG!</b>");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Ден'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Година'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("day", "\"&lt;'[#title#]");
$this->type("year", "\"&lt;'[#title#]");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Ден'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Година'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("day", "&#9829;");
$this->type("year", "&#9829;");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Ден'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Година'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("day", "15");
$this->type("year", "");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=6");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Празник Заглавие <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Празник данни <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("?Мениджър?"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Данни за празниците в календара"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// С-ДАННИ-IP
$this->click("link=IP-to-Country");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("16 777 216"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("btnAdd");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("minIp", "<b>!ВUG!</b>\"&lt;&#9829;'[#title#]");
$this->type("maxIp", "<b>!bug!</b>\"&lt;&#9829;'[#title#]");
$this->type("country2", "<>");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'IP->минимално'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Ip->максимално'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("minIp", "<b>!ВUG!</b>");
$this->type("maxIp", "<b>!bug!</b>");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'IP->минимално'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Ip->максимално'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("minIp", "\"&lt;'[#title#]");
$this->type("maxIp", "\"&lt;'[#title#]");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'IP->минимално'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Ip->максимално'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("minIp", "&#9829;");
$this->type("maxIp", "&#9829;");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'IP->минимално'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Ip->максимално'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("minIp", "222");
$this->type("maxIp", "333");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//div[@id='maincontent']/div/div[2]/div[2]/div/div[5]/a[7]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("<>"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// С-ДАННИ-Тел.кодове
$this->click("link=Тел. кодове");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Quebec"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("btnAdd");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("country", "Страна име <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->type("countryCode", "<b>'</b>");
$this->type("area", "Регион име <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->type("areaCode", "<b>ВUG</b>'&lt;\"");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//div[@id='maincontent']/div/div[2]/div[2]/div/div[5]/a[7]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Страна име <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("<b>'</b>"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Регион име <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("<b>ВUG</b>'&lt;\""));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//div[@id='maincontent']/div/div[2]/div[2]/div/div[4]/table/tbody/tr[11]/td[1]/div/div[1]/a[1]/img");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Страна име <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]", $this->getValue("country"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("<b>'</b>", $this->getValue("countryCode"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Регион име <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]", $this->getValue("area"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("<b>ВUG</b>'&lt;\"", $this->getValue("areaCode"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Отказ']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// С-ДАННИ-ДДС
$this->click("link=ЗДДС №");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("btnAdd");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("vat", "ДДС<b>!ВUG!</b>\"&lt;&#9829;'[#title#]");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'VAT'!\nТекстът е над допустимите 18 символа"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("vat", "<b>!</b>\"&lt;'[##]");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("<b>!</b>\"&lt;'[##]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//div[@id='maincontent']/div/div[2]/div[2]/div/div[3]/table/tbody/tr/td[1]/div/div[1]/a[1]/img");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("<b>!</b>\"&lt;'[##]", $this->getValue("vat"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Отказ']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Проверка на VAT номер']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("vat", "<b>!В!</b>\"&lt;&#9829;'[#title#]");
$this->click("document.forms[0].elements['Cmd[default]'][1]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Това не е VAT номер - '<b>!В!</b>\"&lt;&#9829;'[#title#]'"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("'ДДСпров.!ВUG!\"<♥'"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// С-ДАННИ-МВР
$this->click("link=МВР");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Благоевград"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("btnAdd");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("city", "МВР град <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("МВР град <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//div[@id='maincontent']/div/div[2]/div[2]/div/div[3]/table/tbody/tr[12]/td[4]/div/div[1]/a[1]/img");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("МВР град <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]", $this->getValue("city"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Отказ']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// С-ДАННИ-Съдилища
$this->click("link=Съдилища");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Благоевград"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("btnAdd");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("city", "ОС град <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->type("code", "<'>");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("ОС град <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("<'>"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//div[@id='maincontent']/div/div[2]/div[2]/div/div[3]/table/tbody/tr[13]/td[5]/div/div[1]/a[1]/img");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("ОС град <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]", $this->getValue("city"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("<'>", $this->getValue("code"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Отказ']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// край СИСТЕМА

// ФИНАНСИ
$this->click("link=Финанси");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Валути");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// Ф-ВАЛУТИ-Групи
$this->click("link=Групи валути");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("btnAdd");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("name", "Група валути <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Група валути <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//a[@id='edt4']/img");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Група валути <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]", $this->getValue("name"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Отказ']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// Ф-ВАЛУТИ-Валути
$this->click("link=Списък");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//div[@id='maincontent']/div/div[2]/div[2]/div/div[4]/table/tbody/tr[2]/td[6]/div/a/img");
$this->waitForPageToLoad("30000");
$this->click("//div[@id='maincontent']/div/div[2]/div[2]/div/div[4]/table/tbody/tr[2]/td[6]/div/a/img");
$this->waitForPageToLoad("30000");
$this->click("//div[@id='maincontent']/div/div[2]/div[2]/div/div[4]/table/tbody/tr[2]/td[6]/div/a/img");
$this->waitForPageToLoad("30000");
$this->click("//div[@id='maincontent']/div/div[2]/div[2]/div/div[4]/table/tbody/tr[2]/td[6]/div/a/img");
$this->waitForPageToLoad("30000");
$this->click("//div[@id='maincontent']/div/div[2]/div[2]/div/div[4]/table/tbody/tr[2]/td[6]/div/a/img");
$this->waitForPageToLoad("30000");
$this->click("//div[@id='maincontent']/div/div[2]/div[2]/div/div[4]/table/tbody/tr[2]/td[6]/div/a/img");
$this->waitForPageToLoad("30000");
$this->click("//div[@id='maincontent']/div/div[2]/div[2]/div/div[4]/table/tbody/tr[2]/td[6]/div/a/img");
$this->waitForPageToLoad("30000");
$this->click("//div[@id='maincontent']/div/div[2]/div[2]/div/div[4]/table/tbody/tr[2]/td[6]/div/a/img");
$this->waitForPageToLoad("30000");
$this->click("//div[@id='maincontent']/div/div[2]/div[2]/div/div[4]/table/tbody/tr[2]/td[6]/div/a/img");
$this->waitForPageToLoad("30000");
$this->click("//div[@id='maincontent']/div/div[2]/div[2]/div/div[4]/table/tbody/tr[2]/td[6]/div/a/img");
$this->waitForPageToLoad("30000");
$this->click("//div[@id='maincontent']/div/div[2]/div[2]/div/div[4]/table/tbody/tr[2]/td[6]/div/a/img");
$this->waitForPageToLoad("30000");
$this->click("//div[@id='maincontent']/div/div[2]/div[2]/div/div[4]/table/tbody/tr[2]/td[6]/div/a/img");
$this->waitForPageToLoad("30000");
$this->click("//div[@id='maincontent']/div/div[2]/div[2]/div/div[4]/table/tbody/tr[2]/td[6]/div/a/img");
$this->waitForPageToLoad("30000");
$this->click("//div[@id='maincontent']/div/div[2]/div[2]/div/div[4]/table/tbody/tr[2]/td[6]/div/a/img");
$this->waitForPageToLoad("30000");
$this->click("//div[@id='maincontent']/div/div[2]/div[2]/div/div[4]/table/tbody/tr[2]/td[6]/div/a/img");
$this->waitForPageToLoad("30000");
$this->click("//div[@id='maincontent']/div/div[2]/div[2]/div/div[4]/table/tbody/tr[2]/td[6]/div/a/img");
$this->waitForPageToLoad("30000");
$this->click("//div[@id='maincontent']/div/div[2]/div[2]/div/div[4]/table/tbody/tr[2]/td[6]/div/a/img");
$this->waitForPageToLoad("30000");
$this->click("//div[@id='maincontent']/div/div[2]/div[2]/div/div[4]/table/tbody/tr[2]/td[6]/div/a/img");
$this->waitForPageToLoad("30000");
$this->click("//div[@id='maincontent']/div/div[2]/div[2]/div/div[4]/table/tbody/tr[2]/td[6]/div/a/img");
$this->waitForPageToLoad("30000");
$this->click("//div[@id='maincontent']/div/div[2]/div[2]/div/div[4]/table/tbody/tr[2]/td[6]/div/a/img");
$this->waitForPageToLoad("30000");
$this->click("//div[@id='maincontent']/div/div[2]/div[2]/div/div[4]/table/tbody/tr[2]/td[6]/div/a/img");
$this->waitForPageToLoad("30000");
$this->click("//div[@id='maincontent']/div/div[2]/div[2]/div/div[4]/table/tbody/tr[2]/td[6]/div/a/img");
$this->waitForPageToLoad("30000");
$this->click("//div[@id='maincontent']/div/div[2]/div[2]/div/div[4]/table/tbody/tr[2]/td[6]/div/a/img");
$this->waitForPageToLoad("30000");
$this->click("//div[@id='maincontent']/div/div[2]/div[2]/div/div[4]/table/tbody/tr[2]/td[6]/div/a/img");
$this->waitForPageToLoad("30000");
$this->click("//div[@id='maincontent']/div/div[2]/div[2]/div/div[4]/table/tbody/tr[2]/td[6]/div/a/img");
$this->waitForPageToLoad("30000");
$this->click("//div[@id='maincontent']/div/div[2]/div[2]/div/div[4]/table/tbody/tr[2]/td[6]/div/a/img");
$this->waitForPageToLoad("30000");
$this->click("//div[@id='maincontent']/div/div[2]/div[2]/div/div[4]/table/tbody/tr[2]/td[6]/div/a/img");
$this->waitForPageToLoad("30000");
$this->click("//div[@id='maincontent']/div/div[2]/div[2]/div/div[4]/table/tbody/tr[2]/td[6]/div/a/img");
$this->waitForPageToLoad("30000");
$this->click("//div[@id='maincontent']/div/div[2]/div[2]/div/div[4]/table/tbody/tr[2]/td[6]/div/a/img");
$this->waitForPageToLoad("30000");
$this->click("//div[@id='maincontent']/div/div[2]/div[2]/div/div[4]/table/tbody/tr[2]/td[6]/div/a/img");
$this->waitForPageToLoad("30000");
$this->click("//div[@id='maincontent']/div/div[2]/div[2]/div/div[4]/table/tbody/tr[3]/td[6]/div/a/img");
$this->waitForPageToLoad("30000");
try {
$this->assertTrue($this->isTextPresent("1??БЪЛГАРСКИ ЛЕВBGN"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("32??ЩАТСКИ ДОЛАРUSD"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("34??ЕВРОEUR"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Нова валута']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("name", "Валута <FONT COLOR=RED>!!!redBUG!!!</FONT>\"&lt;&#9829;'[#title#]");
$this->type("code", "<'>");
$this->click("groups_4");
$this->click("lists_1");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Валута <FONT COLOR=RED>!!!redBUG!!!</FONT>\"&lt;&#9829;'[#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("<'>"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//a[@id='edt35']/img");
$this->waitForPageToLoad("30000");
try {
$this->assertEquals("Валута <FONT COLOR=RED>!!!redBUG!!!</FONT>\"&lt;&#9829;'[#title#]", $this->getValue("name"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("<'>", $this->getValue("code"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Група валути <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Отказ']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// тук създавахме валута BGN, но вече е автоматично и само му добавяме група и номенклатура
$this->click("//a[@id='edt1']/img");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("БЪЛГАРСКИ ЛЕВ", $this->getValue("name"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("BGN", $this->getValue("code"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("groups_1");
$this->click("lists_1");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// край Ф-ВАЛУТИ

// Меню ПPОДУКТИ
$this->click("link=Продукти");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// Продукти-КАТАЛОГ
$this->click("link=Каталог");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// П-КАТАЛ-Продукти
$this->click("link=Списък");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// П-КАТАЛ-Мерки
$this->click("link=Мерки");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("btnAdd");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("name", "Мярка<b>В!</b>\"&lt;&#9829;'[#title#]");
$this->type("shortName", "<b>B</b>&lt;");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("btnAdd");
$this->waitForPageToLoad("30000");
$this->type("name", "Подм.<b>!В</b>\"&lt;&#9829;'[#title#]");
$this->type("shortName", "' \"[#title#]");
$this->select("baseUnitId", "label=Мярка<b>В!</b>\"&lt;&#9829;'[#title#]");
$this->type("baseUnitRatio", "Коефициент <b>!ВUG!</b> \" &lt;&#9829; ' [#title#]");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Коефициент'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("autoElement1", "<b>!ВUG!</b>");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Коефициент'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("autoElement1", "<b>b</b>");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Коефициент'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("autoElement1", "&lt;\"&#9829;'[#title#]");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Коефициент'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("autoElement1", "0.1");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Мярка<b>В!</b>\"&lt;&#9829;'[#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("<b>B</b>&lt;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Подм.<b>!В</b>\"&lt;&#9829;'[#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("' \"[#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Мярка<b>В!</b>\"&lt;&#9829;'[#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//div[@id='maincontent']/div/div[2]/div[2]/div/div[3]/table/tbody/tr[8]/td[1]/div/div[1]/a[1]/img");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Мярка<b>В!</b>\"&lt;&#9829;'[#title#]", $this->getValue("name"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("<b>B</b>&lt;", $this->getValue("shortName"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Отказ']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//div[@id='maincontent']/div/div[2]/div[2]/div/div[3]/table/tbody/tr[9]/td[1]/div/div[1]/a[1]/img");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Подм.<b>!В</b>\"&lt;&#9829;'[#title#]", $this->getValue("name"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("' \"[#title#]", $this->getValue("shortName"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Мярка<b>В!</b>\"&lt;&#9829;'[#title#] Подм.<b>!В</b>\"&lt;&#9829;'[#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Отказ']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// П-КАТАЛ-Параметри
$this->click("link=Параметри");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("btnAdd");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("name", "Парам.име<FONT COLOR=RED>!!redBUG!!</FONT>\"&lt;&#9829;'[#title#]");
$this->select("type", "label=Текст");
$this->type("suffix", "Суфикс <b>!ВUG!</b> \" &lt; &#9829; ' [#title#]");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Парам.име<FONT COLOR=RED>!!redBUG!!</FONT>\"&lt;&#9829;'[#title#] [Суфикс <b>!ВUG!</b> \" &lt; &#9829; ' [#title#]]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//div[@id='maincontent']/div/div[2]/div[2]/div/div[3]/table/tbody/tr[6]/td[1]/div/div[1]/a[1]/img");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Парам.име<FONT COLOR=RED>!!redBUG!!</FONT>\"&lt;&#9829;'[#title#]", $this->getValue("name"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Суфикс <b>!ВUG!</b> \" &lt; &#9829; ' [#title#]", $this->getValue("suffix"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Отказ']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// П-КАТАЛ-Опаковки
$this->click("link=Опаковки");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("btnAdd");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("name", "Опак.<b>В!</b>\"&lt;&#9829;'[#t#]");
$this->type("contentPlastic", "<b>!bug!</b> \" &lt;&#9829; ' [#title#] %");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Полимер'!\nГрешка при превръщане на '/9829' в число"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("autoElement1", "\" &lt; ' [#title#] %");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Опак.<b>В!</b>\"&lt;&#9829;'[#t#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//div[@id='maincontent']/div/div[2]/div[2]/div/div[3]/table/tbody/tr[7]/td[1]/div/div[1]/a[1]/img");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Опак.<b>В!</b>\"&lt;&#9829;'[#t#]", $this->getValue("name"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Отказ']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// П-КАТАЛ-Категории
$this->click("link=Категории");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("btnAdd");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("name", "Катег.име<FONT COLOR=RED>!redBUG!</FONT> \"&lt;&#9829; '[#title#]");
$this->type("info", "Категории инфо <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->click("params_6");
$this->click("packagings_7");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Катег.име<FONT COLOR=RED>!redBUG!</FONT> \"&lt;&#9829; '[#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Категории инфо <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Парам.име<FONT COLOR=RED>!!redBUG!!</FONT>\"&lt;&#9829;'[#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Опак.<b>В!</b>\"&lt;&#9829;'[#t#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//div[@id='maincontent']/div/div[2]/div[2]/div/div[3]/table/tbody/tr/td[1]/div/div[1]/a[1]/img");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Катег.име<FONT COLOR=RED>!redBUG!</FONT> \"&lt;&#9829; '[#title#]", $this->getValue("name"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Категории инфо <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]", $this->getValue("info"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Парам.име<FONT COLOR=RED>!!redBUG!!</FONT>\"&lt;&#9829;'[#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Опак.<b>В!</b>\"&lt;&#9829;'[#t#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Отказ']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// П-КАТАЛ-Групи
$this->click("link=Групи");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("btnAdd");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("name", "Група прод.<FONT COLOR=RED>!redBUG!</FONT>\"&lt;&#9829;'[#title#]");
$this->type("info", "Група продукт инфо <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Група прод.<FONT COLOR=RED>!redBUG!</FONT>\"&lt;&#9829;'[#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Група продукт инфо <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//div[@id='maincontent']/div/div[2]/div[2]/div/div[3]/table/tbody/tr/td[1]/div/div[1]/a[1]/img");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Група прод.<FONT COLOR=RED>!redBUG!</FONT>\"&lt;&#9829;'[#title#]", $this->getValue("name"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Група продукт инфо <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]", $this->getValue("info"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Отказ']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// П-КАТАЛ-Списък
$this->click("link=Списък");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("btnAdd");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("name", "Продукт наименование <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->type("code", "Продукт Код<FONT COLOR=RED>red BUG!</FONT>\"&lt;&#9829;'[#title#]");
$this->type("info", "Продукт Детайли <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->select("measureId", "label=Мярка<b>В!</b>\"&lt;&#9829;'[#title#]");
$this->click("groups_1");
$this->click("lists_32");
$this->click("lists_36");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Полето може да съдържа само букви и цифри."));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("code", "Прод.код<b>!ВUG!</b>\"&lt;&#9829;'[#title#]");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Полето може да съдържа само букви и цифри."));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("code", "<b>!ВUG!</b>");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Полето може да съдържа само букви и цифри."));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("code", "\"&lt;&#9829;'[#title#]");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Полето може да съдържа само букви и цифри."));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("code", "Продукт код 1000");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Полето може да съдържа само букви и цифри."));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("code", "ПродуктКод1000");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Списък");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Продукт наименование <FONT CO ... &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Продукт Детайли <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("ПродуктКод1000"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Катег.име<FONT COLOR=RED>!redBUG!</FONT> \"&lt;&#9829; '[#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Група прод.<FONT COLOR=RED>!redBUG!</FONT>\"&lt;&#9829;'[#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// Автоинкрементиране - тест след Запис и Запис и Нов
$this->click("id=btnAdd");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("ПродуктКод1001", $this->getValue("code"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("name=name", "Продукт тест за АВТОИНКРЕМЕНТИРАНЕ на \"Код:\" - трябва да е \"ПродуктКод1001\"");
$this->click("name=Cmd[save_n_new]");
$this->waitForPageToLoad("30000");
try {
$this->assertTrue($this->isTextPresent("Последно добавенo: Наименование: Продукт тест за АВТОИНКРЕМЕНТИРАНЕ на \"Код:\" - трябва да е \"ПродуктКод1001\"\nКод: ПродуктКод1001\nКатегория: 1"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("ПродуктКод1002", $this->getValue("code"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("name=name", "Продукт тест за АВТОИНКРЕМЕНТИРАНЕ на \"Код:\" - трябва да е \"ПродуктКод1002\"");
$this->click("name=Cmd[save]");
$this->waitForPageToLoad("30000");
$this->click("link=Списък");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Продукт тест за АВТОИНКРЕМЕНТИРА ... \" - трябва да е \"ПродуктКод1001\""));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("ПродуктКод1001Катег.име<FONT COLOR=RED>!redBUG!</FONT> \"&lt;&#9829; '[#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Продукт тест за АВТОИНКРЕМЕНТИРА ... \" - трябва да е \"ПродуктКод1002\""));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("ПродуктКод1002Катег.име<FONT COLOR=RED>!redBUG!</FONT> \"&lt;&#9829; '[#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// Автоинкрементиране - ОК!
$this->click("//tr[@id='lr_1']/td[6]/div/div[1]/a[1]/img");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Продукт наименование <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]", $this->getValue("name"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("ПродуктКод1000", $this->getValue("code"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Продукт Детайли <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]", $this->getValue("info"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Мярка<b>В!</b>\"&lt;&#9829;'[#title#] Подм.<b>!В</b>\"&lt;&#9829;'[#title#] брой грам килограм километър метър тон хиляди бройки"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Катег.име<FONT COLOR=RED>!redBUG!</FONT> \"&lt;&#9829; '[#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Група прод.<FONT COLOR=RED>!redBUG!</FONT>\"&lt;&#9829;'[#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Отказ']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Продукт наименование <FONT CO ... &lt; &#9829; ' [#title#]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//div[@id='maincontent']/div/div[2]/div[2]/div[2]/fieldset/legend/a/img");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Продукт наименование <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Парам.име<FONT COLOR=RED>!!redBUG!!</FONT>\"&lt;&#9829;'[#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("value_6", "Ст-ст парам.<FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
try {
$this->assertTrue($this->isTextPresent("Суфикс <b>!ВUG!</b> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//div[@id='maincontent']/div/div[2]/div[2]/div[2]/fieldset/legend/a/img");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Продукт наименование <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Параметри"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Парам.име<FONT COLOR=RED>!!redBUG!!</FONT>\"&lt;&#9829;'[#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Ст-ст парам.<FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]", $this->getValue("value_6"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Суфикс <b>!ВUG!</b> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Отказ']");
$this->waitForPageToLoad("30000");
$this->click("btnAdd");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Добавяне към \"Продукт наименование <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]\""));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->assertTrue($this->isTextPresent("Опак.<b>В!</b>\"&lt;&#9829;'[#t#]"));
// ОК! оправено! беше Опак.<b>В!</b>"&lt;&#9829;'&#91;#t#]  След ремонта на двойното ескейпване когато даде грешка и забие тук - да се замени с Опак.<b>В!</b>"&lt;&#9829;'[#t#]


$this->type("quantity", "Опак.Колич.<b>!ВUG!</b> \" &lt;&#9829; ' [#title#]");
$this->type("netWeight", "Тегло Нето <b>!ВUG!</b> \" &lt;&#9829; ' [#title#]");
$this->type("tareWeight", "Тегло тара <b>!ВUG!</b> \" &lt;&#9829; ' [#title#]");
$this->type("sizeWidth", "Габарит Ширина <b>!ВUG!</b> \" &lt;&#9829; ' [#title#]");
$this->type("sizeHeight", "Габарит Височина <b>!ВUG!</b> \" &lt;&#9829; ' [#title#]");
$this->type("sizeDepth", "Габарит Дълбочина <b>!ВUG!</b> \" &lt;&#9829; ' [#title#]");
$this->type("eanCode", "EAN\"<b>В</b>'");
$this->type("customCode", "Друг код <b>!ВUG!</b> \" &lt;&#9829; ' [#title#]");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Количество'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Тегло->Нето'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Тегло->Тара'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Габарит->Ширина'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Габарит->Височина'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Габарит->Дълбочина'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Идентификация->EAN код'!\nНевалиден EAN13 номер. Полето приема само цифри."));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("netWeight", "\"&lt;&#9829;'[#title#]");
$this->type("autoElement1", "\"&lt;&#9829;'[#title#]");
$this->type("tareWeight", "\"&lt;&#9829;'[#title#]");
$this->type("sizeWidth", "\"&lt;&#9829;'[#title#]");
$this->type("sizeHeight", "\"&lt;&#9829;'[#title#]");
$this->type("sizeDepth", "\"&lt;&#9829;'[#title#]");
$this->type("eanCode", "\"&lt;&#9829;'[#title#]");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Количество'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Тегло->Нето'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Тегло->Тара'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Габарит->Ширина'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Габарит->Височина'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Габарит->Дълбочина'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Идентификация->EAN код'!\nНевалиден EAN13 номер. Полето приема само цифри."));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("netWeight", "&#9829;");
$this->type("autoElement1", "&#9829;");
$this->type("tareWeight", "&#9829;");
$this->type("sizeWidth", "&#9829;");
$this->type("sizeHeight", "&#9829;");
$this->type("sizeDepth", "&#9829;");
$this->type("eanCode", "EAN<b>В!</b>");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Количество'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Тегло->Нето'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Тегло->Тара'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Габарит->Ширина'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Габарит->Височина'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Габарит->Дълбочина'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Идентификация->EAN код'!\nНевалиден EAN13 номер. Полето приема само цифри."));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("autoElement1", "100");
$this->type("netWeight", "100");
$this->type("tareWeight", "100");
$this->type("sizeWidth", "100");
$this->type("sizeHeight", "100");
$this->type("sizeDepth", "100");
$this->type("eanCode", "");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Опак.<b>В!</b>\"&lt;&#9829;'[#t#]100,0000100,0000100,0000100,0000100,0000100,0000Друг код <b>!ВUG!</b> \" &lt;&#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//div[@id='maincontent']/div/div[2]/div[2]/div[2]/div[1]/div[2]/div/div[1]/table/tbody/tr/td[1]/div/div[1]/a[1]/img");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Редактиране в \"Продукт наименование <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]\""));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Опак.<b>В!</b>\"&lt;&#9829;'[#t#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// ОК! оправено! беше assertTextPresent - Опак.<b>В!</b>"&lt;&#9829;'&#91;#t#]     След ремонта на двойното ескейпване когато даде грешка и забие тук - горния ред да се махне и остане само по-горния
try {
$this->assertEquals("Друг код <b>!ВUG!</b> \" &lt;&#9829; ' [#title#]", $this->getValue("customCode"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Отказ']");
$this->waitForPageToLoad("30000");
// проверка-бутон Нова опаковка трябва да не е наличен - избрана е само една опаковка в Категории и тя вече е използвана
try {
$this->assertFalse($this->isElementPresent("btnAdd"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// добавяме опаковки в Категорията, за да тестваме добавянето само на неизползваните до момента опаковки
$this->click("link=Категории");
$this->waitForPageToLoad("30000");
$this->click("//a[@id='edt1']/img");
$this->waitForPageToLoad("30000");
$this->click("id=packagings_2");
$this->click("id=packagings_3");
$this->click("name=Cmd[save]");
$this->waitForPageToLoad("30000");
$this->click("link=Списък");
$this->waitForPageToLoad("30000");
$this->click("link=Продукт наименование <FONT CO ... &lt; &#9829; ' [#title#]");
$this->waitForPageToLoad("30000");
// проверка-бутон Нова опаковка вече трябва да Е наличен - избрани са повече опаковки в Категории и е използвана само една
try {
$this->assertTrue($this->isElementPresent("btnAdd"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// проверка дали позволява добавянето само на неизползваните до сега от дефинираните за Категорията опаковки
$this->click("id=btnAdd");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("exact:Ролка Кашон"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Опак.<b>В!</b>\"&lt;&#9829;'[#t#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Опак.<b>В!</b>\"&lt;&#9829;'&#91;#t#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// След ремонта на двойното ескейпване горния ред може да се изтрие.
$this->select("name=packagingId", "label=Кашон");
$this->type("name=quantity", "20");
$this->type("name=netWeight", "20");
$this->type("name=tareWeight", "20");
$this->type("name=sizeWidth", "20");
$this->type("name=sizeHeight", "20");
$this->type("name=sizeDepth", "20");
$this->type("name=customCode", "Др. код <b>!ВUG!</b> \" &lt;&#9829; ' [#title#]");
$this->click("name=Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Кашон20,000020,000020,000020,000020,000020,0000Др. код <b>!ВUG!</b> \" &lt;&#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// проверка-при редактиране трябва да предлага само редактираната + свободните все още опаковки
$this->click("//a[@id='edt1']/img");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Редактиране в \"Продукт наименование <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]\""));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("exact:Ролка Опак.<b>В!</b>\"&lt;&#9829;'[#t#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Друг код <b>!ВUG!</b> \" &lt;&#9829; ' [#title#]", $this->getValue("customCode"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Отказ']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Нов Файл']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Файл към Продукт наименование <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// Прескочи ъплоудването на файл - Селениум не работи с нов прозорец
// <tr>
// 	<td>click</td>
// 	<td>//input[@value='+']</td>
// 	<td></td>
// </tr>
// <tr>
// 	<td>verifyTextNotPresent</td>
// 	<td>Warning</td>
// 	<td></td>
// </tr>
// <tr>
// 	<td>verifyTextNotPresent</td>
// 	<td>Error</td>
// 	<td></td>
// </tr>
// <tr>
// 	<td>verifyTextNotPresent</td>
// 	<td>&amp;nbsp;</td>
// 	<td></td>
// </tr>
// <tr>
// 	<td>verifyTextNotPresent</td>
// 	<td>Strict Standard</td>
// 	<td></td>
// </tr>
// <tr>
// 	<td>waitForPopUp</td>
// 	<td>addFileDialog</td>
// 	<td>30000</td>
// </tr>
// <tr>
// 	<td>selectWindow</td>
// 	<td>name=addFileDialog</td>
// 	<td></td>
// </tr>
// <tr>
// 	<td>type</td>
// 	<td>ulfile</td>
// 	<td>C:\Users\Gabi\Desktop\Primerna dokumentacia\Snimki za cloud-demo\FONT COLOR=RED!!! red BUG !!!FONT &amp;lt; &amp;&#35;9829;.jpg</td>
// </tr>
// <tr>
// 	<td>clickAndWait</td>
// 	<td>Upload</td>
// 	<td></td>
// </tr>
// <tr>
// 	<td>verifyTextNotPresent</td>
// 	<td>Warning</td>
// 	<td></td>
// </tr>
// <tr>
// 	<td>verifyTextNotPresent</td>
// 	<td>Error</td>
// 	<td></td>
// </tr>
// <tr>
// 	<td>verifyTextNotPresent</td>
// 	<td>&amp;nbsp;</td>
// 	<td></td>
// </tr>
// <tr>
// 	<td>verifyTextNotPresent</td>
// 	<td>Strict Standard</td>
// 	<td></td>
// </tr>
// <tr>
// 	<td>selectWindow</td>
// 	<td>null</td>
// 	<td></td>
// </tr>
// Прескочи до тук
$this->type("description", "Файл Описание <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//div[@id='maincontent']/div/div[2]/div[2]/div[2]/div[2]/div[2]/div/div[1]/table/tbody/tr/td[1]/div/div[1]/a[1]/img");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Файл към Продукт наименование <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// Прескочи проверката на името на ъплоудвания файл - Селениум не работи с нов прозорец
// <tr>
// 	<td>verifyTextPresent</td>
// 	<td>FONT_COLOR_RED_red_BUG_FONT_lt_9829</td>
// 	<td></td>
// </tr>
// Прескочи до тук
try {
$this->assertEquals("Файл Описание <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]", $this->getValue("description"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Отказ']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Списък");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Продукт наименование <FONT CO ... &lt; &#9829; ' [#title#]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Продукт Продукт наименование <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// Двоен ескейп на лява квадратна скоба
try {
$this->assertTrue($this->isTextPresent("НаименованиеПродукт наименование <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Продукт Детайли <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Мярка<b>В!</b>\"&lt;&#9829;'[#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Катег.име<FONT COLOR=RED>!redBUG!</FONT> \"&lt;&#9829; '[#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Група прод.<FONT COLOR=RED>!redBUG!</FONT>\"&lt;&#9829;'[#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Парам.име<FONT COLOR=RED>!!redBUG!!</FONT>\"&lt;&#9829;'[#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Ст-ст парам.<FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Суфикс <b>!ВUG!</b> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Опак.<b>В!</b>\"&lt;&#9829;'[#t#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Друг код <b>!ВUG!</b> \" &lt;&#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// Прескочи проверката на името на ъплоудвания файл - Селениум не работи с нов прозорец
// <tr>
// 	<td>verifyTextPresent</td>
// 	<td>FONT_COLOR_RED_red_BUG_FONT_lt_9829</td>
// 	<td></td>
// </tr>
// Прескочи до тук
try {
$this->assertTrue($this->isTextPresent("Файл Описание <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Списък");
$this->waitForPageToLoad("30000");
// край Прод.-КАТАЛОГ

// Продукти-ЦЕНИ
$this->click("link=Продукти");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Цени");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// П-ЦЕНИ-Групи
$this->click("link=Групи продукти");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("btnAdd");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("name", "Ценова група продукти <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->type("baseDiscount", "<b>В</b>\"&lt;&#9829;'[#title#] %");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Базова Отстъпка'!\nГрешка при превръщане на '/9829' в число"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("autoElement1", "&lt;&#9829; %");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Ценова група продукти <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("9 829,00 %"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//div[@id='maincontent']/div/div[2]/div[2]/div/div[3]/table/tbody/tr/td[1]/div/div[1]/a[1]/img");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Ценова група продукти <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]", $this->getValue("name"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("9829 %", $this->getValue("baseDiscount"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("baseDiscount", "&lt; %");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Ценова група продукти <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("< %"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//div[@id='maincontent']/div/div[2]/div[2]/div/div[3]/table/tbody/tr/td[1]/div/div[1]/a[1]/img");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("baseDiscount", "20 %");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Ценова група продукти <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("20,00 %"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// П-ЦЕНИ-Себестойност
$this->click("link=Себестойност");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("btnAdd");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->select("productId", "label=Продукт наименование <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->select("priceGroupId", "label=Ценова група продукти <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->type("cost", "<FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->type("autoElement1", "01-04-2036");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Себестойност'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("autoElement1", "<b>!ВUG!</b>\"&lt;&#9829;'[#title#]");
$this->type("id=autoElement2", "01-04-2036");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Себестойност'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("autoElement1", "<b>!ВUG!</b>");
$this->type("id=autoElement2", "01-04-2036");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Себестойност'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("autoElement1", "\"&lt;&#9829;'");
$this->type("id=autoElement2", "01-04-2036");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Себестойност'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("autoElement1", "&#9829;");
$this->type("id=autoElement2", "01-04-2036");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Себестойност'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("autoElement1", "&lt;");
$this->type("id=autoElement2", "01-04-2036");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Себестойност'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("autoElement1", "100");
$this->type("id=autoElement2", "01-04-2036");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Всички Продукти Продукт наименование <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Продукт наименование <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("20,00 %(Ценова група продукти <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#])"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//form[@id='autoElement3']/table/tbody/tr[1]/td/div/div/table/tbody/tr[1]/td[7]/div/div[1]/a[1]/img");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Продукт наименование <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Ценова група продукти <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Отказ']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// П-ЦЕНИ-Клиенти
$this->click("link=Класове клиенти");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("btnAdd");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("name", "Пакет отстъпки <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->type("value_1", "<b>!В!</b>\"&lt;&#9829;'[#title#]");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Отстъпки->Ценова група продукти <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]'!\nГрешка при превръщане на '/9829' в число"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("autoElement1", "<b>!В!</b>\"[#title#]");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Отстъпки->Ценова група продукти <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]'!\nГрешка при превръщане на '/' в число"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("autoElement1", "&lt;&#9829;'");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Пакет отстъпки <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]№1"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("91;#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("НаименованиеПакет отстъпки <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("!!! red BUG !!! *"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Ценова група продукти <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("9 829,00 %"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("btnEdit");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Редактиране на пакет \"Пакет отстъпки <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]\""));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Пакет отстъпки <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]", $this->getValue("name"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Ценова група продукти <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]:"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("value_1", "5 %");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Класове клиенти");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Пакет отстъпки <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// П-ЦЕНИ-Ценоразписи
$this->click("link=Ценоразписи");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("btnAdd");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("autoElement1", "<b>!ВUG!</b>\"&lt;&#9829;'[#title#]");
$this->select("discountId", "label=Пакет отстъпки <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->select("currencyId", "label=Валута <FONT COLOR=RED>!!!redBUG!!!</FONT>\"&lt;&#9829;'[#title#]");
$this->type("vat", "<b>В</b>\"&lt;&#9829;'[#title#]");
$this->click("groups_1");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Към Дата'!\nНе е в допустимите формати, като например:"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'ДДС'!\nГрешка при превръщане на '/9829' в число"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("autoElement1", "25-04-2036");
$this->type("vat", "<b>В</b>\"[#title#]");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'ДДС'!\nГрешка при превръщане на '/' в число"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("autoElement2", "&lt;&#9829;'");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Пакет отстъпки <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Валута <FONT COLOR=RED>!!!redBUG!!!</FONT>\"&lt;&#9829;'[#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("9 829,00 %"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Група прод.<FONT COLOR=RED>!redBUG!</FONT>\"&lt;&#9829;'[#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Ценова група продукти <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Продукт наименование <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//div[@id='maincontent']/div/div[2]/div[2]/div[2]/div/div[1]/table/tbody/tr/td[4]/div/div[1]/a[1]/img");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Ценова група продукти <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Продукт наименование <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Отказ']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("btnEdit");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Пакет отстъпки <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("БЪЛГАРСКИ ЛЕВ Валута <FONT COLOR=RED>!!!redBUG!!!</FONT>\"&lt;&#9829;'[#title#] ЕВРО ЩАТСКИ ДОЛАР"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("9829 %", $this->getValue("vat"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("vat", "20 %");
try {
$this->assertTrue($this->isTextPresent("Група прод.<FONT COLOR=RED>!redBUG!</FONT>\"&lt;&#9829;'[#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Ценоразписи");
$this->waitForPageToLoad("30000");
try {
$this->assertTrue($this->isTextPresent("Пакет отстъпки <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Валута <FONT COLOR=RED>!!!redBUG!!!</FONT>\"&lt;&#9829;'[#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// край ПРОДУКТИ

// ВИЗИТНИК (crm)
$this->click("link=Визитник");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Контакти");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// crm Локации
$this->click("link=Локации");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Локации на контрагенти"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// Беше наличен бутон Нов запис (Issue #175) - коригиран - няма да може да се добавят локации от тук

// crm Групи
$this->click("link=Групи");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("btnAdd");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("name", "Визитник група <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->type("info", "Описание на групата във визитника <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Визитник група <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Описание на групата във визитника <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//div[@id='maincontent']/div/div[2]/div[2]/div/div[3]/table/tbody/tr[6]/td[1]/div/div[1]/a[1]/img");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Визитник група <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]", $this->getValue("name"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Описание на групата във визитника <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]", $this->getValue("info"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Отказ']");
$this->waitForPageToLoad("30000");
// crm Лица
$this->click("link=Лица");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("btnAdd");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->select("salutation", "label=Г-н");
$this->type("name", "Лице Имена <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->type("egn", "ЕГН<b>В</b>\"&lt;&#9829;'[#title#]");
$this->select("birthday[]", "label=17");
$this->select("//form[@id='crm_Persons-EditForm']/table/tbody/tr[2]/td/div/table/tbody/tr[4]/td[2]/span/select[2]", "label=Февруари");
$this->select("//form[@id='crm_Persons-EditForm']/table/tbody/tr[2]/td/div/table/tbody/tr[4]/td[2]/span/select[3]", "label=1980");
$this->select("country", "label=Bulgaria");
$this->type("pCode", "<b>K</b>'[#&lt#]");
$this->type("place", "Лице Град<FONT COLOR=RED>!red BUG!</FONT> \"&lt;&#9829;'[#title#]");
$this->type("address", "Лице Адрес <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->type("buzEmail", "sluzh<b>!ВUG!</b>\"&lt;&#9829;'[#title#]@mail.com");
$this->type("buzTel", "Лице Служ. Тел.<b>!ВUG!</b> \" &lt;&#9829; ' [#title#]");
$this->type("buzFax", "Лице Служ. Факс <b>!ВUG!</b> \" &lt;&#9829; ' [#title#]");
$this->type("buzAddress", "Лице Служ. Адрес <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->type("email", "lichen<b>!ВUG!</b>\"&lt;&#9829;'[#title#]@mail.com");
$this->type("tel", "Лице Лич. Тел. <b>!ВUG!</b> \" &lt;&#9829; ' [#title#]");
$this->type("mobile", "Лице Лич. Моб. <b>!ВUG!</b> \" &lt;&#9829; ' [#title#]");
$this->type("fax", "Лице Лич. Факс <b>!ВUG!</b> \" &lt;&#9829; ' [#title#]");
$this->type("website", "www.<b>!ВUG!</b>\"&lt;&#9829;'[#title#].www");
$this->type("autoElement1", "Лице Бележки <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->type("idCardNumber", "<b>!В!</b>'&lt;\"");
$this->type("autoElement2", "<b>!ВUG!</b>\"&lt;&#9829;'[#title#]");
$this->type("autoElement3", "<b>!ВUG!</b>\"&lt;&#9829;'[#title#]");
$this->type("idCardIssuedBy4", "МВР<FONT COLOR=RED>!!red BUG!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->click("groupList_6");
$this->click("id=lists_7");
$this->click("id=lists_10");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'ЕГН'!\nПолето трябва да съдържа 10 цифри."));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Служебни комуникации->Имейл'!\nНекоректен имейл"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Лични комуникации->Имейли'!"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Лични комуникации->Сайт/Блог'!\nНевалидно URL."));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Лична карта->Издадена на'!\nНе е в допустимите формати, като например:"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Лична карта->Валидна до'!\nНе е в допустимите формати, като например:"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("autoElement1", "&lt;&#9829;\"");
$this->type("buzEmail", "&lt;&#9829;\"@mail.com");
$this->type("email", "&lt;&#9829;\"@mail.com");
$this->type("website", "www.&lt;&#9829;\".www");
$this->type("autoElement4", "15-05-2005");
$this->type("autoElement5", "15-05-2015");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'ЕГН'!\nПолето трябва да съдържа 10 цифри."));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Служебни комуникации->Имейл'!\nНекоректен имейл"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Лични комуникации->Имейли'!"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Лични комуникации->Сайт/Блог'!\nНевалидно URL."));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("autoElement1", "<b>В!</b>'");
$this->type("buzEmail", "<b>!ВUG!</b>'@mail.com");
$this->type("email", "<b>!ВUG!</b>'@mail.com");
$this->type("website", "www.<b>!ВUG!</b>'.www");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'ЕГН'!\nПолето трябва да съдържа 10 цифри."));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Служебни комуникации->Имейл'!\nНекоректен имейл"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Лични комуникации->Сайт/Блог'!\nНевалидно URL."));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("autoElement1", "");
$this->type("buzEmail", "noBug@mail.com");
$this->type("website", "www.noBug.www");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Лични комуникации->Имейли'!\nСтойността не е валиден имейл:"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("email", "lichen.'no?Bug@mail.com");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Лице Имена <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#], Bulgaria  17-02-1980"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("<b>K</b>'[#&lt#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Лице Град<Font Color=Red>!red Bug!</font> \"&lt;&#9829;'[#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Лице Адрес <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Лице Лич. Моб. <b>!ВUG!</b> \" &lt;&#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Лице Лич. Тел. <b>!ВUG!</b> \" &lt;&#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Лице Лич. Факс <b>!ВUG!</b> \" &lt;&#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("lichen.'no?Bug@mail.com"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Визитник група <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Лице Служ. Тел.<b>!ВUG!</b> \" &lt;&#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Лице Служ. Факс <b>!ВUG!</b> \" &lt;&#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Лице Служ. Адрес <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("<b>!В!</b>'&lt;\""));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("МВР<FONT COLOR=RED>!!red BUG!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Лице Бележки <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Лица");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Лице Имена <FONT COLOR=RED>!! ... &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Лице Лич. Моб. <b>!ВUG!</b> \" &lt;&#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Лице Лич. Тел. <b>!ВUG!</b> \" &lt;&#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Лице Лич. Факс <b>!ВUG!</b> \" &lt;&#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("lichen.'no?Bug@mail.com"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("<b>K</b>'[#&lt#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Лице Град<Font Color=Red>!red Bug!</font> \"&lt;&#9829;'[#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Лице Адрес <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//tr[@id='lr_1']/td[6]/div/div[1]/a[1]/img");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Лице Имена <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]", $this->getValue("name"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("<b>K</b>'[#&lt#]", $this->getValue("pCode1"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Лице Град<Font Color=Red>!red Bug!</font> \"&lt;&#9829;'[#title#]", $this->getValue("place"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Лице Адрес <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]", $this->getValue("address"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("noBug@mail.com", $this->getValue("buzEmail"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Лице Служ. Тел.<b>!ВUG!</b> \" &lt;&#9829; ' [#title#]", $this->getValue("buzTel"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Лице Служ. Факс <b>!ВUG!</b> \" &lt;&#9829; ' [#title#]", $this->getValue("buzFax"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Лице Служ. Адрес <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]", $this->getValue("buzAddress"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue((bool)preg_match("/^lichen\.'no[\s\S]Bug@mail\.com$/",$this->getValue("email")));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Лице Лич. Тел. <b>!ВUG!</b> \" &lt;&#9829; ' [#title#]", $this->getValue("tel"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Лице Лич. Моб. <b>!ВUG!</b> \" &lt;&#9829; ' [#title#]", $this->getValue("mobile"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Лице Лич. Факс <b>!ВUG!</b> \" &lt;&#9829; ' [#title#]", $this->getValue("fax"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Лице Бележки <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]", $this->getValue("autoElement2"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("<b>!В!</b>'&lt;\"", $this->getValue("idCardNumber"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("МВР<FONT COLOR=RED>!!red BUG!!</FONT> \" &lt; &#9829; ' [#title#]", $this->getValue("idCardIssuedBy5"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Визитник група <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Отказ']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// Тест стринга в България
$this->click("link=Система");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Ядро");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Превод");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("//input[@name='filter']", "Bulgaria");
$this->click("filter");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//div[@id='maincontent']/div/div[2]/div[2]/div/div[3]/table/tbody/tr/td[1]/div/div[1]/a[1]/img");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("translated", "България <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("България <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// <tr>
// 	<td>open</td>
// 	<td>http://cloud.bgerp.com/Selenium/core_Lg/edit/407/?ret_url=Selenium%2Fcore_Lg%2Fdefault%2Ffilter%2FBulgaria%2Flg%2Fbg%2FCmd%2Cdefault%2F%25D0%25A4%25D0%25B8%25D0%25BB%25D1%2582%25D1%2580%25D0%25B8%25D1%2580%25D0%25B0%25D0%25B9</td>
// 	<td></td>
// </tr>
$this->click("//div[@id='maincontent']/div/div[2]/div[2]/div/div[3]/table/tbody/tr/td[1]/div/div[1]/a[1]/img");
$this->waitForPageToLoad("30000");
try {
$this->assertEquals("България <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]", $this->getValue("name=translated"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Отказ']");
$this->waitForPageToLoad("30000");
// Обратно във Визитник
$this->click("link=Визитник");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Лица");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("България <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// crm Фирми
$this->click("link=Фирми");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("btnAdd");
$this->waitForPageToLoad("30000");
$this->type("name", "Фирма име <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->type("pCode", "<b>K</b>'[#&lt#]");
$this->type("place", "Фирма Град<FONT COLOR=RED>!redBUG!</FONT> \"&lt;&#9829;'[#title#]");
$this->type("address", "Фирма Адрес <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->type("email", "firma<FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]@mail.com");
$this->type("tel", "Фирма Тел. <b>!ВUG!</b> \" &lt;&#9829; ' [#title#]");
$this->type("fax", "Фирма Факс <b>!ВUG!</b> \" &lt;&#9829; ' [#title#]");
$this->type("website", "www.<FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#].com");
$this->type("vatId", "Фирма Дан. ном. <b>!VAT!</b> \" &lt;&#9829; ' [#title#]");
$this->type("autoElement1", "Фирма Бележки <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->type("regCourt2", "Фирма Съд <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->type("regDecisionNumber", "Фирма Рег. ном. <b>!ВUG!</b> \" &lt;&#9829; ' [#title#]");
$this->type("autoElement3", "<b>!ВUG!</b>\"&lt;&#9829;'[#title#]");
$this->type("regCompanyFileNumber", "Фирма Дело ном. <b>!ВUG!</b> \" &lt;&#9829; ' [#title#]");
$this->type("regCompanyFileYear4", "Фирма Дело год. <b>!ВUG!</b> \" &lt;&#9829; ' [#title#]");
$this->click("groupList_6");
$this->click("id=lists_6");
$this->click("id=lists_7");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Имейли'!\nСтойността не е валиден имейл: firma<FONT, COLOR=RED>!!!, red, BUG, !!!</FONT>, \", &lt, &#9829, ', [#title#]@mail.com"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Данъчен №'!\nТекстът е над допустимите 18 символа"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Решение по регистрация->Номер'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Решение по регистрация->Дата'!\nНе е в допустимите формати, като например: '??-??-20??'"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// Следващият ред е във връзка с Issue #255 - може да се махне след ремонта му, или да стои за проверка
try {
$this->assertFalse($this->isTextPresent("Некоректна стойност на полето 'Решение по регистрация->Дата'!\nНе е в допустимите формати, като например: '<B>??-??-20??</B>'"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Фирмено дело->Номер'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Фирмено дело->Година'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Web сайт'!\nНевалидно URL."));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("autoElement2", "firma<b>!ВUG!</b>\"&lt;&#9829;'[#title#]@mail.com");
$this->type("website", "www.<b>!ВUG!</b>\"&lt;&#9829;'[#title#].com");
$this->type("vatId", "<b>!VAT!</b>'&lt;\"");
$this->type("regDecisionNumber", "<b>!ВUG!</b>\"&lt;&#9829;'[#title#]");
$this->type("autoElement5", "15-05-1999");
$this->type("regCompanyFileNumber", "<b>!ВUG!</b>\"&lt;&#9829;'[#title#]");
$this->type("regCompanyFileYear6", "<b>!ВUG!</b>\"&lt;&#9829;'[#title#]");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Имейли'!\nСтойността не е валиден имейл: firma<b>!ВUG!</b>\"&lt, &#9829, '[#title#]@mail.com"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Web сайт'!\nНевалидно URL."));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Решение по регистрация->Номер'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Фирмено дело->Номер'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Фирмено дело->Година'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("autoElement2", "firma<b>!ВUG!</b>'[#title#]@mail.com");
$this->type("website", "www.<b>!ВUG!</b>'[#title#].com");
$this->type("regDecisionNumber", "<b>!ВUG!</b>'[#title#]");
$this->type("regCompanyFileNumber", "<b>!ВUG!</b>'[#title#]");
$this->type("regCompanyFileYear6", "<b>!ВUG!</b>'[#title#]");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Имейли'!\nСтойността не е валиден имейл: firma<b>!ВUG!</b>'[#title#]@mail.com"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Web сайт'!\nНевалидно URL."));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Решение по регистрация->Номер'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Фирмено дело->Номер'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Фирмено дело->Година'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("autoElement2", "firma&lt;\"&#9829;@mail.com");
$this->type("website", "www.&lt;\"&#9829;.com");
$this->type("regDecisionNumber", "&lt;\"&#9829;");
$this->type("regCompanyFileNumber", "&lt;\"&#9829;");
$this->type("regCompanyFileYear6", "&lt;\"&#9829;");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Имейли'!\nСтойността не е валиден имейл: firma&lt, \"&#9829, @mail.com"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Web сайт'!\nНевалидно URL."));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Решение по регистрация->Номер'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Фирмено дело->Номер'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Фирмено дело->Година'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("autoElement2", "firma?Mail'@mail.com");
$this->type("website", "www.nobug.com");
$this->type("regDecisionNumber", "666");
$this->type("regCompanyFileNumber", "66");
$this->select("regCompanyFileYear6_comboSelect", "label=1999");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Фирма име <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#] - Фирма Град<Font Color=Red>!redbug!</font> \"&lt;&#9829;'[#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("<b>!VAT!</b>'&lt;\""));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("<b>K</b>'[#&lt#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Фирма Град<Font Color=Red>!redbug!</font> \"&lt;&#9829;'[#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Фирма Адрес <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Фирма Тел. <b>!ВUG!</b> \" &lt;&#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Фирма Факс <b>!ВUG!</b> \" &lt;&#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("firma?Mail'@mail.com"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Визитник група <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Фирма Съд <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Фирма Бележки <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// Банкова сметка-добавяне
$this->click("//div[@id='maincontent']/div/div[2]/div[2]/div/div[2]/div/div[2]/fieldset[6]/legend/a/img");
$this->waitForPageToLoad("30000");
$this->select("name=currencyId", "label=<'>");
$this->select("name=type", "label=Разплащателна");
$this->type("name=iban", "IBAN<b>!ВUG!</b>\"&lt;&#9829;'[#title#]");
$this->type("name=bic", "BIC<b>В</b>\"&lt;");
$this->type("name=bank", "Банка Име <FONT COLOR=RED>!red BUG!</FONT>\"&lt;&#9829;'[#title#]");
$this->type("id=autoElement1", "Информация за банкова сметка <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->click("name=Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'IBAN / №'!\nНевалиден IBAN"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("name=iban", "<b>!ВUG!</b>\"&lt;&#9829;'[#title#]");
$this->click("name=Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'IBAN / №'!\nНевалиден IBAN"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("name=iban", "&lt;&#9829;'[#title#]");
$this->click("name=Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'IBAN / №'!\nНевалиден IBAN"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("name=iban", "&lt;&#9829;");
$this->click("name=Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'IBAN / №'!\nНевалиден IBAN"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("name=iban", "<b>!ВUG!</b>");
$this->click("name=Cmd[save]");
$this->waitForPageToLoad("30000");
$this->type("name=iban", "BG86RZBB91553320002000");
// правилната сметка е с 4 вместо последната 0 -> BG86RZBB91553320002004
$this->click("name=Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'IBAN / №'!\nНевалиден IBAN"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("name=iban", "#<b>!ВUG!</b>\"&lt;&#9829;'[#title#]");
$this->click("name=Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertTrue($this->isTextPresent("<'> <b>!ВUG!</b>\"&lt;&#9829;'[#title#], Разплащателна, Банка Име <FONT COLOR=RED>!red BUG!</FONT>\"&lt;&#9829;'[#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// Локация-добавяне
$this->click("//div[@id='maincontent']/div/div[2]/div[2]/div/div[2]/div/div[2]/fieldset[7]/legend/a/img");
$this->waitForPageToLoad("30000");
try {
$this->assertTrue($this->isTextPresent("Нова локация на Фирма име <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#] - Фирма Град<Font Color=Red>!redbug!</font> \"&lt;&#9829;'[#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("name=title", "Локация име <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->select("name=type", "label=Главна квартира");
$this->type("name=address", "Локация Адрес <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->type("name=gln", "<b>B</b>'&lt;");
$this->type("name=gpsCoords", "<b>B</b>\"&lt;&#9829;'[#title#]");
$this->type("id=autoElement1", "Информация за локация <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->click("name=Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'GLN код'!\nНевалиден EAN13 номер. Полето приема само цифри."));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("name=gln", "<b>B</b>");
$this->click("name=Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'GLN код'!\nНевалиден EAN13 номер. Полето приема само цифри."));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("name=gln", "1234567896543");
$this->click("name=Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'GLN код'!\nНевалиден EAN13 номер."));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("name=gln", "");
$this->click("name=Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertTrue($this->isTextPresent("Локация име <FONT COLOR=RED>! ... &lt; &#9829; ' [#title#], Главна квартира"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// Локация-ОК-евентуално съобщението за GLN номера?
$this->click("link=Фирми");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Всички Екип \"Headquarter\" Стефан Арсов Екип \"Роля Екип <b>!ВUG!</b> \" &lt;&#9829; ' [#title#]\" Име потребител <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Фирма име <FONT COLOR=RED>!!! ... &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("<b>!VAT!</b>'&lt;\""));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Фирма Тел. <b>!ВUG!</b> \" &lt;&#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Фирма Факс <b>!ВUG!</b> \" &lt;&#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("firma?Mail'@mail.com"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("България <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("<b>K</b>'[#&lt#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Фирма Град<Font Color=Red>!redbug!</font> \"&lt;&#9829;'[#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Фирма Адрес <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//tr[@id='lr_2']/td[5]/div/div/a/img");
$this->waitForPageToLoad("30000");
try {
$this->assertEquals("Фирма име <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]", $this->getValue("name"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("<b>K</b>'[#&lt#]", $this->getValue("pCode1"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Фирма Град<Font Color=Red>!redbug!</font> \"&lt;&#9829;'[#title#]", $this->getValue("place"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Фирма Адрес <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]", $this->getValue("address"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue((bool)preg_match("/^firma[\s\S]Mail'@mail\.com$/",$this->getValue("email")));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Фирма Тел. <b>!ВUG!</b> \" &lt;&#9829; ' [#title#]", $this->getValue("tel"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Фирма Факс <b>!ВUG!</b> \" &lt;&#9829; ' [#title#]", $this->getValue("fax"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("<b>!VAT!</b>'&lt;\"", $this->getValue("vatId"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Фирма Бележки <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]", $this->getValue("autoElement2"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Фирма Съд <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]", $this->getValue("regCourt3"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Визитник група <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Отказ']");
$this->waitForPageToLoad("30000");
// Обвързване на Лице към Фирма
$this->click("link=Лица");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//a[@id='edt1']/img");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->select("buzCompanyId", "label=Фирма име <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Фирма име <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Лице Имена <FONT COLOR=RED>!! ... &lt; &#9829; ' [#title#]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Фирма име <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("btnEdit");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Фирма име <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Отказ']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Фирми");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Фирма име <FONT COLOR=RED>!!! ... &lt; &#9829; ' [#title#]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Лице Имена <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Лице Лич. Моб. <b>!ВUG!</b> \" &lt;&#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Лице Служ. Тел.<b>!ВUG!</b> \" &lt;&#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("lichen.'no?Bug@mail.com"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// Проверка-права - Моята Фирма трябва да не се вижда от текущия потребител, т.к. в момента е @system, а потребителят трябва да вижда само своите, при филтриране обаче трябва да се вижда
$this->click("link=Фирми");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Моята Фирма ООД"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("name=search", "Моята Фирма");
$this->click("id=filter");
$this->waitForPageToLoad("30000");
try {
$this->assertTrue($this->isTextPresent("Моята Фирма ООДБългария"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// Създаване на Тестова Фирма АД и избор на група (произволна) за да има варианти за избор на групи в опционното поле при импорт на фирми в Списък за Разпращане
$this->click("link=Фирми");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Моята Фирма ООД"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("id=btnAdd");
$this->waitForPageToLoad("30000");
$this->type("name=name", "Тестова Фирма АД");
$this->click("id=groupList_5");
$this->click("name=Cmd[save]");
$this->waitForPageToLoad("30000");

// crm РАЗПРАЩАНЕ
$this->click("link=Разпращане");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// crm Списъци
$this->click("link=Списъци");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("btnAdd");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("title", "Списък за разпращане <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->type("fields", "# - едноредов коментар\n#Само се премахва '#'\nname=Име\nfamily=Фамилия\ntest1=<b>!boldВUG!</b>\"&lt;&#9829;'[#title#]\npole<FONT COLOR=RED>!!!redBUG!!!</FONT>\"&lt;&#9829;'[#title#]=Поле<FONT COLOR=RED>! red BUG !</FONT> \"&lt;&#9829; ' [#title#]\n#date=Дата\n#hour=Час\n#и др.\n\n#Полета за адресант\n#company = Фирма\n#person = Лице\n#email = Имейл\n#tel = Тел\n#fax = Факс\n#country = Държава\n#postCode = Пощенски код\n#city = Град\n#address = Адрес\n\n#Препоръчителни за \"Писма\"\n#recipient=Получател\n#address=Адрес\n#postCode=Пощенски код\n#city=Град\n#district=Област\n#country=Държава");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("arsov » Списъци за изпращане ... SMS-и, факсове и др. (Кюп) » Списък за разпращане <FONT CO ... &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Заглавие: Списък за разпращане <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Ключово поле: Имейл"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("test1=&lt;b>!boldВUG!&lt;/b>\"&amp;lt;&amp;#9829;&#39;[#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("pole_font_color=RED>!!!redBUG!!!&lt;/FONT>\"&amp;lt;&amp;#9829;&#39;[#title#]&#61;Поле&lt;FONT COLOR&#61;RED>! red BUG !&lt;/FONT> \"&amp;lt;&amp;#9829; &#39; [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// ако светне червено - значи двойния ескейп е оправен и да са замени с test1=<b>!boldВUG!</b>"&lt;&#9829;'[#title#] и pole_font_color=RED>!!!redBUG!!!</FONT>"&lt;&#9829;'[#title#]=Поле<FONT COLOR=RED>!!!redBUG!!!</FONT>"&lt;&#9829;'[#title#]
try {
$this->assertTrue($this->isTextPresent("ИмейлИмеФамилия<b>!boldВUG!</b>\"&lt;&#9829;'[#title#]RED>!!!redBUG!!!</FONT>\"&lt;&#9829;'[#title#]=Поле<FONT COLOR=RED>! red BUG !</FONT> \"&lt;&#9829; ' [#title#]Създаване"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("!boldВUG! <♥"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("RED>!!!redBUG!!! < ♥"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("btnEdit");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Редактиране на циркулярни контакти в Списъци за изпращане ... SMS-и, факсове и др."));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Списък за разпращане <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]", $this->getValue("title"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("# - едноредов коментар\n#Само се премахва '#'\nname=Име\nfamily=Фамилия\ntest1=<b>!boldВUG!</b>\"&lt;&#9829;'[#title#]\npole<FONT COLOR=RED>!!!redBUG!!!</FONT>\"&lt;&#9829;'[#title#]=Поле<FONT COLOR=RED>! red BUG !</FONT> \"&lt;&#9829; ' [#title#]\n#date=Дата\n#hour=Час\n#и др.\n\n#Полета за адресант\n#company = Фирма\n#person = Лице\n#email = Имейл\n#tel = Тел\n#fax = Факс\n#country = Държава\n#postCode = Пощенски код\n#city = Град\n#address = Адрес\n\n#Препоръчителни за \"Писма\"\n#recipient=Получател\n#address=Адрес\n#postCode=Пощенски код\n#city=Град\n#district=Област\n#country=Държава", $this->getValue("fields"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Отказ']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// Нов запис - ръчно въвеждане
$this->click("btnAdd");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("email", "<b>!ВUG!</b>\"&lt;&#9829;'[#title#]@mail.com");
$this->type("name", "Име в списък <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->type("family", "Фамилия в списък <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->type("test1", "тест1 списък <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->type("pole_font_color", "тест2 списък <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Имейл'!\nНекоректен имейл"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("autoElement1", "<b>!ВUG!</b>@mail.com");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Имейл'!\nНекоректен имейл"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("autoElement1", "\"&lt;&#9829;@mail.com");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Имейл'!\nНекоректен имейл"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("autoElement1", "'[#title#]@mail.com");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Имейл'!\nНекоректен имейл"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("autoElement1", "testMail@mail.com");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("arsov » Списъци за изпращане ... SMS-и, факсове и др. (Кюп) » Списък за разпращане <FONT CO ... &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Заглавие: Списък за разпращане <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Ключово поле: Имейл"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("test1=&lt;b>!boldВUG!&lt;/b>\"&amp;lt;&amp;#9829;&#39;[#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("pole_font_color=RED>!!!redBUG!!!&lt;/FONT>\"&amp;lt;&amp;#9829;&#39;[#title#]&#61;Поле&lt;FONT COLOR&#61;RED>! red BUG !&lt;/FONT> \"&amp;lt;&amp;#9829; &#39; [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// ако светне червено - значи двойния ескейп е оправен и да са замени с test1=<b>!boldВUG!</b>"&lt;&#9829;'[#title#] и pole_font_color=RED>!!!redBUG!!!</FONT>"&lt;&#9829;'[#title#]=Поле<FONT COLOR=RED>!!!redBUG!!!</FONT>"&lt;&#9829;'[#title#]
try {
$this->assertTrue($this->isTextPresent("Фамилия<b>!boldВUG!</b>\"&lt;&#9829;'[#title#]RED>!!!redBUG!!!</FONT>\"&lt;&#9829;'[#title#]=Поле<FONT COLOR=RED>! red BUG !</FONT> \"&lt;&#9829; ' [#title#]Създаване"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("testMail@mail.com"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Име в списък <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Фамилия в списък <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("тест1 списък <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("тест2 списък <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//tr[@id='lr_1']/td[1]/div/div[1]/a[1]/img");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Редактиране в \"Списък за разпращане <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]\""));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("testMail@mail.com", $this->getValue("email"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Име в списък <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]", $this->getValue("name"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Фамилия в списък <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]", $this->getValue("family"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("тест1 списък <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]", $this->getValue("test1"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("тест2 списък <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]", $this->getValue("pole_font_color"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("<b>!boldВUG!</b>\"&lt;&#9829;'[#title#]:"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("RED>!!!redBUG!!!</FONT>\"&lt;&#9829;'[#title#]=Поле<FONT COLOR=RED>! red BUG !</FONT> \"&lt;&#9829; ' [#title#]:"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Отказ']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// Импорт - Фирми от Група в Контакти
$this->click("//input[@value='Импорт']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("source4");
$this->click("Cmd[next]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->select("name=companiesGroup", "label=Визитник група <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#] (1)");
// След ремонта на двойното ескейпване когато даде грешка и забие тук - да се замени с Визитник група <FONT COLOR=RED>!!! red BUG !!!</FONT> " &lt; &#9829; ' [#title#] (2)
$this->click("Cmd[next]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->select("priority", "label=Съществуващите данни да се запазят");
$this->click("Cmd[next]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->select("colemail", "label=Имейли");
$this->select("colname", "label=Фирма");
$this->select("colfamily", "label=Град");
$this->select("coltest1", "label=Адрес");
$this->select("colpole_font_color", "label=П. код");
$this->click("Cmd[next]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Добавени са 1 нови записа, обновени - 0, пропуснати - 0"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("firma?mail'@mail.com"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Фирма име <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Фирма Град<Font Color=Red>!redbug!</font> \"&lt;&#9829;'[#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Фирма Адрес <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("<b>K</b>'[#&lt#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//tr[@id='lr_2']/td[1]/div/div[1]/a[1]/img");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Редактиране в \"Списък за разпращане <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]\""));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue((bool)preg_match("/^firma[\s\S]mail'@mail\.com$/",$this->getValue("email")));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Фирма име <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]", $this->getValue("name"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Фирма Град<Font Color=Red>!redbug!</font> \"&lt;&#9829;'[#title#]", $this->getValue("family"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Фирма Адрес <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]", $this->getValue("test1"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("<b>K</b>'[#&lt#]", $this->getValue("pole_font_color"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("<b>!boldВUG!</b>\"&lt;&#9829;'[#title#]:"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("RED>!!!redBUG!!!</FONT>\"&lt;&#9829;'[#title#]=Поле<FONT COLOR=RED>! red BUG !</FONT> \"&lt;&#9829; ' [#title#]:"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Отказ']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// Импорт - Лица от Група в Контакти
$this->click("//input[@value='Импорт']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("source5");
$this->click("Cmd[next]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("Cmd[next]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->select("priority", "label=Съществуващите данни да се запазят");
$this->click("Cmd[next]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// с неправилно избрани полета
$this->select("colemail", "label=Имена");
$this->select("colname", "label=Лични комуникации->Имейли");
$this->select("colfamily", "label=Нас. място");
$this->select("coltest1", "label=Адрес");
$this->select("colpole_font_color", "label=Пощ. код");
$this->click("Cmd[next]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Добавени са 0 нови записа, обновени - 0, пропуснати - 1"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Импорт']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("source5");
$this->click("Cmd[next]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("Cmd[next]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->select("priority", "label=Съществуващите данни да се запазят");
$this->click("Cmd[next]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// полетата-правилно
$this->select("colemail", "label=Лични комуникации->Имейли");
$this->select("colname", "label=Имена");
$this->select("colfamily", "label=Нас. място");
$this->select("coltest1", "label=Адрес");
$this->select("colpole_font_color", "label=Пощ. код");
$this->click("Cmd[next]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Добавени са 1 нови записа, обновени - 0, пропуснати - 0"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("lichen.'no?bug@mail.com"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Лице Имена <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Лице Град<Font Color=Red>!red Bug!</font> \"&lt;&#9829;'[#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Лице Адрес <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("<b>K</b>'[#&lt#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//tr[@id='lr_3']/td[1]/div/div[1]/a[1]/img");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Редактиране в \"Списък за разпращане <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]\""));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue((bool)preg_match("/^lichen\.'no[\s\S]bug@mail\.com$/",$this->getValue("email")));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Лице Имена <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]", $this->getValue("name"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Лице Град<Font Color=Red>!red Bug!</font> \"&lt;&#9829;'[#title#]", $this->getValue("family"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Лице Адрес <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]", $this->getValue("test1"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("<b>K</b>'[#&lt#]", $this->getValue("pole_font_color"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("<b>!boldВUG!</b>\"&lt;&#9829;'[#title#]:"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("RED>!!!redBUG!!!</FONT>\"&lt;&#9829;'[#title#]=Поле<FONT COLOR=RED>! red BUG !</FONT> \"&lt;&#9829; ' [#title#]:"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Отказ']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// Импорт - Copy&Paste на CSV данни
$this->click("//input[@value='Импорт']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("source2");
$this->click("Cmd[next]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("csvData", "<b>!ВUG!</b>\"&lt;&#9829;'[#title#]@mail.com,Име CSV Paste <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#],Нас.място CSV Paste <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#],Адрес CSV Paste <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#],Пощ. код CSV Paste <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->click("Cmd[next]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->select("delimiter2_comboSelect", "label=,");
$this->select("enclosure3_comboSelect", "label=\"");
$this->select("firstRow", "label=Данни");
$this->click("Cmd[next]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->select("priority", "label=Съществуващите данни да се запазят");
$this->click("Cmd[next]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("!ВUG!\"<♥'[#title#]@mail.com"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Име CSV Paste !!! red BUG !!! \" < ♥ ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Нас.място CSV Paste !!! red BUG !!! \" < ♥ ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Адрес CSV Paste !!! red BUG !!! \" < ♥ ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Пощ. код CSV Paste !!! red BUG !!! \" < ♥ ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->select("name=colemail", "label=<b>!ВUG!</b>\"&lt;&#9829;'[#title#]@mail.com");
$this->select("name=colname", "label=Име CSV Paste <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->select("name=colfamily", "label=Нас.място CSV Paste <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->select("name=coltest1", "label=Адрес CSV Paste <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->select("name=colpole_font_color", "label=Пощ. код CSV Paste <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->click("Cmd[next]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Добавени са 0 нови записа, обновени - 0, пропуснати - 1"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// отново-с валиден мейл
$this->click("//input[@value='Импорт']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("source2");
$this->click("Cmd[next]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("csvData", "drug_lichen@mail.com,Име CSV Paste <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#],Нас.място CSV Paste <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#],Адрес CSV Paste <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#],Пощ. код CSV Paste <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->click("Cmd[next]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->select("delimiter2_comboSelect", "label=,");
$this->select("enclosure3_comboSelect", "label=\"");
$this->select("firstRow", "label=Данни");
$this->click("Cmd[next]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->select("priority", "label=Съществуващите данни да се запазят");
$this->click("Cmd[next]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Име CSV Paste !!! red BUG !!! \" < ♥ ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Нас.място CSV Paste !!! red BUG !!! \" < ♥ ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Адрес CSV Paste !!! red BUG !!! \" < ♥ ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Пощ. код CSV Paste !!! red BUG !!! \" < ♥ ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->select("colemail", "label=drug_lichen@mail.com");
$this->select("name=colname", "label=Име CSV Paste <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->select("name=colfamily", "label=Нас.място CSV Paste <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->select("name=coltest1", "label=Адрес CSV Paste <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->select("name=colpole_font_color", "label=Пощ. код CSV Paste <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->click("Cmd[next]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Добавени са 1 нови записа, обновени - 0, пропуснати - 0"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("drug_lichen@mail.com"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Име CSV Paste <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Нас.място CSV Paste <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Адрес CSV Paste <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Пощ. код CSV Paste <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//tr[@id='lr_4']/td[1]/div/div[1]/a[1]/img");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Редактиране в \"Списък за разпращане <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]\""));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("drug_lichen@mail.com", $this->getValue("email"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Име CSV Paste <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]", $this->getValue("name"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Нас.място CSV Paste <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]", $this->getValue("family"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Адрес CSV Paste <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]", $this->getValue("test1"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Пощ. код CSV Paste <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]", $this->getValue("pole_font_color"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("<b>!boldВUG!</b>\"&lt;&#9829;'[#title#]:"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("RED>!!!redBUG!!!</FONT>\"&lt;&#9829;'[#title#]=Поле<FONT COLOR=RED>! red BUG !</FONT> \"&lt;&#9829; ' [#title#]:"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Отказ']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}

// последно!! данни след Setup
$this->click("link=Система");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Ядро");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Пакети");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("xpath=(//input[@value='Обновяване'])[5]");
$this->waitForPageToLoad("30000");
$this->click("link=Визитник");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Календар");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Списък");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("autoElement2", "12-02-2012");
$this->click("filter");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Празник име <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Рожден ден на Лице Имена <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}

// ДОКУМЕНТИ
// Създаване Папка на Фирма
$this->click("link=Визитник");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Контакти");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Фирми");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Тестова Фирма АД");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Папка']");
$this->waitForPageToLoad("30000");
// Дали да е само клик или като долу
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->assertTrue((bool)preg_match('/^Наистина ли желаете да създадетe папка за документи към "Тестова Фирма АД - Bulgaria"[\s\S]$/',$this->getConfirmation()));
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// тук имаше pause 5000 - ако светне червено следващия ред - върни
try {
$this->assertTrue($this->isTextPresent("arsov » Тестова Фирма АД - Bulgaria (Фирма)"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Общи");
$this->waitForPageToLoad("30000");
$this->click("link=Папки");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Тестова Фирма АД - Bulgaria"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Тестова Фирма АД - BulgariaФирмаArsov"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("arsov@selenium.bgerp.comЕ-кутияArsov"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Визитник");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Контакти");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Фирми");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Фирма име <FONT COLOR=RED>!!! ... &lt; &#9829; ' [#title#]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->clickAt("//input[@value='Папка']", "");
$this->waitForPageToLoad("30000");
// Дали да остане тази команда или само клик както е по-горе
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->assertTrue((bool)preg_match('/^Наистина ли желаете да създадетe папка за документи към "Фирма име &lt;FONT COLOR=RED>!!! red BUG !!!&lt;\/FONT> " &amp;lt; &amp;#9829; \' \[#title#\] - Фирма Град&lt;Font Color=Red>!redbug!&lt;\/font> "&amp;lt;&amp;#9829;\'\[#title#\]"[\s\S]$/',$this->getConfirmation()));
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("arsov » Фирма име <FONT CO ... &#9829;'[#title#] (Фирма)"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Общи");
$this->waitForPageToLoad("30000");
$this->click("link=Папки");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Фирма име <FONT CO ... &#9829;'[#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Фирма име <FONT CO ... &#9829;'[#title#]ФирмаArsov"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// Изпращане на email
$this->click("link=Документи");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Общи");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Папки");
$this->waitForPageToLoad("30000");
$this->click("link=arsov@*");
$this->waitForPageToLoad("30000");
// линкът по принцип е link=arsov@selenium.bgerp.com, но е съкратен, за да работи с различни бази
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("id=btnAdd");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Изходящ имейл']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("name=subject", "Тест за изпращане Selenium <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->type("id=autoElement1", "Здравейте, \n\n[em=bigsmile]\n\nТова е само Тест за изпращане Selenium <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]\n\n[bg=yellow]Това беше линк към файл:[/bg] ... \n\n[i][u]Това е тестовата фирма:[/u][/i] http://cloud.bgerp.com/Selenium/crm_Companies/single/2/?ret_url=Selenium%2Fcrm_Companies%2Fdefault\n\n[em=beer]\n\nСърдечни поздрави,\nСтефан Арсов\nМоята Фирма ООД");
$this->type("name=recipient", "Фирма в мейл <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->click("id=autoElement1");
$this->click("name=Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("arsov » arsov@selenium.bgerp.com (Е-кутия) » Тест за изпращане Selenium <F ... &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Изходящ имейл #Eml1 (Чернова)"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Тест за изпращане Selenium <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]?Реф.: Eml1"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Дата: ??-??-201? До: Фирма в мейл <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Това е само Тест за изпращане Selenium <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Това беше линк към файл: ..."));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Това е тестовата фирма: Фирма име <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Активиране']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Изходящ имейл #Eml1 (Активирано)"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Изпращане']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->select("name=boxFrom", "label=arsov@*");
// пълният адрес беше arsov@selenium.bgerp.com - съкратен е за да върви и в други бази
$this->type("name=emailsTo", "bgERPtest@gmail.com");
// HTML в html-а ?
try {
$this->assertTrue($this->isTextPresent("Реф.: Eml1*Дата: ??-??-201?*До: Фирма в мейл <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]*Тест за изпращане Selenium <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]*Здравейте,?????????Това е само Тест за изпращане Selenium <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]*Това беше линк към файл: ...*Това е тестовата фирма: Фирма име <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]*Сърдечни поздрави,*Стефан Арсов*Моята Фирма ООД"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// HTML в ritch текста ?
try {
$this->assertTrue($this->isTextPresent("Реф.: Eml1?Дата: ??-??-201??До: Фирма в мейл <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]???Тест за изпращане Selenium <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]???Здравейте,????:D???Това е само Тест за изпращане Selenium <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]??Това беше линк към файл: ...???Това е тестовата фирма: Фирма име <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]???[beer]???Сърдечни поздрави,?Стефан Арсов?Моята Фирма ООД"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// в ritch текста в текста на писмото линка към фирма във Визитника се извеждаше като url - т.е.: ???Това е тестовата фирма: http://cloud.bgerp.com/Selenium/crm_Companies/single/2/???[beer]???  -  решено е щом изпращаме нещо извън фирмата (системата), линковете да се заместват с името - т.е. линкове да не се пращат - т.е. линк вече няма и в HTML-а
try {
$this->assertFalse($this->isTextPresent("!!! red BUG !!!"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("id=save");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Успешно изпратено до: bgERPtest@gmail.com"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("1 изпращане"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
sleep(10);
// Ръчен download на писмата
$this->click("link=Система");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Ядро");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Крон");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->open("http://cloud.bgerp.com/Selenium/core_Cron/ProcessRun/9_45c4/?forced=yes");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->assertTrue($this->isTextPresent("catchall@selenium.bgerp.com (localhost): Skip: 0, Skip service: 0, Errors: 0, New: 1"));
// Връщаме се в bgERP
$this->open("http://cloud.bgerp.com/Selenium/core_Cron/");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Документи");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Общи");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Папки");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=arsov@*");
$this->waitForPageToLoad("30000");
// линкът по принцип е link=arsov@selenium.bgerp.com, но е съкратен, за да работи с различни бази
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("#EML1??? Тест за изпращане Selen ... &lt; &#9829; ' [#title#]arsov*Днес*Днес"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=#EML1??? Тест за изпращане Selen*&lt; &#9829; ' [#title#]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Входящ имейл #Msg1 (Приключено)"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Относно: #EML1??? Тест за изпращане Selenium <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("До: arsov@selenium.bgerp.com"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("От: arsov@selenium.bgerp.com"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Реф.: Eml1??Дата: ??-??-20????До: Фирма в мейл <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]????Тест за изпращане Selenium <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Здравейте,*Това е само Тест за изпращане Selenium <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]*Това беше линк към файл: ...*Това е тестовата фирма: Фирма име <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]*[beer]*Сърдечни поздрави,*Стефан Арсов*Моята Фирма ООД"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}

// Тест на Разпращане
// Създаване на фирми с имейли, на които ще изпращаме бласт писма
$this->click("link=Визитник");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Контакти");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Фирми");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//a[@id='edt2']/img");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("name=email", "bgERPtest@gmail.com");
$this->click("name=Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("id=btnAdd");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("name=name", "Яка фирма в bgERPtest за Разпращане СД");
$this->type("name=email", "testbgERP@gmail.com");
$this->click("id=groupList_6");
$this->click("name=Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// Създаване на списък за разпращане
$this->click("link=Визитник");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Разпращане");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Списъци");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("id=btnAdd");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("name=title", "Selenium тест на \"Разпращане\"");
$this->type("name=fields", "# - едноредов коментар\n#Само се премахва '#'\n#name=Име\n#family=Фамилия\n#date=Дата\n#hour=Час\n#и др.\n\n#Полета за адресант\ncompany = Фирма\nperson = Лице\n#email = Имейл\n#tel = Тел\n#fax = Факс\n#country = Държава\n#postCode = Пощенски код\n#city = Град\n#address = Адрес\n\n#Препоръчителни за \"Писма\"\n#recipient=Получател\n#address=Адрес\n#postCode=Пощенски код\n#city=Град\n#district=Област\n#country=Държава");
$this->click("name=Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Списък за масово разпращане - #Bls2"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Заглавие: Selenium тест на \"Разпращане\""));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// Попълване на списъка - импорт на група фирми
$this->click("//input[@value='Импорт']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("id=source4");
$this->click("name=Cmd[next]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->select("name=companiesGroup", "label=Визитник група <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#] (2)");
$this->click("name=Cmd[next]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->select("name=priority", "label=Съществуващите данни да се запазят");
$this->click("name=Cmd[next]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->select("name=colemail", "label=Имейли");
$this->select("name=colcompany", "label=Фирма");
$this->select("name=colperson", "label=nameList");
$this->click("name=Cmd[next]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Добавени са 2 нови записа, обновени - 0, пропуснати - 0"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//a[@id='edt6']/img");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("name=person", "Пешо Пешев");
$this->click("name=Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//a[@id='edt5']/img");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("name=person", "Лице от фирма <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->click("name=Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// Подготовка на шаблона на имейл-а
$this->click("link=Визитник");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Разпращане");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Имейли");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("id=btnAdd");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->select("name=listId", "label=Selenium тест на \"Разпращане\"");
$this->select("name=from", "label=arsov@*");
// пълният адрес беше arsov@selenium.bgerp.com - съкратен е за да върви и в други бази
$this->type("name=subject", "Разпращане до [#email#] и Selenium <b>!ВUG!</b>\"&lt;&#9829;'[#title#]");
$this->type("id=autoElement1", "Уважаеми г-н/г-жо [#person#]!\n\n[em=bigsmile]\n\nТова е само Selenium тест на системата за разпращане!\n\n<FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]\n\nПоздрави!");
$this->click("//img[@alt='bigsmile']");
$this->click("name=Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Шаблоните, които сте въвели ги няма в БД: tel, fax, country, postCode, city, address"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("name=tel", "");
$this->type("name=fax", "");
$this->type("name=country", "");
$this->type("name=pcode", "");
$this->type("name=place", "");
$this->type("name=address", "");
$this->click("name=Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Шаблоните, които сте въвели ги няма в БД: title, title"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("name=subject", "Разпращане до [#email#] и Selenium <b>!ВUG!</b>\"&lt;'&#9829;");
$this->type("id=autoElement1", "Уважаеми г-н/г-жо [#person#]!\n\n[em=bigsmile]\n\nТова е само Selenium тест на системата за разпращане!\n\n<FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; ' &#9829;\n\nПоздрави!");
$this->click("name=Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("arsov » Циркулярни имейли (Кюп) » Разпращане до [#email#] и Seleni ... ВUG!</b>\"&lt;'&#9829;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("#Inf1 - Циркулярен имейл, Чернова"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Разпращане до [#email#] и Selenium <b>!ВUG!</b>\"&lt;'&#9829;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Реф.: Inf1 Дата: ??-??-201?"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("До: [#company#] Към: [#person#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Имейл: [#email#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Уважаеми г-н/г-жо [#person#]!????????Това е само Selenium тест на системата за разпращане!????<FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; ' &#9829;????Поздрави!"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Активиране']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Стартиране на масово разпращане"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Писмо: Разпращане до [#email#] и Selenium <b>!ВUG!</b>\"&lt;'&#9829; / ??-??-12"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// HTML в html-а ?
try {
$this->assertTrue($this->isTextPresent("Реф.: Inf1??Дата: ??-??-201???До: Фирма име <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]??Към: Лице от фирма <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]????Имейл: bgerptest@gmail.com"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Разпращане до bgerptest@gmail.com и Selenium <b>!ВUG!</b>\"&lt;'&#9829;??????Уважаеми г-н/г-жо Лице от фирма <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]!????????Това е само Selenium тест на системата за разпращане!????<FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; ' &#9829;????Поздрави!"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// HTML в ritch текста ?
try {
$this->assertTrue($this->isTextPresent("Реф.: Inf1?Имейл: bgerptest@gmail.com?Дата: ??-??-201??До: Фирма име <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]?Към: Лице от фирма <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Разпращане до bgerptest@gmail.com и Selenium <b>!ВUG!</b>\"&lt;'&#9829;???Уважаеми г-н/г-жо Лице от фирма <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]!???:D???Това е само Selenium тест на системата за разпращане!??<FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; ' &#9829;??Поздрави!"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// задаваме параметрите
$this->type("name=sendPerMinute", "10");
$this->click("name=Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Успешно активирахте бласт имейл-а"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}

// Ръчен send & download на писмата
$this->click("link=Система");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Ядро");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Крон");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->open("http://cloud.bgerp.com/Selenium/core_Cron/ProcessRun/10_d3d9/?forced=yes");
try {
$this->assertTrue($this->isTextPresent("ProcessRun successfuly execute blast_Emails->cron_SendEmail"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Изпращането приключи"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
sleep(10);
$this->open("http://cloud.bgerp.com/Selenium/core_Cron/ProcessRun/9_45c4/?forced=yes");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->assertTrue($this->isTextPresent("catchall@selenium.bgerp.com (localhost): Skip: 0, Skip service: 0, Errors: 0, New: ?"));
// Връщаме се в bgERP
$this->open("http://cloud.bgerp.com/Selenium/core_Cron/");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Документи");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Общи");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Папки");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("arsov@selenium.bgerp.comЕ-кутияArsov3   4Днес"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("team@selenium.bgerp.comЕ-кутияArsov   0Днес"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=arsov@selenium.bgerp.com");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Msg?\n?#INF1??? Разпращане до testbgerp*arsov@selenium.bgerp.comДнес"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Msg?\n?#INF1??? Разпращане до bgerptest*arsov@selenium.bgerp.comДнес"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=#INF1??? Разпращане до bgerptest*");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("arsov » arsov@selenium.bgerp.com (Е-кутия) » #INF1??? Разпращане до bgerptest ... ВUG!</b>\"&lt;'&#9829;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Входящ имейл #Msg? (Приключено)"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Относно: #INF1??? Разпращане до bgerptest@gmail.com и Selenium <b>!ВUG!</b>\"&lt;'&#9829;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("До: arsov@selenium.bgerp.com"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("От: arsov@selenium.bgerp.com"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Реф.: Inf1\n Имейл: bgerptest@gmail.com"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("До: Фирма име <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]\n Лице от фирма <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Разпращане до bgerptest@gmail.com и Selenium <b>!ВUG!</b>\"&lt;&#9829;'[#title#]\n \n Уважаеми г-н/г-жо Лице от фирма <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]!\n \n Това е само Selenium тест на системата за разпращане!\n \n <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]\n \n Поздрави!"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// В горните два реда има двойно ескейпване
try {
$this->assertTrue($this->isTextPresent(":D"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Теми");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=#INF1??? Разпращане до testbgerp*");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("arsov » arsov@selenium.bgerp.com (Е-кутия) » #INF1??? Разпращане до testbgerp ... ВUG!</b>\"&lt;'&#9829;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Входящ имейл #Msg? (Приключено)"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Относно: #INF1??? Разпращане до testbgerp@gmail.com и Selenium <b>!ВUG!</b>\"&lt;'&#9829;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("До: arsov@selenium.bgerp.com"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("От: arsov@selenium.bgerp.com"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Реф.: Inf1\n Имейл: testbgerp@gmail.com"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("До: Яка фирма в bgERPtest за Разпращане СД\n Към: Пешо Пешев"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Разпращане до testbgerp@gmail.com и Selenium <b>!ВUG!</b>\"&lt;&#9829;'[#title#]\n \n Уважаеми г-н/г-жо Пешо Пешев!\n \n Това е само Selenium тест на системата за разпращане!\n \n <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]\n \n Поздрави!"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent(":D"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}

// рутиране по домейн от визитка до "общ" адрес - изпращане на писмо от база worktest от адрес, домейнът на който се споменава в служебния мейл адрес на лице, представител на фирма - би трябвало да създаде папка на фирмата и да влезе в нея
// създаваме визитка на лице, прикачено към фирма, която няма направена папка
$this->click("link=Визитник");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Контакти");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Фирми");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("id=btnAdd");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("name=name", "Фирма тест за Рутиране по ДОМЕЙН ЕАД");
$this->click("id=groupList_6");
$this->click("id=groupList_1");
$this->click("id=lists_7");
$this->click("name=Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Лица");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("id=btnAdd");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->select("name=salutation", "label=Г-н");
$this->type("name=name", "Домейн Домейнов");
$this->type("name=place", "База worktest");
$this->select("name=buzCompanyId", "label=Фирма тест за Рутиране по ДОМЕЙН ЕАД");
$this->type("name=buzEmail", "dimko@worktest.bgerp.com");
$this->click("id=groupList_6");
$this->click("id=groupList_1");
$this->click("id=lists_7");
$this->click("name=Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Служебна информация?Фирма тест за Рутиране по ДОМЕЙН ЕАД??Имейл: dimko@worktest.bgerp.com"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Фирми");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Фирма тест за Рутиране по ДОМЕЙН ЕАД"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Лица");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Домейн ДомейновФирма тест за Рутиране по ДОМЕЙН ЕАД?България !!! red BUG !!! \" < ♥ ' [#title#]?База Worktest"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// когато се оправи HTML Injection-а от превода тук ще светне червено - да се замени България !!! red BUG !!! " < ♥ ' [#title#] с България <FONT COLOR=RED>!!! red BUG !!!</FONT> " &lt; &#9829; ' [#title#]
$this->click("//a[@id='edt2']/img");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("dimko@worktest.bgerp.com", $this->getValue("name=buzEmail"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Отказ']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// база  workTest
// изпращаме писмото от база worktest
$this->open("http://cloud.bgerp.com/workTest/");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("name=nick", "test007");
$this->type("name=password", "test007");
$this->click("xpath=(//input[@name='Cmd[default]'])[2]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Документи");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Общи");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Папки");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=test007@worktest.bgerp.com");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("id=btnAdd");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Изходящ имейл']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("name=subject", "Тест за рутиране по ДОМЕЙН");
$this->type("id=autoElement1", "Здравейте, \n\nНадяваме се искрено, че рутирането ще работи съгласно заданието - писмото би трябвало да създаде папка на фирма \"Фирма тест за Рутиране по ДОМЕЙН ЕАД\" и да влезе в нея!\n\nИ само за тест <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]\n\nСърдечни поздрави,\nТестер Тестерсен\nФирма тест за Рутиране по Визитка ЕАД\n5000 В. Търново\nул. ПАТРИАРХ ЕВТИМИЙ, № 3\nТел.: 062 / 611 515\nФакс: 062 / 611 515\nwww.worktest.bgerp.com");
$this->click("name=Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Изходящ имейл #Eml* (Чернова)"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("И само за тест <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Активиране']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Изходящ имейл #Eml* (Активирано)"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Изпращане']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("name=emailsTo", "team@selenium.bgerp.com");
try {
$this->assertTrue($this->isTextPresent("Дата: ??-??-201????????????Здравейте,?????Надяваме се искрено, че рутирането ще работи съгласно заданието - писмото би трябвало да създаде папка на фирма \"Фирма тест за Рутиране по ДОМЕЙН ЕАД\" и да влезе в нея!????И само за тест <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Дата: ??-??-201????Тест за рутиране по ДОМЕЙН???Здравейте,???Надяваме се искрено, че рутирането ще работи съгласно заданието - писмото би трябвало да създаде папка на фирма \"Фирма тест за Рутиране по ДОМЕЙН ЕАД\" и да влезе в нея!??И само за тест <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("id=save");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Успешно изпратено до: team@selenium.bgerp.com"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("1 изпращане"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("id=btnDelete");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->assertTrue((bool)preg_match('/^Наистина ли желаете да оттеглите документа[\s\S]$/',$this->getConfirmation()));
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=изход:test007");
$this->waitForPageToLoad("30000");
// в база Selenium
$this->open("http://cloud.bgerp.com/Selenium/core_Cron/ProcessRun/9_45c4/?forced=yes");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error:"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error!"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->open("http://cloud.bgerp.com/Selenium/");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Известия*Днес*??:??Отворени теми в \"Фирма тест за Рутиране по ДОМЕЙН ЕАД - Bulgaria\""));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Документи");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Общи");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Папки");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Фирма тест за Рутиране по ДОМЕЙН ЕАД - BulgariaФирмаArsov1???1Днес"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// ОК!! създадена е папка на Фирма тест за Рутиране по ДОМЕЙН ЕАД - в служебния мейл адрес на чийто представител се споменава домейна!!!
$this->click("link=Фирма тест за Рутиране по ДОМЕЙН ЕАД - Bulgaria");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("arsov » Фирма тест за Рутиране по ДОМЕЙН ЕАД - Bulgaria (Фирма)"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Msg4\n?#EML*??? Тест за рутиране по ДОМЕЙНtest007@worktest.bgerp.comДнес ??:??1Днес"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=#EML*??? Тест за рутиране по ДОМЕЙН");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Входящ имейл #Msg4 (Приключено)"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Относно:?#EML*??? Тест за рутиране по ДОМЕЙН?До:?team@selenium.bgerp.com?От:?test007@worktest.bgerp.com"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Тест за рутиране по ДОМЕЙН????Здравейте,?????Надяваме се искрено, че рутирането ще работи съгласно заданието - писмото би трябвало да създаде папка на фирма \"Фирма тест за Рутиране по ДОМЕЙН ЕАД\" и да влезе в нея!????И само за тест <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]????Сърдечни поздрави,??Тестер Тестерсен??Фирма тест за Рутиране по Визитка ЕАД??5000 В. Търново??ул. ПАТРИАРХ ЕВТИМИЙ, № 3??Тел.: 062 / 611 515??Факс: 062 / 611 515*www.worktest.bgerp.com"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Документи");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Имейли");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Рутиране");
$this->waitForPageToLoad("30000");
try {
$this->assertTrue($this->isTextPresent("10fromdimko@worktest.bgerp.comcompany:5"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("11domainworktest.bgerp.comcompany:5"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// визитката създава правила за рутиране по From и по Domain от адреса в нея
try {
$this->assertTrue($this->isTextPresent("12fromtest007@worktest.bgerp.comdocument:8"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// писмото създава правило по From от адреса на изпращача
// рутиране по ДОМЕЙН от визитка е ОК!!!!

// тест рутиране по From от Визитка до "общ" адрес - изпращане на писмо от база worktest от адрес, за който тук имаме визитка на лице, прикачено към фирма - би трябвало да създаде папка на фирмата и да влезе вътре
// създаваме визитка на лице, прикачено към фирма, която няма направена папка
$this->click("link=Визитник");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Контакти");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Фирми");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("id=btnAdd");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("name=name", "Фирма Рутиране по FROM от Визитка ЕАД");
$this->click("id=groupList_6");
$this->click("id=groupList_1");
$this->click("id=lists_7");
$this->click("name=Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Лица");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("id=btnAdd");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->select("name=salutation", "label=Г-н");
$this->type("name=name", "Тестер Тестерсен");
$this->type("name=place", "База worktest");
$this->select("name=buzCompanyId", "label=Фирма Рутиране по FROM от Визитка ЕАД");
$this->type("name=buzEmail", "test@worktest.bgerp.com");
$this->click("id=groupList_6");
$this->click("id=groupList_1");
$this->click("id=lists_7");
$this->click("id=lists_7");
$this->click("name=Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Фирми");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Фирма Рутиране по FROM от Визитка ЕАД"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Лица");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Тестер ТестерсенФирма Рутиране по FROM от Визитка ЕАД"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("База Worktest"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//a[@id='edt3']/img");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("test@worktest.bgerp.com", $this->getValue("name=buzEmail"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Отказ']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// база  workTest
// изпращаме писмото от база worktest
$this->open("http://cloud.bgerp.com/workTest/");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("name=nick", "test");
$this->type("name=password", "test");
$this->click("xpath=(//input[@name='Cmd[default]'])[2]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Документи");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Общи");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Папки");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=test@worktest.bgerp.com");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("id=btnAdd");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Изходящ имейл']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("name=subject", "Тест за рутиране по FROM от Визитка");
$this->type("id=autoElement1", "Здравейте, \n\nНадяваме се искрено, че рутирането ще работи съгласно заданието - писмото би трябвало да създаде папка на фирма \"Фирма Рутиране по FROM от Визитка ЕАД\" и да влезе в нея!\n\nСърдечни поздрави,\nТестер Тестерсен\nФирма Рутиране по FROM от Визитка ЕАД\n5000 В. Търново\nул. ПАТРИАРХ ЕВТИМИЙ, № 3\nТел.: 062 / 611 515\nФакс: 062 / 611 515\nwww.worktest.bgerp.com");
$this->click("name=Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Активиране']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Изпращане']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("name=emailsTo", "team@selenium.bgerp.com");
$this->click("id=save");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Успешно изпратено до: team@selenium.bgerp.com"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("1 изпращане"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("id=btnDelete");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->assertTrue((bool)preg_match('/^Наистина ли желаете да оттеглите документа[\s\S]$/',$this->getConfirmation()));
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// в база Selenium
$this->open("http://cloud.bgerp.com/Selenium/core_Cron/ProcessRun/9_45c4/?forced=yes");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error:"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error!"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->open("http://cloud.bgerp.com/Selenium/");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Известия*Днес*??:??Отворени теми в \"Фирма Рутиране по FROM от Визитка ЕАД - Bulgaria\""));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Документи");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Общи");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Папки");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// ОК е!! отиваше в новосъздадена папка Тестер Тестерсен, а би трябвало да е в папка на фирмата "Фирма тест за Рутиране по Визитка ЕАД", чийто представител е Тестерсен
try {
$this->assertTrue($this->isTextPresent("Фирма Рутиране по FROM от Визитка ЕАД - BulgariaФирмаArsov1???1Днес"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Фирма Рутиране по FROM от Визитка ЕАД - Bulgaria");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("arsov » Фирма Рутиране по FROM от Визитка ЕАД - Bulgaria (Фирма)"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Msg5\n?#EML*??? Тест за рутиране по FROM от Визиткаtest@worktest.bgerp.comДнес ??:??1Днес"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=#EML*??? Тест за рутиране по FROM от Визитка");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Входящ имейл #Msg5 (Приключено)"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Относно:?#EML*??? Тест за рутиране по FROM от Визитка?До:?team@selenium.bgerp.com?От:?test@worktest.bgerp.com"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Тест за рутиране по FROM от Визитка????Здравейте,?????Надяваме се искрено, че рутирането ще работи съгласно заданието - писмото би трябвало да създаде папка на фирма \"Фирма Рутиране по FROM от Визитка ЕАД\" и да влезе в нея!????Сърдечни поздрави,??Тестер Тестерсен??Фирма Рутиране по FROM от Визитка ЕАД??5000 В. Търново??ул. ПАТРИАРХ ЕВТИМИЙ, № 3??Тел.: 062 / 611 515??Факс: 062 / 611 515*www.worktest.bgerp.com"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}

// рутиране по FromTo
// база workTest
$this->open("http://cloud.bgerp.com/workTest/");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Документи");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Общи");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Папки");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=test@worktest.bgerp.com");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("id=btnAdd");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Изходящ имейл']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("name=subject", "Тест за рутиране по FromTo и по То - писмо 2 вече до персонален адрес");
$this->type("id=autoElement1", "Здравейте, \n\nТова е нашият втори тест от тази база - този път писмото би следвало да НЕ последва първото, което изпратихме от този имейл (от тази база) и да отиде в папката на конкретния получател (по правилото То, т.к. FromTo няма да сработи - това е първи мейл от този изпращач до този получател с персонален адрес), въпреки че вече имаме изпратено от същия адрес test@, но до общия team@ - където сработва правилото From по визитка!\n\nСърдечни поздрави,\nТестер Тестерсен\nФирма тест за Рутиране по Визитка ЕАД\n5000 В. Търново\nул. ПАТРИАРХ ЕВТИМИЙ, № 3\nТел.: 062 / 611 515\nФакс: 062 / 611 515\nwww.printed-bags.net");
$this->click("name=Cmd[sending]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("За да изпратите имейла, трябва да попълните полето Адресант->Имейл."));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("id=autoElement2", "arsov@selenium.bgerp.com");
$this->click("name=Cmd[sending]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Успешно изпратено до: arsov@selenium.bgerp.com"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("1 изпращане"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("id=btnDelete");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->assertTrue((bool)preg_match('/^Наистина ли желаете да оттеглите документа[\s\S]$/',$this->getConfirmation()));
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// връщаме се в Селениум - ръчен даунлоуд на писмата
$this->open("http://cloud.bgerp.com/Selenium/core_Cron/ProcessRun/9_45c4/?forced=yes");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error:"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error!"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->open("http://cloud.bgerp.com/Selenium/");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Известия*Днес*??:??Отворени теми в \"arsov@selenium.bgerp.com\""));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Отворени теми в \"arsov@selenium.bgerp.com\"");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("arsov » arsov@selenium.bgerp.com (Е-кутия)"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// ОК - отива в папката на потребителя, 1-во писмо е по FromTo и се задейства То
try {
$this->assertTrue($this->isTextPresent("Msg6\n?#EML*??? Тест за рутиране по Fr ... писмо 2 вече до персонален адресtest@worktest.bgerp.comДнес ??:??1Днес"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Имейли");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Рутиране");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("13fromtest@worktest.bgerp.comcompany:6"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// визитката е създала правило за рутиране по from
try {
$this->assertTrue($this->isTextPresent("11domainworktest.bgerp.comcompany:5"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// остава създаденото най-рано правило за domain
try {
$this->assertTrue($this->isTextPresent("14fromTotest@worktest.bgerp.com|arsov@selenium.bgerp.comdocument:10"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// писмото създава правило за рутиране по fromTo тук вече имаме конкретен (персонален) адрес на получател (но оригинално беше № 14 правилото?!!?)

// отговор на мейла - за да отвори вече затворена (оттеглена) нишка
$this->click("link=Документи");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Общи");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=arsov@selenium.bgerp.com");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=#EML*??? Тест за рутиране по Fr ... писмо 2 вече до персонален адрес");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Отговор']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("id=autoElement1", "Здравейте Г-н Тестер Тестерсен, \n\nБлагодаря за имейла Ви.\nДо тук рутирането работи добре!\n\nСърдечни поздрави,\nСтефан Арсов\nМоята Фирма ООД");
$this->click("name=Cmd[sending]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Успешно изпратено до: test@worktest.bgerp.com"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("1 изпращане"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// проверка в workTest дали нишката се е отворила
$this->open("http://cloud.bgerp.com/workTest/core_Cron/ProcessRun/24_1ff1/?forced=yes");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error:"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error!"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->open("http://cloud.bgerp.com/workTest/");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Известия*Днес ??:??Отворени теми в \"test@worktest.bgerp.com\""));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Отворени теми в \"test@worktest.bgerp.com\"");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Тест за рутиране по FromTo и по То - писмо 2 вече до персонален адрес");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Изходящ имейл #Eml* (Оттеглено)"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Входящ имейл #Msg* (Приключено)"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// ОК - нишката е възстановена и отворена
$this->click("link=test@worktest.bgerp.com");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// оттегляме нишката, за да не се получи объркване при следващите тестове
$this->click("//td/input");
$this->click("id=with_selected");
$this->waitForPageToLoad("30000");
try {
$this->assertTrue($this->isTextPresent("Действия с избраните редове:"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Изтриване (1)']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("!!! Беше изтрит 1 запис !!!"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Днес"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// довърши оттеглянето на нишката (да не е "Изтриване") след решаването на Issue #330 за този проблем

// рутиране по Държава
$this->open("http://www.mail.ru/");
$this->type("id=mailbox__login", "bgERPtest");
$this->type("id=mailbox__password", "qwertytest");
$this->click("id=mailbox__auth__button");
$this->waitForPageToLoad("30000");
$this->click("id=HeaderBtnSentMsg");
$this->waitForPageToLoad("30000");
sleep(5);
$this->type("id=sentmsgab_compose_to", "team <team@selenium.bgerp.com>,");
$this->type("id=sentmsgab_compose_subj", "Test for routing by Country");
$this->clickAt("link=Просто текст", "");
$this->type("id=sentmsgcomposeEditor", "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.\n\n\nIt is a long established fact that a reader will be distracted by the readable content of a page when looking at its layout. The point of using Lorem Ipsum is that it has a more-or-less normal distribution of letters, as opposed to using 'Content here, content here', making it look like readable English. Many desktop publishing packages and web page editors now use Lorem Ipsum as their default model text, and a search for 'lorem ipsum' will uncover many web sites still in their infancy. Various versions have evolved over the years, sometimes by accident, sometimes on purpose (injected humour and the like).\n \n\nContrary to popular belief, Lorem Ipsum is not simply random text. It has roots in a piece of classical Latin literature from 45 BC, making it over 2000 years old. Richard McClintock, a Latin professor at Hampden-Sydney College in Virginia, looked up one of the more obscure Latin words, consectetur, from a Lorem Ipsum passage, and going through the cites of the word in classical literature, discovered the undoubtable source. Lorem Ipsum comes from sections 1.10.32 and 1.10.33 of \"de Finibus Bonorum et Malorum\" (The Extremes of Good and Evil) by Cicero, written in 45 BC. This book is a treatise on the theory of ethics, very popular during the Renaissance. The first line of Lorem Ipsum, \"Lorem ipsum dolor sit amet..\", comes from a line in section 1.10.32.\n\nThe standard chunk of Lorem Ipsum used since the 1500s is reproduced below for those interested. Sections 1.10.32 and 1.10.33 from \"de Finibus Bonorum et Malorum\" by Cicero are also reproduced in their exact original form, accompanied by English versions from the 1914 translation by H. Rackham.\n\n\nThere are many variations of passages of Lorem Ipsum available, but the majority have suffered alteration in some form, by injected humour, or randomised words which don't look even slightly believable. If you are going to use a passage of Lorem Ipsum, you need to be sure there isn't anything embarrassing hidden in the middle of text. All the Lorem Ipsum generators on the Internet tend to repeat predefined chunks as necessary, making this the first true generator on the Internet. It uses a dictionary of over 200 Latin words, combined with a handful of model sentence structures, to generate Lorem Ipsum which looks reasonable. The generated Lorem Ipsum is therefore always free from repetition, injected humour, or non-characteristic words etc.\n\n\n--\nСовременная мобильная почта - для смартфонов и телефонов.\nОцените мобильный m.mail.ru с Вашего телефона");
$this->click("link=Отправить");
$this->waitForPageToLoad("30000");
$this->click("link=Отправленные");
$this->waitForPageToLoad("30000");
$this->click("link=Выделить все письма");
$this->waitForPageToLoad("30000");
$this->click("link=Удалить");
$this->waitForPageToLoad("30000");
$this->click("link=выход");
$this->waitForPageToLoad("30000");
sleep(12);
$this->open("http://cloud.bgerp.com/Selenium/core_Cron/ProcessRun/9_45c4/?forced=yes");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error:"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error!"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->open("http://cloud.bgerp.com/Selenium/");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Известия*Днес*??:??Отворени теми в \"Unsorted - Bulgaria\""));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Отворени теми в \"Unsorted - Bulgaria\"");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("arsov » Unsorted - Bulgaria (Кюп)"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Msg7Test for routing by CountryBgERP TestTeam Днес"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}

// рутиране по потребителски правила - текст в събджекта и в бодито
$this->open("http://cloud.bgerp.com/Selenium/");
$this->click("link=Документи");
$this->waitForPageToLoad("30000");
$this->click("link=Имейли");
$this->waitForPageToLoad("30000");
$this->click("link=Рутиране");
$this->waitForPageToLoad("30000");
$this->click("link=Ръчно (филтри)");
$this->waitForPageToLoad("30000");
$this->click("id=btnAdd");
$this->waitForPageToLoad("30000");
$this->type("name=email", "Шаблон рутер Изпращач <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->type("name=subject", "Шаблон рутер Относно <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->type("name=body", "Шаблон рутер Текст <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->select("name=action", "label=В папка");
$this->select("name=folderId", "label=Фирма име <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#] - Фирма Град<Font Color=Red>!redbug!</font> \"&lt;&#9829;'[#title#]");
$this->type("name=note", "Забележка в Потребителско правило за рутиране <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->click("name=Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertTrue($this->isTextPresent("1??Шаблон рутер Изпращач <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]Шаблон рутер Относно"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Шаблон рутер Относно <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]Шаблон рутер Текст"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Шаблон рутер Текст <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]В папка"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("В папкаФирма име <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#] - Фирма Град<Font Color=Red>!redbug!</font> \"&lt;&#9829;'[#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("[#title#]Забележка в Потребителско правило за рутиране <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//a[@id='edt1']/img");
$this->waitForPageToLoad("30000");
try {
$this->assertTrue($this->isTextPresent("Редактиране на запис в \"Потребителски правила за рутиране\""));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Шаблон рутер Изпращач <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]", $this->getValue("name=email"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Шаблон рутер Относно <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]", $this->getValue("name=subject"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Шаблон рутер Текст <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]", $this->getValue("name=body"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Фирма име <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#] - Фирма Град<Font Color=Red>!redbug!</font> \"&lt;&#9829;'[#title#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertEquals("Забележка в Потребителско правило за рутиране <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]", $this->getValue("name=note"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Отказ']");
$this->waitForPageToLoad("30000");
$this->click("id=btnAdd");
$this->waitForPageToLoad("30000");
$this->type("name=subject", "Шаблон рутер Относно <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->type("name=body", "Шаблон рутер Текст <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->select("name=action", "label=В папка");
$this->select("name=folderId", "label=Фирма име <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#] - Фирма Град<Font Color=Red>!redbug!</font> \"&lt;&#9829;'[#title#]");
$this->type("name=note", "Тест за рутиране по \"Потребителско правило\" - текст в събджекта и боди-то");
$this->click("name=Cmd[save]");
$this->waitForPageToLoad("30000");

// първо писмо - потребителския рутер трябва да сработи и да отиде в папката на фирмата с HTML стринга, НО да НЕ създаде правило FromTo
// база workTest - изпращаме писмата отделно, т.к. заедно при даунлоуда в Селениум се разменят и обърква теста - може да не се получи желания "кофти" вариант - първо да сработи потребителския рутер, за да е сигурно че НЕ създава правила за стандартния
$this->open("http://cloud.bgerp.com/workTest/");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Документи");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Общи");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Папки");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=test@worktest.bgerp.com");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("id=btnAdd");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Изходящ имейл']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("name=subject", "Относно 1 съдържа текста: Шаблон рутер Относно <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]   който търсим");
$this->type("id=autoElement1", "Здравейте, \n\nТова е нашият ПЪРВИ тест за рутиране по Потребителски правила - текстът който търсим в бодито е   Шаблон рутер Текст <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]     , та да видим - трябва да отиде в избраната в шаблона папка - Фирма с HTML стринга!\n\nСърдечни поздрави,\nТестер Тестерсен\nФирма тест за Рутиране по Визитка ЕАД\n5000 В. Търново\nул. ПАТРИАРХ ЕВТИМИЙ, № 3\nТел.: 062 / 611 515\nФакс: 062 / 611 515\nwww.printed-bags.net");
$this->type("name=email", "arsov@selenium.bgerp.com");
$this->click("name=Cmd[sending]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Успешно изпратено до: arsov@selenium.bgerp.com"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("1 изпращане"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// изтриваме нишката, за да не се трупат
$this->click("id=btnDelete");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->assertTrue((bool)preg_match('/^Наистина ли желаете да оттеглите документа[\s\S]$/',$this->getConfirmation()));
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
sleep(2);
try {
$this->assertFalse($this->isTextPresent("Днес"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// връщаме се в Селениум - ръчен даунлоуд на писмата
$this->open("http://cloud.bgerp.com/Selenium/core_Cron/ProcessRun/9_45c4/?forced=yes");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error:"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error!"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->open("http://cloud.bgerp.com/Selenium/");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Известия*Днес*??:??Отворени теми в \"Фирма име <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#] - Фирма Град<Font Color=Red>!redbug"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Отворени теми в \"Фирма име <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#] - Фирма Град<Font Color=Red>!redbug");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("arsov » Фирма име <FONT CO ... &#9829;'[#title#] (Фирма)"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// ОК - отива в папката на Фирма име HTML - задейства се потребителския рутер
$this->click("link=#EML*??? Относно 1 съдържа текс ... 9829; ' [#title#] който търсим");
$this->waitForPageToLoad("30000");
try {
$this->assertTrue($this->isTextPresent("arsov » Фирма име <FONT CO ... &#9829;'[#title#] (Фирма) » #EML*??? Относно 1 съдържа текс ... 9829; ' [#title#] който търсим"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->selectWindow("name=Selenium");
try {
$this->assertTrue($this->isTextPresent("Входящ имейл #Msg8 (Приключено)"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Относно: #EML*??? Относно 1 съдържа текста: Шаблон рутер Относно <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#] който търсим"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("До: arsov@selenium.bgerp.com?От: test@worktest.bgerp.com"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Относно 1 съдържа текста: Шаблон рутер Относно <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#] който търсим????Здравейте,?????Това е нашият ПЪРВИ тест за рутиране по Потребителски правила - текстът който търсим в бодито е Шаблон рутер Текст <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#] , та да видим - трябва да отиде в избраната в шаблона папка - Фирма с HTML стринга!"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Имейли");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Рутиране");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("14fromTotest@worktest.bgerp.com|arsov@selenium.bgerp.comdocument:10"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// FromTo е от по-стар документ - настоящият, прихванат от потребителския рутер, не го е променило

// в база workTest - изпращаме писмата
$this->open("http://cloud.bgerp.com/workTest/");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Документи");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Общи");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Папки");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=test@worktest.bgerp.com");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// второ писмо само с едното от двете полета - потребителския рутер би трябвало да не сработи и да не е създал правило FromTo, което би изпратило това писмо в същата папка като предното, и да отиде в папка arsov@....
$this->click("id=btnAdd");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("//input[@value='Изходящ имейл']");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("name=subject", "Относно 2 съдържа, но НЕ ТОЧНО текста: Шаблон р-р Относно <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]   който търсим");
$this->type("id=autoElement1", "Здравейте, \n\nТова е нашият ВТОРИ тест за рутиране по Потребителски правила - текстът който търсим в бодито е   Шаблон рутер Текст <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]   и е ОК, но текстът в Относно: е променен - та да видим - трябва потребителското рутиране да не сработи и да не е създало правило FromTo, по което настоящето писмо да последва предишното, и по стандартното рутиране да отиде в папката на получателя arsov@... по fromTo - вече имаме получено в нея от по-рано писмо със същите получател и изпращач!\n\nСърдечни поздрави,\nТестер Тестерсен\nФирма тест за Рутиране по Визитка ЕАД\n5000 В. Търново\nул. ПАТРИАРХ ЕВТИМИЙ, № 3\nТел.: 062 / 611 515\nФакс: 062 / 611 515\nwww.printed-bags.net");
$this->type("name=email", "arsov@selenium.bgerp.com");
$this->click("name=Cmd[sending]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Успешно изпратено до: arsov@selenium.bgerp.com"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("1 изпращане"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// изтриваме нишката, за да не се трупат
$this->click("id=btnDelete");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->assertTrue((bool)preg_match('/^Наистина ли желаете да оттеглите документа[\s\S]$/',$this->getConfirmation()));
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
sleep(2);
try {
$this->assertFalse($this->isTextPresent("Днес"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=изход:test");
$this->waitForPageToLoad("30000");
// връщаме се в Селениум - ръчен даунлоуд на писмата
$this->open("http://cloud.bgerp.com/Selenium/core_Cron/ProcessRun/9_45c4/?forced=yes");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error:"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error!"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->open("http://cloud.bgerp.com/Selenium/");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Известия*Днес ??:??Отворени теми в \"arsov@selenium.bgerp.com\""));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Отворени теми в \"arsov@selenium.bgerp.com\"");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=#EML*??? Относно 2 съдържа, но ... 9829; ' [#title#] който търсим");
$this->waitForPageToLoad("30000");
try {
$this->assertTrue($this->isTextPresent("arsov » arsov@selenium.bgerp.com (Е-кутия) » #EML*??? Относно 2 съдържа, но ... 9829; ' [#title#] който търсим"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Входящ имейл #Msg9 (Приключено)"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Относно: #EML67ASK Относно 2 съдържа, но НЕ ТОЧНО текста: Шаблон р-р Относно <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#] който търсим"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("До: arsov@selenium.bgerp.com?От: test@worktest.bgerp.com"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// ОК! в папка arsov@.... e !
$this->click("link=Имейли");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Рутиране");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("14fromTotest@worktest.bgerp.com|arsov@selenium.bgerp.comdocument:14"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
// FromTo е обновено от настоящия (последния) документ

// Нотификации
$this->click("link=Документи");
$this->waitForPageToLoad("30000");
$this->click("link=Общи");
$this->waitForPageToLoad("30000");
$this->click("link=Папки");
$this->waitForPageToLoad("30000");
$this->click("link=Тестова Фирма АД - Bulgaria");
$this->waitForPageToLoad("30000");
$this->click("id=btnAdd");
$this->waitForPageToLoad("30000");
$this->click("//input[@value='Коментар']");
$this->waitForPageToLoad("30000");
$this->type("name=subject", "Тест за НОТИФИЦИРАНЕ - нов запис - споделен");
$this->type("id=autoElement1", "Това е коментар за тестване на нотификациите - създаваме нов запис, споделен с друг потребител - би трябвало споделеният потребител да получи нотификация за споделен документ в този тред....");
$this->click("id=sharedUsers_3");
$this->click("id=activate");
$this->waitForPageToLoad("30000");
$this->click("link=изход:arsov");
$this->waitForPageToLoad("30000");
$this->type("name=nick", "officer");
$this->type("name=password", "officer");
$this->click("xpath=(//input[@name='Cmd[default]'])[2]");
$this->waitForPageToLoad("30000");
try {
$this->assertTrue($this->isTextPresent("Известия*Днес ??:??Arsov сподели коментар в \"Тест за НОТИФИЦИРАНЕ - нов запис - споделен\""));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Arsov сподели коментар в \"Тест за НОТИФИЦИРАНЕ - нов запис - споделен\"");
$this->waitForPageToLoad("30000");
try {
$this->assertTrue($this->isTextPresent("arsov » Тестова Фирма АД - Bulgaria (Фирма) » Тест за НОТИФИЦИРАНЕ - нов запис - споделен"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Коментар #C1 (Активирано)"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Тест за НОТИФИЦИРАНЕ - нов запис - споделен"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Папки");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Тестова Фирма АД - Bulgaria"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->select("name=users", "label=Стефан Арсов");
$this->click("id=filter");
$this->waitForPageToLoad("30000");
$this->click("link=Фирма Рутиране по FROM от Визитка ЕАД - Bulgaria");
$this->waitForPageToLoad("30000");
$this->click("link=#EML64TXO Тест за рутиране по FROM от Визитка");
$this->waitForPageToLoad("30000");

$this->break();

// crm Календар - да се преработи и премести след завършването на Календар-а
$this->click("link=Календар");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("btnAdd");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("date", "<b>!ВUG!</b>\"&lt;&#9829;'[#title#]");
$this->type("type", "Тип<b>В</b>\"&lt;&#9829;'[#titl#]");
$this->select("classId", "label=Данни за празниците в календара");
$this->type("objectId", "Обект календ: <FONT COLOR=RED>!!! red BUG !!!</FONT> \" &lt; &#9829; ' [#title#]");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Дата'!\nНе е в допустимите формати, като например:"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Обект'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("autoElement1", "16-02-2012");
$this->type("objectId", "<b>!ВUG!</b>\"[#title#]");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Обект'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("autoElement1", "&lt;&#9829;'");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Обект'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("autoElement1", "Обект");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Некоректна стойност на полето 'Обект'!\nНедопустими символи в число/израз"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("autoElement1", "123");
$this->click("Cmd[save]");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Тип<b>В</b>\"&lt;&#9829;'[#titl#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->click("link=Календар");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
$this->type("autoElement2", "12-02-2012");
$this->click("filter");
$this->waitForPageToLoad("30000");
try {
$this->assertFalse($this->isTextPresent("Warning"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Error"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("&nbsp;"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertFalse($this->isTextPresent("Strict Standard"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}
try {
$this->assertTrue($this->isTextPresent("Тип<b>В</b>\"&lt;&#9829;'[#titl#]"));
} catch (PHPUnit_Framework_AssertionFailedError $e) {
array_push($this->verificationErrors, $e->toString());
}


  }
}
?>
