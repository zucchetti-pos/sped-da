<?php

namespace NFePHP\DA\Tests\NFe;

use NFePHP\DA\NFe\Danfce;
use NFePHP\DA\Tests\Utils;
use PHPUnit\Framework\TestCase;

class DanfceTest extends TestCase
{
    public function test_preservaVariacaoDeGradeCompleta_quandoNomeDoProdutoEhLongo(): void
    {
        $xml = file_get_contents(TEST_FIXTURES . 'xml/nfe.xml');
        $xml = str_replace('<mod>55</mod>', '<mod>65</mod>', $xml);
        $xml = str_replace(
            '<xProd>FRIGIDEIRA RETA ALTA 20 SEM TPA - CEREJA</xProd>',
            '<xProd>' . str_repeat('Produto com nome bem grande ', 3) . 'Produto c... - Cor Azul / Tamanho GG</xProd>',
            $xml
        );

        $obj = new Danfce($xml);
        $pdf = $obj->render();

        $this->assertTrue(Utils::pdfContemTexto($pdf, 'Cor Azul'));
        $this->assertTrue(Utils::pdfContemTexto($pdf, 'Tamanho GG'));
    }

    public function test_mantemDescricaoNormal_quandoItemNaoTemGrade(): void
    {
        $xml = str_replace('<mod>55</mod>', '<mod>65</mod>', file_get_contents(TEST_FIXTURES . 'xml/nfe.xml'));

        $obj = new Danfce($xml);
        $pdf = $obj->render();

        $this->assertTrue(Utils::pdfContemTexto($pdf, 'FRIGIDEIRA RETA ALTA'));
    }
}
