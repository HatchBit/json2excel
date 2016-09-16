<?php
/**
 * JSON2EXCEL
 *
 * JSONからExcelファイルを出力
 *
 * @package     json2excel
 * @author      Y.Yajima <yajima@hatchbit.jp>
 * @copyright   2016-09-16, HatchBit & Co.
 * @license     Apache License 2.0
 * @link        http://www.hatchbit.jp
 * @since       Version 1.0
 * @filesource
 * @param   string    $apiurl   JSONデータを提供しているAPI URL
 * @param   string    $colname  EXCELに変換するオブジェクト名 ex. 'data', 'results' etc...
 * @param   string    $file     出力するファイルフォーマット'
 */

/*====================
  DEFINE
  ====================*/
error_reporting(E_ALL);
ini_set('default_mimetype', 'text/html');
ini_set('default_charset', 'UTF-8');
ini_set("auto_detect_line_endings", true);
mb_language('Japanese');
mb_internal_encoding('UTF-8');

if(!defined('ROOT_DIR')) define('ROOT_DIR', dirname(dirname(dirname(__FILE__))) );// ルートディレクトリ
if(!defined('OUT_DIR')) define('OUT_DIR', dirname(__FILE__).'/exports' );// ルートディレクトリ

// 必要モジュールを読み込み

// PHPExcel 1.8.0
require ROOT_DIR.'/library/PHPExcel.php';
require ROOT_DIR.'/library/PHPExcel/IOFactory.php';

/*====================
  BEFORE ACTIONS
  ====================*/
$apiurl = $data = "";
$resultColumnName = "data";

/*====================
  MAIN ACTIONS
  ====================*/
if(isset($_GET['colname'])) {
    $resultColumnName = $_GET['colname'];
}
if(isset($_GET['apiurl'])) {
    $apiurl = strval($_GET['apiurl']);
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $apiurl);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $data = curl_exec($ch);
    
    if (curl_errno($ch)) { 
        print "Error: " . curl_error($ch); 
    } else { 
        // Show me the result 
        //var_dump($data); 
        curl_close($ch); 
    }
}

if(!empty($data) && isset($_GET['file'])) {
    
    $dataarr = json_decode($data, true);// レスポンスデータ（JSONを配列にデコード）
    
    // ヘッダー行を作成
    $headers = array();
    foreach($dataarr[$resultColumnName] as $val) {
        $tempheaders = array_keys($val);
        $diff = array_diff($tempheaders, $headers);
        foreach($diff as $d) {
            $headers[] = $d;
        }
    }
    unset($val);
    
    if(strpos($_GET['file'], 'excel') !== FALSE) {
        $excel = new PHPExcel();//新しいExcelオブジェクトを作成
        $excel->setActiveSheetIndex(0);//何番目のシートに有効にするか
        $sheet = $excel->getActiveSheet();//有効になっているシートを取得
        $sheet->setTitle('シート 1');//シート名を設定します。
        
        //数値で指定する場合
        
        // ヘッダー行書き出し
        $col = 0;
        $row = 1;
        foreach($headers as $hval) {
            //$value = mb_convert_encoding($hval, "SJIS-WIN", "UTF-8");
            $value = $hval;
            $sheet->setCellValueByColumnAndRow($col, $row, $value);
            $col++;
        }
        unset($hval);
        
        // データ書き出し
        foreach($dataarr[$resultColumnName] as $val) {
            $row++;
            $lines = array();
            $col = 0;
            foreach($headers as $hval) {
                if(isset($val[$hval])) {
                    $value = $val[$hval];
                    $sheet->setCellValueByColumnAndRow($col, $row, $value);
                }
                $col++;
            }
            unset($hval);
        }
        unset($val);
        
        switch($_GET['file']) {
            case "excel5":
                $filename = "response.xls";
                $filekind = "Excel5";
                break;
            case "excel2007":
                $filename = "response.xlsx";
                $filekind = "Excel2007";
                break;
        }
        
        if(file_exists(OUT_DIR.'/'.$filename)) unlink(OUT_DIR.'/'.$filename);// 既存のファイルを削除
        $writer = PHPExcel_IOFactory::createWriter($excel, $filekind);// Excelファイルを作成
        $writer->save(OUT_DIR.'/'.$filename);// Excelファイルを保存
        
        // Excel用にヘッダー出力
        header('Content-Type: application/octet-stream');//ダウンロードの指示
        header('Content-Disposition: attachment; filename='.$filename);//ダウンロードするファイル名
        header('Content-Length:'.filesize(OUT_DIR.'/'.$filename));//ダウンロードするファイルのサイズ
        ob_end_clean();//ファイル破損エラー防止
        readfile(OUT_DIR.'/'.$filename);
        
        exit();
    }
    
    if($_GET['file'] == 'csv') {
        mb_convert_variables("SJIS-WIN", "UTF-8", $dataarr);
        $filename = "response.csv";
        $fp = fopen(OUT_DIR.'/'.$filename, "w");
        fputcsv($fp, $headers);
        foreach($dataarr[$resultColumnName] as $val) {
            $lines = array();
            foreach($headers as $hval) {
                $lines[] = (isset($val[$hval])) ? $val[$hval] : "";
            }
            unset($hval);
            fputcsv($fp, $lines);
        }
        unset($val);
        fclose($fp);
        
        // CSVファイル用にヘッダー出力
        header('Content-Type: application/octet-stream; charset=SJIS-win');//ダウンロードの指示
        header('Content-Disposition: attachment; filename='.$filename);//ダウンロードするファイル名
        header('Content-Length:'.filesize(OUT_DIR.'/'.$filename));//ダウンロードするファイルのサイズ
        readfile(OUT_DIR.'/'.$filename);
        
        exit();
    }
}

/*====================
  AFTER ACTIONS
  ====================*/

/*====================
  FUNCTIONS
  ====================*/
