<?php

/*
  remember: implementation simplicity is more important than interface simplicity.

  TODO:
  - /etc/schema.php defining table properties
  - /script/genModel.php to create a
  - etc/schema.php from existing db configuration and db
  - lib/data/* classes

  Entity holds a record data
  list retrieves RecordSet
  Fetch hydrates Entity

  no on the fly code generation
 */


/*
  una entity conosce come salvare un record

  class Entity {
  var $data = array();
  // l'oggetto deve essere esplicitamente settato con un nome di tabella oppure
  // questo verrà dedotto dal nome della classe, quindi
  // il nome della classe e il nome della tabella devono essere uguali
  var $table = '';
  //-- validation
  var $_requiredPresence;
  var $_requiredNumericallity;
  var $_requiredIsString;
  var $_requiredValidation;
  var $_requiredFormat;
  function get($name, $default = null) {
  if (isset($this->data[$name]) && !empty($this->data[$name])) {
  //TODO: lookup DataDictionary to find correct type of data
  return $this->data[$name];
  } else {
  //TODO: lookup default for this field
  return null;
  }
  }
  function set($name, $val) {
  $this->data[$name] = $val;
  }
  // recupera i dati da un rs
  //function hydrate($rs) {}
  // salva l'ogetto ed eventualmente le sue modifiche
  function save() {
  if ($this->isValid()) {
  $this->confirm();
  } else {
  // error

  }
  }
  function isValid() {
  return $this->validatePresence() && $this->validateNumericallity() && $this->validateIsString() && $this->validateUniquenessOf() && $this->validateFormatOf() && $this->requireValidation();
  }
  function confirm() {
  // cerca di eseguire una update del record e se fallisce tenta un inserimento
  // act=0 significa che sto eseguito un update, 1 un insert
  $db = Weasel::getDB();
  $where = $this->getPkField() . '=' . $this->getPK();
  $sql = SQL::update($this->getTableName(), $where, $this->data) . "\n";
  $result = $db->qry_cmd($sql, __LINE__, __FILE__);
  $act = 0;
  if ($db->lines == 0) {
  $sql = SQL::insert($this->getTableName(), $this->data) . "\n";
  $result = $db->qry_cmd($sql, __LINE__, __FILE__);
  $this->set($this->getPKField(), $db->get_last_inserted_id());
  $act = 1;
  }
  return $result;
  }
  function getPK() {
  return $this->get($this->getPKField(), null);
  }
  function getPKField() {
  return DataDictionary::getPK($this->getTableName());
  }
  function getTableName() {
  return !empty($this->table) ? $this->table : strtolower(get_class($this));
  }
  //-- validation setters
  function requirePresenceOf($fields = array()) {
  $this->_requiredPresence = $fields;
  }
  function requireNumericallityOf($fields = array()) {
  $this->_requiredNumericallity = $fields;
  }
  function requireIsStringOf($field, $minLen = 0, $maxLen = 255) {
  $this->_requiredIsString[$field] = array('min' => $minLen, 'max' => $maxLen);
  }
  function requireValidationOf($field, $validation_function) {
  $this->_requiredValidation[] = array($field, $validation_function);
  }
  function requireUniquenessOf($field) {
  }
  function requireFormatOf($field, $regexp, $message) {
  $this->_requiredFormat[$field] = $regexp;
  }
  //-- validation appliers
  function validatePresence() {
  if (!empty($this->_requiredPresence)) {
  foreach ($this->_requiredPresence as $field) {
  $v = $this->get($field);
  if ($v == '' || $v === null) {
  return false;
  }
  }
  }
  return true;
  }
  function validateNumericallity() {
  if (!empty($this->_requiredNumericallity)) {
  foreach ($this->_requiredNumericallity as $field) {
  $v = $this->get($field);
  if (!is_int($v)) {
  return false;
  }
  }
  }
  return true;
  }
  function validateIsString() {
  if (!empty($this->_requiredIsString)) {
  foreach ($this->_requiredIsString as $field => $a) {
  $v = $this->get($field);
  return is_string($v) && strlen($v) >= $a['min'] && strlen($v) <= $a['max'];
  }
  }
  return true;
  }
  function validateUniquenessOf() {
  return true;
  }
  function validateFormatOf() {
  if (!empty($this->_requiredFormat)) {
  foreach ($this->_requiredFormat as $field => $regexp) {
  $v = preg_match($regexp, $this->get($field));
  if (!$v) return false;
  }
  }
  return true;
  }
  function requireValidation() {
  if (!empty($this->_requiredValidation)) {
  foreach ($this->_requiredValidation as $i => $v_a) {
  $field = $v_a[0];
  $f = $v_a[1];
  $v = $f($this, $field, $this->get($field));
  if (!$v) {
  return false;
  }
  }
  }
  return true;
  }
  //---- relationships
  //-- get records, use in entity->getChildren();
  function _hasOne_get($table, $fromField = 'id', $toTableField = 'id') {
  }
  function _hasMany_get($table, $fromField = 'id', $toTableField = 'id') {
  }
  function _hasManyToMany_get($linkTable, // tabella di collegamento
  $table, // tabella da raggiungere
  $fromField = 'id', // campo dell'entità corrente
  $linkTableFromField = '', // campo da confrontare con l'entità corrnte
  $linkTableByField = '', // campo da confrontare con la tabella da raggiungere
  $toTableField = 'id' // campo pk della tabella da raggiungere
  ) {
  }
  //-- delete records relatad to current entity
  function _hasOne_delete($table, $fromField = 'id', $toTableField = 'id') {
  }
  function _hasMany_delete($table, $fromField = 'id', $toTableField = 'id') {
  }
  function _hasManyToMany_delete($linkTable, // tabella di collegamento
  $table, // tabella da raggiungere
  $fromField = 'id', // campo dell'entità corrente
  $linkTableFromField = '', // campo da confrontare con l'entità corrnte
  $linkTableByField = '', // campo da confrontare con la tabella da raggiungere
  $toTableField = 'id' // campo pk della tabella da raggiungere
  ) {
  }
  }
 */

// -contiene le query di selezione
// -ritorna sempre $rs
// -no DB abstraction
class DAL {

    // return a recordset
    function select($sql, &$count) {
        $db = Weasel::getDB();
        $rs = $db->qry($sql, __LINE__, __FILE__);
        $count = $db->num_rows($rs);
        return $rs;
    }

    //
    // ritorna il primo valore del primo record
    //
    function selectValue($sql) {
        $db = Weasel::getDB();
        $rs = $db->qry($sql, __LINE__, __FILE__);
        $a = array();
        $record = $db->rs2a($rs);
        if ($record) {
            return $a[0][0];
        } else {
            return null;
        }
    }

    //
    // ritorna array di entità
    //
    function fetch($rs, $model_class) {
        $data = mysql_fetch_array($rs);
        if ($data) {
            $o = new $model_class();
            $o->data = $data;
            return $o;
        } else {
            return false;
        }
    }

}
