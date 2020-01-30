<?php
/**
 * Entrada [ http://www.entrada-project.org ]
 * 
 * Entrada is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Entrada is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Entrada.  If not, see <http://www.gnu.org/licenses/>.
 *
 * This file is responsible for creating the database connection and
 * initializing the ADOdb class.
 * 
 * @author Organisation: Queen's University
 * @author Unit: School of Medicine
 * @author Developer: Matt Simpson <matt.simpson@queensu.ca>
 * @copyright Copyright 2010 Queen's University. All Rights Reserved.
 * 
*/

$ADODB_QUOTE_FIELDNAMES = true;	// Whether or not you want ADOdb to backtick field names in AutoExecute, GetInsertSQL and GetUpdateSQL.
define("ADODB_QUOTE_FIELDNAMES", $ADODB_QUOTE_FIELDNAMES);

// Information required to start a new database connection.
$db = NewADOConnection(DATABASE_TYPE);
$db->Connect(DATABASE_HOST, DATABASE_USER, DATABASE_PASS, DATABASE_NAME);
$db->SetFetchMode(ADODB_FETCH_ASSOC);

if (defined("DEFAULT_CHARSET") && isset($ENTRADA_CHARSETS) && is_array($ENTRADA_CHARSETS) && array_key_exists(DEFAULT_CHARSET, $ENTRADA_CHARSETS)) {
	$db->Execute("SET NAMES ".$db->qstr($ENTRADA_CHARSETS[DEFAULT_CHARSET]["mysql_names"])." COLLATE ".$db->qstr($ENTRADA_CHARSETS[DEFAULT_CHARSET]["mysql_collate"]));
}

$db->debug = (((isset($DEVELOPER_IPS) && is_array($DEVELOPER_IPS) && isset($_SERVER["REMOTE_ADDR"]) && in_array($_SERVER["REMOTE_ADDR"], $DEVELOPER_IPS)) && (isset($_GET["debug"]))) ? true : false);

@ini_set("session.name", SESSION_NAME);
@ini_set("session.gc_maxlifetime", SESSION_EXPIRES);

if ((defined("ADODB_SESSION")) && (defined("DATABASE_SESSIONS")) && (DATABASE_SESSIONS)) {
	require_once("Entrada/adodb/session/adodb-session2.php");

	ADODB_Session::config(SESSION_DATABASE_TYPE, SESSION_DATABASE_HOST, SESSION_DATABASE_USER, SESSION_DATABASE_PASS, SESSION_DATABASE_NAME, array("table" => "sessions"));
	ADODB_Session::encryptionKey(ENCRYPTION_KEY);
	ADODB_Session::open(false, false, false);
	ADODB_Session::optimize(true);
	ADODB_Session::expireNotify(array("PROXY_ID", "expired_session"));
	session_start();
} else {
	session_start();
}
