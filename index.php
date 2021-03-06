<?php

include ('src/estimator.php');

const ESTIMATOR_ROUTE = '/api/v1/on-covid-19';

const LOG_ROUTE = '/api/v1/on-covid-19/logs';

const ESTIMATOR_ROUTE_JSON = '/api/v1/on-covid-19/json';

const ESTIMATOR_ROUTE_XML = '/api/v1/on-covid-19/xml';

header("Access-Control-Allow-Origin: *");
header("HTTP/1.1 200 OK");

$request_url = $_SERVER['REQUEST_URI'];
$request_method = $_SERVER['REQUEST_METHOD'];
$host = $_SERVER['HTTP_HOST'];
$port = $_SERVER['SERVER_PORT'];

//$formatted_request_url = str_replace('/api/v1/', '', $request_url);

if(in_array($request_url, [ESTIMATOR_ROUTE, ESTIMATOR_ROUTE_JSON]) && $request_method === "POST") {
    $starttime = microtime(true);
    header("Content-Type: application/json; charset=UTF-8");
    try {
        echo json_encode(covid19ImpactEstimator(json_decode(file_get_contents("php://input"), true)),
                        JSON_PRETTY_PRINT);
        $response_time = pingDomain($starttime);
        file_put_contents('log.txt', logContent($response_time, 200, $request_method, $request_url),
                            FILE_APPEND);
    }catch (Exception $e) {
        $response_time = pingDomain($starttime);
        file_put_contents('log.txt', logContent($response_time, 500, $request_method, $request_url),
                            FILE_APPEND);
    }
}

if($request_url === ESTIMATOR_ROUTE_XML && $request_method === "POST") {
    $starttime = microtime(true);
    header("Content-Type: application/xml;charset=utf-8");
    try {
        $response = covid19ImpactEstimator(json_decode(file_get_contents("php://input"), true));
        echo arrayToXml($response);
        $response_time = pingDomain($starttime);
        file_put_contents('log.txt', logContent($response_time, 200, $request_method, $request_url),
            FILE_APPEND);
    }catch (Exception $e) {
        $response_time = pingDomain($starttime);
        file_put_contents('log.txt', logContent($response_time, 500, $request_method, $request_url),
            FILE_APPEND);
    }
}

if($request_url === LOG_ROUTE && $request_method === "GET") {
    $starttime = microtime(true);
    header('Content-Type: text/plain');
    try {
        $response_time = pingDomain($starttime);
        file_put_contents('log.txt', logContent($response_time, 200, $request_method, $request_url),
            FILE_APPEND);

        echo file_get_contents('log.txt');

    }catch (Exception $e) {
        $response_time = pingDomain($starttime);
        file_put_contents('log.txt', logContent($response_time, 500, $request_method, $request_url),
                            FILE_APPEND);
    }
}

function logContent($response_time, $response_code, $method, $request_url)
{
    return $method."\t\t" .$request_url."\t\t" .$response_code."\t\t" .$response_time."\n";
}

function pingDomain($starttime)
{
    $stoptime = microtime(true);
    $status = floor(($stoptime - $starttime) * 1000);
    if(strlen($status) > 1) {
        return $status.'ms';
    }else {
        return '0'.$status.'ms';
    }
}

function arrayToXml($array, $rootElement = null, $xml = null) {
    $_xml = $xml;

    // If there is no Root Element then insert root
    if ($_xml === null) {
        $_xml = new SimpleXMLElement($rootElement !== null ? $rootElement : '<root/>');
    }

    // Visit all key value pair
    foreach ($array as $k => $v) {

        // If there is nested array then
        if (is_array($v)) {

            // Call function for nested array
            arrayToXml($v, $k, $_xml->addChild($k));
        }

        else {

            // Simply add child element.
            $_xml->addChild($k, $v);
        }
    }

    return $_xml->asXML();
}
