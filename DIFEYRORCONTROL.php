<?php
header("Content-Type: application/json");

error_reporting(E_ERROR | E_PARSE);//error_reporting(0);


include('wialon.php');
$wialon_api = new Wialon();

// old username and password login is deprecated, use token login
//USURARIO: SETRAMEXAI27
//CONTRASE�0�5A: Setra123#
$token = "19124fbdaf86df5f56c6a07526ecd40757991AC19205607AB866EE24B743FCE06A309304";// YO MERENGUES

$result = $wialon_api->login($token);
$json = json_decode($result, true);
// Funci��n para obtener el valor absoluto de jamming
function getJamming($name, $value){
  if (preg_match('/\b(?:io_1_88)\b/', $name) && $value < 2) {
    return $value;
  } elseif (preg_match('/\b(?:jamming_status)\b/', $name) && $value < 3) {
    return ($value == 1) ? 0 : 1;
  } else {
    return "NaN";
  }
}

if (!isset($json['error'])) {
  // Realizar la b��squeda de los elementos
  $ecos = $wialon_api->core_search_items('{
        "spec": {
            "itemsType": "avl_unit",
            "propName": "sys_name",
            "propValueMask": "*",
            "sortType": "sys_name"
        },
        "force": 1,
        "flags": 4611686018427387903,
        "from": 0,
        "to": 0
    }');

  $json1 = json_decode($ecos); //print_r($ecos);
  $cuadros = array();

  foreach ($json1->items as $unidades) {
    $imei = $unidades->uid;
    $timestamp = $unidades->pos->t;
    date_default_timezone_set('UTC');
    $hora_utc = date('Y-m-d\TH:i:s', $timestamp);
    $latitud = $unidades->pos->y;
    $longitud = $unidades->pos->x;
    $altitude = $unidades->pos->z;
    $angulo= $unidades->pos->c;
    $velocidad = $unidades->pos->s;
    $conexion = $unidades->netconn;
    $ignicion = $unidades->prms->ign->v;
    $SOS = 'SOS';
    /*parametris <adicionales*/ 
    
        // Algoritmo para obtener jamming 1 de mayo del 2024        
    // getNameSensor
    //algoritmo para obtener bateria desconectada
    //getNameSensor
    foreach ($sens as $item) {
      if (preg_match('/\b(?:Voltaje|volt|vol)\b/i', $item->n)){
        $nameBattery = $item->p;
        $namebat = $item->n;
      }
    }
    $valueBattery = (isset($unidades->prms->$nameBattery->v)) ? $unidades->prms->$nameBattery->v : ($unidades->prms->{'power'}->v)/1000;
    
    $odometer = (isset($unidades->prms->{'odometer'}->v)) ? $unidades->prms->{'odometer'}->v : 652300;
    
    // Construir el array final
    $arr = array(
      "imei" => $imei,
      "dt" => $hora_utc,
      "lat" => $latitud,
      "lng" => $longitud,
      "altitude" => $altitude,
      "angle" => $angulo,
      "speed" => $velocidad,
      "loc_valid" => $conexion,
      "odo" => $odometer,
      "batp" => $valueBattery,
      "acc" => $ignicion,
      "event"=> $SOS

    );

    print_r(json_encode($arr));
    array_push($cuadros, $arr);
  
  }

  $wialon_api->logout();
} else {
  echo WialonError::error($json['error']);
}
 
 $arr2= json_encode($cuadros);

$params = "batp=" . $arr['batp'] . "|acc=" . $arr['acc'] . "|";

// ENVIAMOS LA DATA A ESTOS INDIVIDUOS
$url = "https://gps.undercontrolsa.com/api/api_loc.php?" . 
"imei=" . urlencode($arr['imei']) .
"&dt=" . urlencode($arr['dt']) .
"&lat=" . urlencode($arr['lat']) .
"&lng=" . urlencode($arr['lng']) .
"&altitude=" . urlencode($arr['altitude']) .
"&angle=" . urlencode($arr['angle']) .
"&speed=" . urlencode($arr['speed']) .
"&loc_valid=" . urlencode($arr['loc_valid']) .
"&params=" . urlencode($params) .
"&event=" . urlencode($arr['event']);// URL de la API
 // Datos a enviar en la solicitud POST

// Realizar la solicitud HTTP con file_get_contents
$response = file_get_contents($url);

// Mostrar la respuesta
echo "Respuesta de la API: " . $response;



?>
