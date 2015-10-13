#!/usr/bin/php -q
<?php

require_once 'phpQuery/phpQuery/phpQuery.php';
require_once 'PHPExcel/Classes/PHPExcel.php';

$url = 'http://www.amazon.com/EVGA-Continuous-Warranty-Supply-100-W1-0500-KR/dp/B00H33SFJU/ref=pd_sim_147_7?ie=UTF8&refRID=0JJ2BPZ1FC6W65FS8Q37';

$content = file_get_contents($url);

$doc = phpQuery::newDocumentHTML($content);

$alsoBoughtAjaxObject = pq('#purchase-sims-feature', $doc)->find('div')->filter(':first')->attr('data-a-carousel-options');
$alsoBoughtAjaxArray = json_decode($alsoBoughtAjaxObject, true);

var_dump($alsoBoughtAjaxArray);

$amazonAjaxBaseUrl = 'http://www.amazon.com';

$parsedUrl = parseUrl($amazonAjaxBaseUrl, $alsoBoughtAjaxArray['ajax'], 'amazon');
$parsedUrl = addAsinsParam($parsedUrl, $alsoBoughtAjaxArray['ajax']['id_list'], 5, 1);
echo $parsedUrl;

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