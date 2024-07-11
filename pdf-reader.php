<?php

include "vendor/autoload.php";

ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ERROR);

use NcJoes\PopplerPhp\Config;
use NcJoes\PopplerPhp\PdfToHtml;
use voku\helper\HtmlDomParser;

?>
    <div>
        <h3>Please note for testing purpose, only first 5 pdf page will processed.</h3>
        <form method="POST" enctype="multipart/form-data">
            <input style="padding:5px;border:1px solid #333;" type="file" name="pdf"/>
            <input style="padding:8px;border:1px solid #333;"  type="SUBMIT"/>
        </form>
    </div>
<?php
if ($_FILES && isset($_FILES['pdf']) && $file = $_FILES['pdf']) {
    $upload = false;
    $target = __DIR__ . '/files/source.pdf';
    if ($file['type'] !== 'application/pdf') {
        echo '<h3>Must be PDF file</h3>';
    } elseif ($file["size"] > 5000000) {
        echo "<h3>File is too large. Please use file lower than 5MB</h3>";
    } else {
        if (move_uploaded_file($file['tmp_name'], $target)) {
            $upload = true;
        } else {
            echo '<h3>Could not upload file</h3>';
        }
    }

    if ($upload) {
        // // set Poppler utils binary location
        // Config::setBinDirectory('C:\Users\zoids\Downloads\Release-24.02.0-0\poppler-24.02.0\Library\bin');
        //
        // // set output directory
        // Config::setOutputDirectory(__DIR__ . '/files');
        //
        // $pdfToHtml = new PdfToHtml($target);
        // // $pdfToHtml->setZoomRatio(1.8);
        // // $pdfToHtml->exchangePdfLinks();
        // $pdfToHtml->startFromPage(1)->stopAtPage(5);
        // $pdfToHtml->generateSingleDocument();
        // $pdfToHtml->noFrames();
        // // $pdfToHtml->oddPagesOnly();
        // $pdfToHtml->outputToConsole();
        // $html = $pdfToHtml->generate();
        // $entity = htmlentities($html);
        //
        // // echo $html; die;
        //
        //
        // $dom = HtmlDomParser::str_get_html(strip_tags($html, '<p>'));
        // $elements = $dom->findMulti('p');
        //
        // $rows = [];
        // foreach ($elements as $i => $p) {
        //     $styles = [];
        //     foreach (explode(';', $p->getAttribute('style')) as $str) {
        //         list($key, $value) = explode(':', $str, 2);
        //         $styles[$key] = str_replace('px', '', strtolower($value));
        //     }
        //
        //     // $rows[$styles['top']][] = [
        //     //     'inner' => $p->innerHtml(),
        //     //     'style' => $p->getAttribute('style'),
        //     // ];
        //     $rows[$styles['top']][] = $p->innerHtml();
        // }
        // echo '<h3>Your PDF content: </h3>';
        // foreach ($rows as $row) {
        //     echo '<span style="display: block; font-weight: bolder;">' . implode(', ', $row) . '</span><br/>';
        // }

        // if (file_exists($img = __DIR__ . '/files/source001.png')) unlink($img);
        // Parse PDF file and build necessary objects.
        $parser = new \Smalot\PdfParser\Parser();

        $pdf = $parser->parseFile($target);

        $data = $pdf->getPages()[0]->getDataTm();
        $rows = [];
        $puci = [];
        foreach ($data as $index => $row) {
            $rows[$row[0][5]][] = $row;
            $puci[$row[0][5]][] = $row[1];
        }

        foreach ($puci as $row => $items) {
            echo implode('', $items) . '<br/>';
        }
        echo '<pre>' . print_r($rows, true)  .'</pre>';


        unlink($target);
    }
}
