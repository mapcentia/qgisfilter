<?php
namespace app\extensions\qgisfilter\api;

use \app\conf\App;
use \app\inc\Util;
use \app\inc\Input;
use \app\inc\Route;

/**
 * Class Wms
 * @package app\controllers
 */
class Wms extends \app\inc\Controller
{
    /**
     * Wms constructor.
     */
    function __construct()
    {
        parent::__construct();
    }

    /**
     *
     */
    public function get_index()
    {
        $id = Route::getParam("id");

        $name = md5(rand(1, 999999999) . microtime());

        $e = "/var/www/geocloud2/app/wms/qgsfiles/parsed_lokalplan.qgs";
        $mapFile = fopen($e, "r");
        $str = fread($mapFile,filesize($e));
        fclose($mapFile);

        // Set SQL=
        $str = str_replace("sql=", "sql=planid={$id}", $str);


        $n = "/var/www/geocloud2/app/tmp/{$name}.qgs";
        $newMapFile = fopen($n, "w");
        fwrite($newMapFile, $str);
        fclose($newMapFile);


        $url = "http://127.0.0.1/cgi-bin/qgis_mapserv.fcgi?map={$n}&" . $_SERVER["QUERY_STRING"];

        header("X-Powered-By: GC2 WMS");
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function ($curl, $header_line) {
            $bits = explode(":", $header_line);
            if ($bits[0] == "Content-Type") {
                $this->type = trim($bits[1]);
            }
            // Send text/xml instead of application/vnd.ogc.se_xml
            if ($bits[0] == "Content-Type" && trim($bits[1]) == "application/vnd.ogc.se_xml") {
                header("Content-Type: text/xml");
            } elseif ($bits[0] != "Content-Encoding" && trim($bits[1]) != "chunked") {
                header($header_line);
            }
            return strlen($header_line);
        });
        $content = curl_exec($ch);
        curl_close($ch);
        echo $content;
        exit();
    }
}