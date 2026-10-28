<?php

namespace NFePHP\DA\Tests\NFe;

use NFePHP\DA\NFe\Danfce;
use NFePHP\DA\Tests\Utils;
use PHPUnit\Framework\TestCase;

class DanfceProtocoloTest extends TestCase
{
    /**
     * @dataProvider protocolos
     */
    public function test_protocolo_de_autorizacao_e_impresso_integralmente(string $protocolo): void
    {
        $xml = file_get_contents(TEST_FIXTURES . 'xml/nfe.xml');
        $xml = str_replace('<mod>55</mod>', '<mod>65</mod>', $xml);
        $xml = preg_replace('#<nProt>\d+</nProt>#', "<nProt>{$protocolo}</nProt>", $xml);

        $danfce = new Danfce($xml);
        $pdf = $danfce->render();

        $this->assertTrue(Utils::pdfContemTexto($pdf, $protocolo));
    }

    public static function protocolos(): array
    {
        return [
            'protocolo com 15 digitos' => ['135259806782559'],
            'protocolo com 17 digitos' => ['13526007376365687'],
        ];
    }
}
