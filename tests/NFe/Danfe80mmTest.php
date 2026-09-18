<?php

namespace NFePHP\DA\Tests\NFe;

use NFePHP\DA\NFe\Danfe80mm;
use NFePHP\DA\Tests\Utils;
use PHPUnit\Framework\TestCase;

class Danfe80mmTest extends TestCase
{
    public function test_preservaVariacaoDeGradeCompleta_quandoNomeDoProdutoEhLongo(): void
    {
        $xml = str_replace(
            '<xProd>FRIGIDEIRA RETA ALTA 20 SEM TPA - CEREJA</xProd>',
            '<xProd>' . str_repeat('Produto com nome bem grande ', 4) . 'Curto - Cor Azul / Tamanho GG</xProd>',
            file_get_contents(TEST_FIXTURES . 'xml/nfe.xml')
        );

        $obj = new Danfe80mm($xml);
        $pdf = $obj->render();

        $this->assertTrue(Utils::pdfContemTexto($pdf, 'Cor Azul / Tamanho GG'));
    }

    public function test_mantemDescricaoNormal_quandoItemNaoTemGrade(): void
    {
        $xml = file_get_contents(TEST_FIXTURES . 'xml/nfe.xml');

        $obj = new Danfe80mm($xml);
        $pdf = $obj->render();

        $this->assertTrue(Utils::pdfContemTexto($pdf, 'FRIGIDEIRA RETA'));
    }
}
