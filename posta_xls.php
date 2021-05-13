<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

/*
 * This is the file path, please download fresh xlsx from Posta, than setting current file name
 * @url:https://www.posta.hu/szolgaltatasok/iranyitoszam-kereso
 */
$inputFileName = 'Iranyitoszam-Internet_uj.xlsx';
/*
 * Create new reader
 */
$reader = new Xlsx();
/*
 * Load file
 */
$spreadsheet = $reader->load($inputFileName);
/*
 * Get active sheet in array
 */
$sheetDatas = $spreadsheet->getActiveSheet()->toArray();
/*
 * Unset first empty row and header
 */
unset($sheetDatas[0], $sheetDatas[1]);
// AWS DynamoDB table name:
$tablename = 'Postcode-b2pjglf355ff7n3jca5dvjy4gm-test';
// Staring string of file
$head = '{'.PHP_EOL;
$head .= '  "'.$tablename.'": ['.PHP_EOL;
// Ending string of file
$foot = '   ]'.PHP_EOL;
$foot .= '}';
// Content string of file
$body = '';
$numberOfImportedDataRow = count($sheetDatas);
$numberOfIteration = ceil(($numberOfImportedDataRow / 25));

echo $numberOfImportedDataRow.' data rows,'.PHP_EOL;
echo $numberOfIteration.' file will create.'.PHP_EOL;
/*
 * We have cut array after 25 element, because AWS max limit per file is 25 (Putrequest)
 */
$feldolgozotomb = array_chunk($sheetDatas, 25);
$i = 0;
$end = false;
foreach($feldolgozotomb as $chunk ) {
    /*
     * If empty element value end loop
     */
    if ($end) {
        break;
    }
    $i++;
    $body = '';
    $c = 0;
    foreach($chunk as $elements) {
        /*
         * If empty array value break
         */
        if (empty($elements[0]) && empty($elements[1])) {
            $end = true;
            break;
        }
        $c++;
        // First iteration hasn't coma
        if($c > 1){
            $body .= PHP_EOL . '  ,';
        }
        // Special AWS json format
        $body .= '{'.PHP_EOL;
        $body .= '    "PutRequest": {'.PHP_EOL;
        $body .= '      "Item": {'.PHP_EOL;
        $body .= '        "id": {'.PHP_EOL;
        $body .= '          "S": "'.$elements[0].'"'.PHP_EOL;
        $body .= '        },'.PHP_EOL;
        $body .= '        "settlement": {'.PHP_EOL;
        $body .= '          "S": "'.$elements[1].'"'.PHP_EOL;
        $body .= '        },'.PHP_EOL;
        $body .= '        "country": {'.PHP_EOL;
        $body .= '          "S": "HU"'.PHP_EOL;
        $body .= '        },'.PHP_EOL;
        $body .= '        "createdAt": {'.PHP_EOL;
        $body .= '          "S": "2021-01-24T14:57:11.852Z"'.PHP_EOL;
        $body .= '        },'.PHP_EOL;
        $body .= '        "updatedAt": {'.PHP_EOL;
        $body .= '          "S": "2021-01-24T14:57:11.852Z"'.PHP_EOL;
        $body .= '        }'.PHP_EOL;
        $body .= '      }'.PHP_EOL;
        $body .= '    }'.PHP_EOL;
        $body .= '  }'.PHP_EOL;
    }
    $contents = $head.$body.$foot;
    /*
     * If no content, we can"t create file
     */
    if(!$end) {
        $filename = 'json/'. $i . '.json';
        file_put_contents($filename, $contents) or die('Something went wrong!');
        echo $filename . ' file OK'.PHP_EOL;
    }
}
