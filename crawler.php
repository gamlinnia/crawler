#!/usr/bin/php -q
<?php

require_once 'phpQuery/phpQuery/phpQuery.php';

$url = 'http://www.amazon.com/EVGA-Continuous-Warranty-Supply-100-W1-0500-KR/dp/B00H33SFJU/ref=pd_sim_147_7?ie=UTF8&refRID=0JJ2BPZ1FC6W65FS8Q37';

$content = file_get_contents($url);

$doc = phpQuery::newDocumentHTML($content);

$alsoBoughtAjaxObject = pq('div.data-a-carousel-options', $doc)->html();




var_dump($alsoBoughtAjaxObject);