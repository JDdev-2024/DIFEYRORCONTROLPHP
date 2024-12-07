<?php 
header("Content-Type: application/json");
error_reporting(E_ERROR | E_PARSE);

include('wialon.php');
$wialon_api = new Wialon();

$token = "19124fbdaf86df5f56c6a07526ecd40757991AC19205607AB866EE24B743FCE06A309304";

$result = $wialon_api->login($token);
$json = json_decode($result, true);

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

  $json1 = json_decode($ecos);

  foreach ($json1->items as $unidades) {
    $imei = $unidades->uid;
    $timestamp = $unidades->pos->t;
    date_default_timezone_set('UTC');
    $hora_utc = date('Y-m-d\TH:i:s', $timestamp);
    $latitud = $unidades->pos->y;
    $longitud = $unidades->pos->x;
    $altitude = $unidades->pos->z;
    $angulo = $unidades->pos->c;
    $velocidad = $unidades->pos->s;
    $conexion = $unidades->netconn;
    $ignicion = $unidades->prms->ign->v;
    $SOS = 'Ok';

    // Algoritmo para obtener la batería
    $valueBattery = isset($unidades->prms->power->v) ? ($unidades->prms->power->v) / 1000 : 0;

    // Odometer predeterminado si no está presente
    $odometer = isset($unidades->prms->odometer->v) ? $unidades->prms->odometer->v : 652300;

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
      "event" => $SOS
    );

    // Construcción de los parámetros
    $params = "batp=" . $arr['batp'] . "|acc=" . $arr['acc'] . "|";

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
        "&event=" . urlencode($arr['event']);

    // Realizar la solicitud para cada unidad
    $response = file_get_contents($url);

    // Mostrar la respuesta de cada envío
    echo "Respuesta de la API para IMEI {$url}: " . $response . "\n";
  }

  $wialon_api->logout();
} else {
  echo WialonError::error($json['error']);
}
?>
