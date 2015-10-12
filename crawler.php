#!/usr/bin/php -q
<?php

require_once 'phpQuery/phpQuery/phpQuery.php';

$url = 'http://www.amazon.com/EVGA-Continuous-Warranty-Supply-100-W1-0500-KR/dp/B00H33SFJU/ref=pd_sim_147_7?ie=UTF8&refRID=0JJ2BPZ1FC6W65FS8Q37';

$content = file_get_contents($url);

$doc = phpQuery::newDocumentHTML($content);

$alsoBoughtAjaxObject = pq('#purchase-sims-feature', $doc)->find('div')->filter(':first')->attr('data-a-carousel-options');
$alsoBoughtAjaxArray = json_decode($alsoBoughtAjaxObject, true);

var_dump($alsoBoughtAjaxArray);

$amazonAjaxBaseUrl = 'http://www.amazon.com/gp/p13n-shared/faceout-partial';

parseUrl($amazonAjaxBaseUrl, $alsoBoughtAjaxArray, 'amazon');


// var_dump($alsoBoughtAjaxObject->html());

function parseUrl ($baseUrl, $paramArray, $web) {

}
