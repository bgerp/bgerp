<?php


/**
 * Интерфейс за доставчици на JSON-LD възли
 *
 * Доставчиците получават подготвени данни от конкретен източник и връщат
 * масив от jsonld_Node обекти.
 */
class jsonld_ProviderIntf
{
    /**
     * Връща JSON-LD възлите за подадените данни
     *
     * @param mixed $data
     *
     * @return jsonld_Node[]
     */
    public function getJsonLdNodes($data)
    {
        return $this->class->getJsonLdNodes($data);
    }
}
