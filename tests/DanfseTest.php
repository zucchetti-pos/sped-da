<?php

namespace NFePHP\DA\Tests\NFSe;

use NFePHP\DA\NFSe\Danfse;

class DanfseTest extends \PHPUnit\Framework\TestCase
{

    /**
     * @test
     */

    public function printDanfse()
    {
        $pathBase = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'fixtures' . DIRECTORY_SEPARATOR;
        $pathXml =   $pathBase . 'xml' . DIRECTORY_SEPARATOR . 'nfse.xml';
        $pathPdf =   $pathBase . 'pdf' . DIRECTORY_SEPARATOR . 'nfse.pdf';
        $pathLogo =   $pathBase . 'logo' . DIRECTORY_SEPARATOR . 'logo.jpeg';

        $xml = file_get_contents($pathXml);
        $danfse = new Danfse($xml);
        $danfse->printParameters('P', 'A4', 7, 7);

        if ($logoContent = file_get_contents($pathLogo)) {
            $logoImage = 'data://text/plain;base64,' . base64_encode($logoContent);
            $danfse->logoParameters($logoImage, null, false);
        }

        $pdf = $danfse->render();
        file_put_contents($pathPdf, $pdf);
    }
}
