<?php

namespace App\Controllers;

/**
 * DataController | anything to do with data
 *                  Uses Lazer flatfile database
 * Date: August 16 2021
 */

define("LAZER_DATA_PATH", "./resources/tables/data/");

use Lazer\Classes\Database as Lazer;

// use Lazer\Classes\Helpers as Helpers;

class DataController
{
 const TABLE_TEMPLATES = "./resources/tables/";

 public static function createTable($tableName)
 {
  $templateFile = self::TABLE_TEMPLATES . $tableName . ".json";

  if (is_file($templateFile)) {
   $template = json_decode(file_get_contents($templateFile), true);
   Lazer::create($tableName, $template);
   return true;
  }

  return false;
 }

 public static function getAllFromTable($tableName)
 {
  try {
   \Lazer\Classes\Helpers\Validate::table($tableName)->exists();
   $results = Lazer::table($tableName)
    ->findAll()
    ->asArray();
   $result = json_encode($results);

   return $result;
  } catch (\Lazer\Classes\LazerException $e) {
   //Database doesn't exist
   print_r($e);
   return false;
  }
 }

 public static function getRowByID($tableName, $id)
 {
  $row = Lazer::table($tableName)->find($id);

  return $row;
 }

 public static function checkTable($tableName)
 {
  try {
   \Lazer\Classes\Helpers\Validate::table($tableName)->exists();
   $result = true;
  } catch (\Lazer\Classes\LazerException $e) {
   //Database doesn't exist
   $result = self::createTable($tableName);
  }

  return $result;
 }

 public static function checkTableRow($tableName, $fieldName, $fieldData)
 {
  $row = Lazer::table($tableName)
   ->where($fieldName, "=", $fieldData)
   ->find();

  if (isset($row->id) && $row->id > 0) {
   return true;
  }

  return false;
 }

 public static function addRowFV($tableName, $field, $value)
 {
  $row = Lazer::table($tableName);
  $row->setField($field, $value);
  $row->save();
 }

 public static function addRow($tableName, $data)
 {
  $row = Lazer::table($tableName);
  foreach ($data as $f => $v) {
   $row->setField($f, $v);
  }
  $row->save();
 }

 public static function updateTableData(
  $data,
  $tableName = "",
  $findKey = "",
  $findValue = ""
 ) {
  $row = Lazer::table($tableName)
   ->where($findKey, "=", $findValue)
   ->find();

  if (isset($row->id)) {
   $row->set($data);
   $row->save();

   return true;
  }

  return false;
 }

 public static function removeRow($table, $id)
 {
  // Confirm Row Exists
  $row = Lazer::table($table)->find($id);

  if (isset($row->id)) {
   Lazer::table($table)
    ->find($id)
    ->delete();
   return true;
  }

  return false;
 }
}
