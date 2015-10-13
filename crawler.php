#!/usr/bin/php -q
<?php

require_once 'phpQuery/phpQuery/phpQuery.php';
require_once 'PHPExcel/Classes/PHPExcel.php';
require_once 'functions.php';

$excelArray = parseXlsxIntoArray('Crawler_Format.xlsx');

foreach ($excelArray as $row) {
    $url = $row['Primary SKU URL'];
    $content = file_get_contents($url);
    $doc = phpQuery::newDocumentHTML($content);

    switch ($row['Website']) {
        case 'amazon' :
            $alsoBoughtAjaxObject = pq('#purchase-sims-feature', $doc)->find('div')->filter(':first')->attr('data-a-carousel-options');
            $alsoBoughtAjaxArray = json_decode($alsoBoughtAjaxObject, true);
            var_dump($alsoBoughtAjaxArray);
            $amazonAjaxBaseUrl = 'http://www.amazon.com';

            foreach ($row as $title => $column) {
                if (preg_match('/^secondary.*SKU #$/i', $title, $match)) {
                    if (in_array($column, $alsoBoughtAjaxArray['ajax']['id_list'])) {
                        echo $column . ' in the list' . PHP_EOL;
                    }
                }
            }
//            $parsedUrl = parseUrl($amazonAjaxBaseUrl, $alsoBoughtAjaxArray['ajax'], 'amazon');
//            $parsedUrl = addAsinsParam($parsedUrl, $alsoBoughtAjaxArray['ajax']['id_list'], 5, 1);
//            echo $parsedUrl;

            break;
    }
}

$arrayToExcel = array(array('abc' => 'response for abc', 'cde' => 'respnonse for cde'));

exportArrayToXlsx($arrayToExcel, array(
    "filename"=>"test.xls",
    "title"=>"Product List"
));

function parseUrl ($baseUrl, $paramArray, $webSite) {
    $response = $baseUrl;
    switch ($webSite) {
        case 'amazon' :
            $response = $response . $paramArray['url'];
            $neededAttr = array('featureId', 'reftagPrefix', 'widgetTemplateClass', 'imageHeight', 'faceoutTemplateClass', 'auiDeviceType', 'imageWidth', 'productDetailsTemplateClass', 'relatedRequestID');
            $count = 0;
            foreach ($paramArray['params'] as $attr => $attrValue) {
                if (in_array($attr, $neededAttr)) {
                    if ($count < 1) {
                        $response = $response . '?' . $attr . '=' . $attrValue;
                    } else {
                        $response = $response . '&' . $attr . '=' . $attrValue;
                    }
                    $count++;
                }
            }
            break;
    }
    return $response;
}

function addAsinsParam ($parsedUrl, $id_list, $count, $offset) {
    $parsedUrl .= '&count=' . $count . '&offset=' . $offset;
    $asins = '&asins=';
    for ($i = $count*($offset-1); $i < $count*$offset; $i++) {
        $asins .= $id_list[$i];
        if ($i < ($count*$offset) -1) {
            $asins .= ',';
        }
    }
    return $parsedUrl . $asins;
}