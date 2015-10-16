<?php

function currentTime () {
    $now = new DateTime(null, new DateTimeZone('UTC'));
    return $now->format('Y-m-d H:i:s');    /*MySQL datetime format*/
}

function attributeSetNameAndId ($nameOrId, $value) {
    /*$nameOrId = 'attributeSetName' or 'attributeSetId'*/
    $attributeSetCollection = Mage::getResourceModel('eav/entity_attribute_set_collection') ->load();
    foreach ($attributeSetCollection as $id => $attributeSet) {
        $entityTypeId = $attributeSet->getEntityTypeId();
        $name = $attributeSet->getAttributeSetName();
        switch ($nameOrId) {
            case 'attributeSetName' :
                if ($name == $value) {
                    return array(
                        'name' => $name,
                        'id' => $id,
                        'entityTypeId' => $entityTypeId
                    );
                }
                break;
            case 'attributeSetId' :
                if ((int)$id == (int)$value) {
                    return array(
                        'name' => $name,
                        'id' => $id,
                        'entityTypeId' => $entityTypeId
                    );
                }
                break;
        }
    }
    return null;
}

function getCountNumberOfProducts () {
    $productCollection = Mage::getModel('catalog/product')->getCollection();
    return count($productCollection);
}

function getProductInfoFromMagentoForExport ($pageSize, $pageNumber = 1, $noOutputAttr) {
    $productCollection = Mage::getModel('catalog/product')->getCollection()->addAttributeToSelect('*')
        ->setOrder('entity_id', 'ASC')->setPageSize($pageSize)->setCurPage($pageNumber);

    $response = array();
    foreach ($productCollection as $product) {
        $tempArray = array();
        foreach ($product->debug() as $attr => $attrValue) {
            if (!in_array($attr, $noOutputAttr)) {
                $tempArray[$attr] = $attrValue;
            }
        }

        $withDownloadableFile = false;
        $user_manuals=Mage::getModel('usermanuals/usermanuals')->getCollection()->addFieldToFilter('product_id',$product['entity_id']);
        if ( count($user_manuals) > 0 ) {
            $withDownloadableFile = true;
        }
        $drivers=Mage::getModel('drivers/drivers')->getCollection()->addFieldToFilter('product_id',$product['entity_id']);
        if ( count($drivers) > 0 ) {
            $withDownloadableFile = true;
        }
        $firmwares=Mage::getModel('firmware/firmware')->getCollection()->addFieldToFilter('product_id',$product['entity_id']);
        if ( count($drivers) > 0 ) {
            $withDownloadableFile = true;
        }
        if ($withDownloadableFile) {
            $tempArray['attachment'] = 'yes';
        }

        $response[] = $tempArray;
    }
    return $response;
}

// - The $exportArray is a 2-dimension array. (row & column)
// - Each row is a php Associative Array :  array("key"=>"value")
// - The first row is used to generate column names in excel, so it must has all possible keys.
//   (You may insert a dummy first row with all possible keys.)
// - Check function test_getDataFromDB() above for how to generate $exportArray.
function exportArrayToXlsx ($exportArray, $exportParam) {

    PHPExcel_Cell::setValueBinder( new PHPExcel_Cell_AdvancedValueBinder() );

    $objPHPExcel = new PHPExcel();

    // Set properties
    $objPHPExcel->getProperties()->setCreator($exportParam['title'])
        ->setLastModifiedBy($exportParam['title'])
        ->setTitle($exportParam['title'])
        ->setSubject($exportParam['title'])
        ->setDescription($exportParam['title'])
        ->setKeywords($exportParam['title'])
        ->setCategory($exportParam['title']);

    // Set active sheet
    $objPHPExcel->setActiveSheetIndex(0);
    $objPHPExcel->getActiveSheet()->setTitle($exportParam['title']);

    // Set cell value
    //rows are 1-based whereas columns are 0-based, so “A1″ becomes (0,1).
    //$objPHPExcel->setCellValueByColumnAndRow($column, $row, $value);
    //$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(0, 1, "This is A1");
    for($row = 0; $row < count($exportArray); $row++){
        ksort($exportArray[$row]);  // sort by key
        foreach ($exportArray[$row] AS $key => $value){
            // Find key index from first row
            $key_index = -1;
            if (array_key_exists($key, $exportArray[0])){
                $key_index = array_search($key, array_keys($exportArray[0]));
            }

            // Set key(column name)
            if($row==0){
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($key_index, 1, $key);
            }

            //   var_dump($key);

            if($key_index != -1){

                switch ($key) {

                    case 'createDate' :
                    case 'mtime' :
                        if($value!=null && $value> 25569){
                            $value=(($value/86400)+25569); //  change  database  timestamp to date for excel .
                        }

                        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($key_index, $row+2, $value);
                        $objPHPExcel->getActiveSheet()->getStyleByColumnAndRow($key_index, $row+2)->getNumberFormat()->setFormatCode(PHPExcel_Style_NumberFormat::FORMAT_DATE_YYYYMMDD);
                        //  var_dump($key.$value);
                        break;

                    default:
                        $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow($key_index, $row+2, $value);
                    //    var_dump($key.$value);

                }
                // Set Value (each row)


            }else{
                // Can not find $key in $row
            }

        }
    }

    // Browser download
    if (strcmp("php://output", $exportParam['filename'])==0){
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="FixedAssets.xls"');
        header('Cache-Control: max-age=0');
    }

    // Write to file
    // If you want to output e.g. a PDF file, simply do:
    //$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'PDF');
    $objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel5');
    $objWriter->save($exportParam['filename']); // Excel2007 : '.xlsx'   Excel5 : '.xls'

    echo json_encode(array('message' => 'success'));
}

function parseProductAttributesForExport ($magentoProductInfo) {
    $response = array();
    foreach ($magentoProductInfo as $attrKey => $attrValue) {
        switch ($attrKey) {
            case 'attribute_set_id' :
                $attrIdName = attributeSetNameAndId('attributeSetId', $attrValue);
                $response[$attrKey] = $attrIdName['name'];
                break;
            default :
                $response[$attrKey] = getAttributeValueFromOptionsForExport('attributeName', $attrKey, $attrValue);;
        }
    }
    return $response;
}

function getAttributeOptions ($nameOrId, $value) {
    /*$nameOrId = 'attributeName' or 'attributeId'*/
    switch ($nameOrId) {
        case 'attributeName' :
            $attributeCode = $value;
            $attributeId = Mage::getResourceModel('eav/entity_attribute')->getIdByCode('catalog_product', $value);
            break;
        case 'attributeId' :
            $attributeCode = Mage::getModel('eav/entity_attribute')->load($value)->getAttributeCode();
            $attributeId = $value;
            break;
    }

    if (isset($attributeCode)) {
        $attribute = Mage::getSingleton('eav/config')->getAttribute('catalog_product', $attributeCode);
        $attributeData = $attribute->getData();
        $rs = array(
            'attributeCode' => $attributeCode,
            'attributeId' => $attributeId,
            'frontend_input' => $attributeData['frontend_input'],
            'backend_type' => $attributeData['backend_type']
        );
        if ($attribute->usesSource()) {
            $options = $attribute->getSource()->getAllOptions(false);
            $rs['options'] = $options;
        }
        return $rs;
    }

    return null;
}

function getAttributeValueFromOptionsForExport ($nameOrId, $attrCodeOrId, $valueToBeMapped) {
    /*$nameOrId = 'attributeName' or 'attributeId'*/
    file_put_contents('log.txt', $attrCodeOrId . ': ' . $valueToBeMapped . PHP_EOL, FILE_APPEND);
    $optionsArray = getAttributeOptions($nameOrId, $attrCodeOrId);
    if ($optionsArray && isset($optionsArray['frontend_input']) ) {
        switch ($optionsArray['frontend_input']) {
            case 'select' :
            case 'boolean' :
                foreach ($optionsArray['options'] as $optionObject) {
                    if ((int)$optionObject['value'] == (int)$valueToBeMapped) {
                        return $optionObject['label'];
                    }
                }
                break;
            case 'multiselect' :
                /*multiselect : a02030_headsets_connector,
                "a02030_headsets_connector": "147,148,149,150"*/
                file_put_contents('log.txt', $attrCodeOrId . ': ' . $valueToBeMapped . PHP_EOL, FILE_APPEND);
                $valueToBeMappedArray = explode(',', $valueToBeMapped);
                file_put_contents('log.txt', 'count($valueToBeMappedArray)' . ': ' . count($valueToBeMappedArray) . PHP_EOL, FILE_APPEND);
                if (count($valueToBeMappedArray) < 2) {
                    foreach ($optionsArray['options'] as $optionObject) {
                        if ((int)$optionObject['value'] == (int)$valueToBeMapped) {
                            return $optionObject['label'];
                        }
                    }
                } else {
                    $mappedArray = array();
                    foreach ($optionsArray['options'] as $optionObject) {
                        if (in_array((int)$optionObject['value'], $valueToBeMappedArray)) {
                            file_put_contents('log.txt', 'mapped value' . ': ' . $optionObject['label'] . PHP_EOL, FILE_APPEND);
                            $mappedArray[] = $optionObject['label'];
                        }
                    }
                    return join(',', $mappedArray);
                }
                break;
            case 'text' :
            case 'textarea' :
            case 'price' :
            case 'date' :
            case 'weight' :
            case 'media_image' :
                return $valueToBeMapped;
                break;
            default :
                return "******** " . $optionsArray['frontend_input'] . " ********";
        }
    }
    return $valueToBeMapped;
}

function parseXlsxIntoArray ($inputFileName) {
    date_default_timezone_set('Asia/Taipei');
    try {
        $inputFileType = PHPExcel_IOFactory::identify($inputFileName);
        $objReader = PHPExcel_IOFactory::createReader($inputFileType);
        $objPHPExcel = $objReader->load($inputFileName);
    } catch(Exception $e) {
        die('Error loading file "'.pathinfo($inputFileName,PATHINFO_BASENAME).'": '.$e->getMessage());
    }

//  Get worksheet dimensions
    $sheetCount = $objPHPExcel->getSheetCount();
    $sheet = $objPHPExcel->getSheet(0);
    $highestRow = $sheet->getHighestRow();
    $highestColumn = $sheet->getHighestColumn();
    $highestColumnNumber = excel_col_to_num($highestColumn);

//  Loop through each row of the worksheet in turn
    $dataArray = array();
    $rowTitle = array();
    for ($row = 1; $row <= $highestRow; $row++){
        if ($row == 1) {
            //  Read a row of data into an array
            $rowTitle = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row,
                NULL,
                TRUE,
                FALSE);
        } else {
            //  Read a row of data into an array
            $rowRawData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row,
                NULL,
                TRUE,
                FALSE);
            //  Insert row data array into your database of choice here
            $rowData = array();

            for ($i = 0; $i < $highestColumnNumber; $i++) {
                switch ($rowTitle[0][$i]) {
                    case '入庫日期' :
                    case '保固到期日' :
                    case 'modifiedDate' :
                    case 'createDate' :
                    case 'purchaseDate' :
                    case 'deployDate' :


                        if ($rowRawData[0][$i] != null) {
                            if(is_nan($rowRawData[0][$i])){
                                $rowRawData[0][$i] =strtotime($rowRawData[0][$i]);
                                //   var_dump(strtotime($rowRawData[0][$i]));
                            }else{
                                // if ($rowRawData[0][$i] > 25569 && $rowRawData[0][$i] != null) {
                                $rowRawData[0][$i] = ((int)$rowRawData[0][$i] - 25569) * 86400;     //  change excel date number to timestamp.

                            }
                        } else {
                            $rowRawData[0][$i] = 0;
                        }
                        break;

                }
                if ($rowRawData[0][$i] != null) {
                    $rowData[$rowTitle[0][$i]] = $rowRawData[0][$i];
                }
            }

            array_push($dataArray, $rowData);
        }
    }
    return $dataArray;
}

//英文轉數字
function excel_col_to_num($str){
    $result = 0;
    $arr = array_reverse(str_split($str));
    foreach((array)$arr as $key => $val){
        $result += pow(26, $key)*az_num($val);
    }
    return $result;
}

//英文轉數字對照
function az_num($str) {
    if(strtoupper($str)=="A"){return 1;}
    if(strtoupper($str)=="B"){return 2;}
    if(strtoupper($str)=="C"){return 3;}
    if(strtoupper($str)=="D"){return 4;}
    if(strtoupper($str)=="E"){return 5;}
    if(strtoupper($str)=="F"){return 6;}
    if(strtoupper($str)=="G"){return 7;}
    if(strtoupper($str)=="H"){return 8;}
    if(strtoupper($str)=="I"){return 9;}
    if(strtoupper($str)=="J"){return 10;}
    if(strtoupper($str)=="K"){return 11;}
    if(strtoupper($str)=="L"){return 12;}
    if(strtoupper($str)=="M"){return 13;}
    if(strtoupper($str)=="N"){return 14;}
    if(strtoupper($str)=="O"){return 15;}
    if(strtoupper($str)=="P"){return 16;}
    if(strtoupper($str)=="Q"){return 17;}
    if(strtoupper($str)=="R"){return 18;}
    if(strtoupper($str)=="S"){return 19;}
    if(strtoupper($str)=="T"){return 20;}
    if(strtoupper($str)=="U"){return 21;}
    if(strtoupper($str)=="V"){return 22;}
    if(strtoupper($str)=="W"){return 23;}
    if(strtoupper($str)=="X"){return 24;}
    if(strtoupper($str)=="Y"){return 25;}
    if(strtoupper($str)=="Z"){return 26;}
}

function getHtmlContent ($url) {
    global $client;
    $request = $client->getMessageFactory()->createRequest($url, 'GET');
    $response = $client->getMessageFactory()->createResponse();
    $client->send($request, $response);

    if ($response->getStatus() === 200 || $response->getStatus() === 301) {
        return $response->getContent();
    } else {
        echo 'get status: ' . $response->getStatus() . PHP_EOL;
        sleep(20);
        return getHtmlContent($url);
    }

}

function parseAllNumberToSku ($number) {
    $temp = substr_replace($number, '-', 5, 0);
    return substr_replace($temp, '-', 2, 0);
}

function sendMailWithDownloadUrl ($action, $url) {
    global $debug;

    if ($debug) {
        $recipient_array = array(
            'to' => array('Li.L.Liu@newegg.com'),
            'bcc' => array('Reyna.C.Chu@newegg.com', 'Tim.H.Huang@newegg.com')
        );
    } else {
        $recipient_array = array(
            'to' => array('uspm@rosewill.com'),
            'bcc' => array('Li.L.Liu@newegg.com', 'Tim.H.Huang@newegg.com')
        );
    }

    require_once 'class/Email.class.php';
    require_once 'class/EmailFactory.class.php';

    /* SMTP server name, port, user/passwd */
    $smtpInfo = array("host" => "127.0.0.1",
        "port" => "25",
        "auth" => false);
    $emailFactory = EmailFactory::getEmailFactory($smtpInfo);

    /* $email = class Email */
    $email = $emailFactory->getEmail($action, $recipient_array);
    $content = templateReplace($action, $url);
    $email->setContent($content);
    $email->sendMail();

    return true;
}

function templateReplace ($action, $url) {
    require_once 'PHPExcel/Classes/PHPExcel.php';
    $content = file_get_contents('email/content/template.html');
    $doc = phpQuery::newDocumentHTML($content);

    $contentTitle = array(
        'Crawler Report' => 'Crawler Report'
    );
    (isset($contentTitle[$action])) ? $doc['.descriptionTitle'] = $contentTitle[$action] : $doc['.descriptionTitle'] = $action;

    $emailContent = array();
    $doc['.description'] = 'URL: ' . '<a href="http://test.rosewill.com/media/report/' . $url . '">' . $url . "</a>";
    $doc['.logoImage']->attr('src', 'images/rosewilllogo.png');
    return $doc;
}

function replace_unicode_escape_sequence($match) {
    return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
}

function unicode_decode($str) {
    return preg_replace_callback('/\\\\u([0-9a-f]{4})/i', 'replace_unicode_escape_sequence', $str);
}

function parseMayWeSuggest ($neweggItemNumber) {
    /* N82E16812119269 */
    $url = 'http://content.newegg.com/Common/Ajax/RelationItemInfo2013.aspx?type=Seller&item=' . $neweggItemNumber . '&v2=2012&action=Biz.Product.ItemRelationInfoManager.JsonpCallBack';
    $originalContent = file_get_contents($url);
    $decoded = unicode_decode($originalContent);
    $decoded = str_replace('\/', '/', $decoded);
    $decoded = str_replace('||+||+||+||', '', $decoded);
    $doc = phpQuery::newDocumentHTML($decoded);
    $combineBox0 = pq('#CombineBoxItem0', $doc)->html();
    return $combineBox0;
}