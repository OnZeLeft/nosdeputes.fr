<?php
if (!isset($multi)) {
  $multi = array();
 }
if (!isset($champs)) {
  $champs = $res[$champ];
 }
foreach(array_keys($champs) as $key) 
{
  echo "$key;";
}
echo "\n";

function depile_assoc($asso, $breakline, $multi) {
  global $alreadyline;
  $semi = 0;
  foreach (array_keys($asso) as $k) {
    if (isset($multi[$k]) && $multi[$k]) {
      $semi = 1;
    }
    depile($asso[$k], $breakline, $multi, $semi);
    if ($k == $breakline) {
      echo "\n";
    }
  }
  return $semi;
}

function depile($res, $breakline, $multi, $comma = 0) {
  if (is_array($res)) {
    if (!isset($res[0])) {
      return depile_assoc($res, $breakline, $multi);
    }
    foreach($res as $r) {
      $semi = depile($r, $breakline, $multi);
    }
    if ($semi) 
      echo ';';
  }else{
    if ($comma)
      $res = preg_replace('/[,;]/', '', $res);
    $string = preg_match('/[,;]/', $res);
    if ($string)
      echo '"';
    echo $res;
    if ($string)
      echo '"';
    if ($comma) 
      echo '|';
    else
      echo ';';
  }
}

depile($res, $breakline, $multi);