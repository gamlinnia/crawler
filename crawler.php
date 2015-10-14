#!/usr/bin/php -q
<?php

date_default_timezone_set('Asia/Taipei');
require_once 'phpQuery/phpQuery/phpQuery.php';
require_once 'PHPExcel/Classes/PHPExcel.php';
require_once 'functions.php';
$debug = true;

$excelArray = parseXlsxIntoArray('Crawler_Format.xlsx');

$arrayToExcel = array();
foreach ($excelArray as $row) {
    $url = $row['Primary SKU URL'];
    $content = file_get_contents($url);
    $doc = phpQuery::newDocumentHTML($content);
    $rowResponse = array(
        'Primary SKU #' => $row['Primary SKU #'],
        'Missing Items' => ''
    );

    switch ($row['Website']) {
        case 'amazon' :
            $alsoBoughtAjaxObject = pq('#purchase-sims-feature', $doc)->find('div')->filter(':first')->attr('data-a-carousel-options');
            $alsoBoughtAjaxArray = json_decode($alsoBoughtAjaxObject, true);
            $amazonAjaxBaseUrl = 'http://www.amazon.com';

            foreach ($row as $title => $column) {
                if (preg_match('/^secondary.*SKU #$/i', $title, $match)) {
                    if ($column && !in_array($column, $alsoBoughtAjaxArray['ajax']['id_list'])) {
                        ($rowResponse['Missing Items'] != '') ? $rowResponse['Missing Items'] .= ',' . $column : $rowResponse['Missing Items'] = $column;
                    }
                }
            }
//            $parsedUrl = parseUrl($amazonAjaxBaseUrl, $alsoBoughtAjaxArray['ajax'], 'amazon');
//            $parsedUrl = addAsinsParam($parsedUrl, $alsoBoughtAjaxArray['ajax']['id_list'], 5, 1);
//            echo $parsedUrl;
            break;
        case 'newegg' :
            $mayWeSuggest = pq('.wrapSideSell div', $doc);
            echo $mayWeSuggest->html() . PHP_EOL . PHP_EOL;
            foreach ($mayWeSuggest as $suggestDiv) {
                echo $suggestDiv->html() . PHP_EOL . PHP_EOL;
            }
            break;
    }
    if ($rowResponse['Missing Items'] == '') {
        $rowResponse['Missing Items'] = 'No Missing Item';
    }
    $arrayToExcel[] = $rowResponse;
}

if ($debug) {
    echo 'debug mode enabled' . PHP_EOL;
    return;
}

$fileName = date("Ymd_Hi") . '.xls';

$fileDir = 'report/';
if (!file_exists($fileDir)) {
    mkdir($fileDir);
}
exportArrayToXlsx($arrayToExcel, array(
    "filename" => $fileDir . $fileName,
    "title" => "Missing List"
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