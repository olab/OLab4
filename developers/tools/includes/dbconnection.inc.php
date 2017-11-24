<?php
/**
 * Whether or not you want ADOdb to backtick field names in AutoExecute, GetInsertSQL and GetUpdateSQL.
 */
$ADODB_QUOTE_FIELDNAMES = true;

/**
 * Information required to start a new database connection.
 */
$db = NewADOConnection(DATABASE_TYPE);
$db->Connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);
$db->SetFetchMode(ADODB_FETCH_ASSOC);
?>