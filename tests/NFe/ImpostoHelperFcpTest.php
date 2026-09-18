<?php

namespace NFePHP\DA\Tests\NFe;

use DOMDocument;
use NFePHP\DA\NFe\Danfe;
use NFePHP\DA\Legacy\Pdf;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ImpostoHelperFcpTest extends TestCase
{
    private function buildICMSTot(array $valores): \DOMElement
    {
        $dom = new DOMDocument();
        $icmsTot = $dom->createElement('ICMSTot');
        foreach ($valores as $tag => $valor) {
            $icmsTot->appendChild($dom->createElement($tag, (string) $valor));
        }
        $dom->appendChild($icmsTot);

        return $icmsTot;
    }

    private function buildDanfeWithIcmsTot(\DOMElement $icmsTot): Danfe
    {
        $reflection = new ReflectionClass(Danfe::class);
        /** @var Danfe $danfe */
        $danfe = $reflection->newInstanceWithoutConstructor();

        $icmsTotProp = $reflection->getProperty('ICMSTot');
        $icmsTotProp->setAccessible(true);
        $icmsTotProp->setValue($danfe, $icmsTot);

        $fontProp = $reflection->getParentClass()->getProperty('fontePadrao');
        $fontProp->setAccessible(true);
        $fontProp->setValue($danfe, 'Times');

        $pdfProp = $reflection->getParentClass()->getProperty('pdf');
        $pdfProp->setAccessible(true);
        $pdfProp->setValue($danfe, new class extends Pdf {
            public array $valoresImpressos = [];

            public function textBox(
                $x,
                $y,
                $w,
                $h,
                $text = '',
                $aFont = ['font' => 'Times', 'size' => 8, 'style' => ''],
                $vAlign = 'T',
                $hAlign = 'L',
                $border = true,
                $link = '',
                $force = true,
                $hmax = 0,
                $vOffSet = 0,
                $fill = false
            ) {
                if ($hAlign === 'R') {
                    $this->valoresImpressos[] = $text;
                }

                return $y;
            }
        });

        return $danfe;
    }

    private function callImpostoHelper(Danfe $danfe, string $campoImposto): string
    {
        $reflection = new ReflectionClass(Danfe::class);
        $method = $reflection->getMethod('impostoHelper');
        $method->setAccessible(true);
        $method->invoke($danfe, 0, 0, 10, 10, 'TITULO', $campoImposto);

        $pdfProp = $reflection->getParentClass()->getProperty('pdf');
        $pdfProp->setAccessible(true);
        $pdf = $pdfProp->getValue($danfe);

        return end($pdf->valoresImpressos);
    }

    public function test_valorDoIcms_naoDeveSomarVfcp(): void
    {
        $icmsTot = $this->buildICMSTot([
            'vICMS' => '112.15',
            'vFCP' => '18.69',
        ]);
        $danfe = $this->buildDanfeWithIcmsTot($icmsTot);

        $valorImpresso = $this->callImpostoHelper($danfe, 'vICMS');

        $this->assertSame('112,15', $valorImpresso);
    }

    public function test_valorDoIcmsSubst_naoDeveSomarVfcpst(): void
    {
        $icmsTot = $this->buildICMSTot([
            'vST' => '135.68',
            'vFCPST' => '22.61',
        ]);
        $danfe = $this->buildDanfeWithIcmsTot($icmsTot);

        $valorImpresso = $this->callImpostoHelper($danfe, 'vST');

        $this->assertSame('135,68', $valorImpresso);
    }

    public function test_valorDoIcms_semFcpNaNota_continuaFuncionando(): void
    {
        $icmsTot = $this->buildICMSTot([
            'vICMS' => '50.00',
        ]);
        $danfe = $this->buildDanfeWithIcmsTot($icmsTot);

        $valorImpresso = $this->callImpostoHelper($danfe, 'vICMS');

        $this->assertSame('50,00', $valorImpresso);
    }
}
