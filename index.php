<?php

$opts = array('http' => array('proxy'=> 'tcp://127.0.0.1:8080', 'request_fulluri'=> true), 'ssl' => array( 'verify_peer' => false, 'verify_peer_name' => false));
stream_context_set_default($opts);
$context = stream_context_create($opts);

$ch = curl_init();

$ip = $_SERVER['HTTP_CLIENT_IP'];
$ip_api = "http://ip-api.com/json/" . $ip;

curl_setopt($ch, CURLOPT_URL, $ip_api);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo 'Error:' . curl_error($ch);
} else {
    $data = json_decode($response);
    $ville = $data->city;
    $lat = $data->lat;
    $lon = $data->lon;
}

curl_close($ch);

$xsl = new DOMDocument();
$xsl->load('meteo.xsl');

$api_meteo = "https://www.infoclimat.fr/public-api/gfs/xml?_ll=" . $lat . "," . $lon . "&_auth=ARsDFFIsBCZRfFtsD3lSe1Q8ADUPeVRzBHgFZgtuAH1UMQNgUTNcPlU5VClSfVZkUn8AYVxmVW0Eb1I2WylSLgFgA25SNwRuUT1bPw83UnlUeAB9DzFUcwR4BWMLYwBhVCkDb1EzXCBVOFQoUmNWZlJnAH9cfFVsBGRSPVs1UjEBZwNkUjIEYVE6WyYPIFJjVGUAZg9mVD4EbwVhCzMAMFQzA2JRMlw5VThUKFJiVmtSZQBpXGtVbwRlUjVbKVIuARsDFFIsBCZRfFtsD3lSe1QyAD4PZA%3D%3D&_c=19f3aa7d766b6ba91191c8be71dd1ab2";
$xml = new DOMDocument();
$xml->load($api_meteo);

$xsltproc = new XSLTProcessor();
$xsltproc->importStylesheet($xsl);
echo $xsltproc->transformToXML($xml);


