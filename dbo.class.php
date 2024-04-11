<?php
/**
  * DATABASE CLASS
  */
class Dbo{

function connect() {
  global $db_host, $db_username, $db_password, $db_name, $_SESSION;

  // Create the connection
  $conn = mysqli_connect($db_host, $db_username, $db_password);

  // Check connection
  if (!$conn) {
    $_SESSION['errors'][] = "Connection failed: " . mysqli_connect_error();
    return false;
  }

  // Select the database
  if (!mysqli_select_db($conn, $db_name)) {
    $_SESSION['errors'][] = "Database selection failed: " . mysqli_error($conn);
    mysqli_close($conn);
    return false;
  }

  // Set connection charset (optional)
  mysqli_set_charset($conn, "utf8");

  return $conn; // Return the mysqli object
}



	//query on the table
	function query($sql, $hide_errors=0){
		global $_SESSION;
		global $docroot;
		
		if( @mysqli_query($sql) ){

			$id = mysqli_insert_id();
			if( $id==0 )
				return 1;
			else
				return $id;
		}

		else{
			file_put_contents( $docroot.'/mysql.log', "[".date("Y-m-d H:i:s")."] [".$_SERVER["SCRIPT_NAME"]."] dbo->query".PHP_EOL.$sql.PHP_EOL."ERR: ".mysqli_error().PHP_EOL.PHP_EOL, FILE_APPEND );

			if( $hide_errors==0 ){
				array_push( $_SESSION['errors'], mysqli_error() );
			}
			
			else{
				return false;
			}
		}
	}

	//fetch row
	function fetchRow($sql){
		global $_SESSION;
		global $docroot;
		
		if( $rows = @mysqli_fetch_row( mysqli_query($sql) ) ){
			
			return $rows;
		}
		
		else{
			file_put_contents( $docroot.'/mysql.log', "[".date("Y-m-d H:i:s")."] [".$_SERVER["SCRIPT_NAME"]."] dbo->fetchRow".PHP_EOL.$sql.PHP_EOL."ERR: ".mysqli_error().PHP_EOL.PHP_EOL, FILE_APPEND );
			
			array_push( $_SESSION['errors'], mysqli_error() );
			
			return false;
		}
	}



	//record count 
	function fetchNum($sql, $hide_error=0){
	global $_SESSION;
	global $docroot;

	if( $q=mysqli_query($sql) ){
		$num = mysqli_num_rows($q);
		if( !mysqli_errno() ){
			return $num;
		}

		else{
			file_put_contents( $docroot.'/mysql.log', "[".date("Y-m-d H:i:s")."] [".$_SERVER["SCRIPT_NAME"]."] dbo->fetchNum".PHP_EOL.$sql.PHP_EOL."ERR: ".mysqli_error().PHP_EOL.PHP_EOL, FILE_APPEND );
			array_push( $_SESSION['errors'], mysqli_error() );
			return false;
		}
	}

	else{
		file_put_contents( $docroot.'/mysql.log', "[".date("Y-m-d H:i:s")."] [".$_SERVER["SCRIPT_NAME"]."] dbo->fetchNum".PHP_EOL.$sql.PHP_EOL."ERR: ".mysqli_error().PHP_EOL.PHP_EOL, FILE_APPEND );
		array_push( $_SESSION['errors'], mysqli_error() );
		return false;
	}
}
	
	

	//fetch records
	function fetchArray($sql, $hide_error=0){
  global $_SESSION;
  global $docroot;

  $i = 0;

  if ($q = mysqli_query($sql)) {
    while ($rs = mysqli_fetch_assoc($q)) {
      $array[$i] = $rs;
      $i++;
    }
  } else {
    if ($hide_error == 0) {
      array_push($_SESSION['errors'], mysqli_error());
    }

    file_put_contents($docroot.'/mysql.log', "[".date("Y-m-d H:i:s")."] [".$_SERVER["SCRIPT_NAME"]."] dbo->fetchArray".PHP_EOL.$sql.PHP_EOL."ERR: ".mysqli_error().PHP_EOL.PHP_EOL, FILE_APPEND );
  }

  mysqli_free_result($q); // Free the result set (optional for memory management)

  return $array;
}

	
	//fetch records (numero al posto del nome del campo)
	function fetchRows($sql) {
  global $_SESSION;

  $i = 0;

  if ($q = @mysqli_query($sql)) { // Note: Error suppression (@) discouraged
    while ($rs = mysqli_fetch_row($q)) {
      $array[$i] = $rs;
      $i++;
    }
  } else {
    if ($hide_error === 0) { // Strict comparison for optional parameter
      array_push($_SESSION['errors'], mysqli_error());
    }
  }

  mysqli_free_result($q); // Free the result set (optional for memory management)

  return $array;
}

	//Returns the last inserted record
	function last_inserted_id(){
		return mysqli_insert_id();
	}
	

// Esegue più query prese da un dump SQL
function multiQuery($filename, $delimiter = ';') {
  global $docroot;

  $inString = false;
  $escChar = false;
  $sql = '';
  $stringChar = '';
  $queryLine = [];
  $queryBlock = file_get_contents($filename);

  // Convert encoding if necessary (assuming UTF-8 output)
  if (mb_detect_encoding($queryBlock) !== 'UTF-8') {
    $queryBlock = mb_convert_encoding($queryBlock, "iso-8859-1", "utf-8");
  }

  $sqlRows = explode("\n", $queryBlock);
  $delimiterLen = strlen($delimiter);

  do {
    $sqlRow = current($sqlRows) . "\n";
    $sqlRowLen = strlen($sqlRow);
    for ($i = 0; $i < $sqlRowLen; $i++) {
      // Check for comment lines only outside of strings
      if (substr(ltrim($sqlRow), $i, 2) === '--' && !$inString) {
        break;
      }
      $znak = substr($sqlRow, $i, 1);
      if ($znak === '\'' || $znak === '"') {
        if ($inString) {
          if (!$escChar && $znak === $stringChar) {
            $inString = false;
          }
        } else {
          $stringChar = $znak;
          $inString = true;
        }
      }
      if ($znak === '\\' && substr($sqlRow, $i - 1, 2) !== '\\\\') {
        $escChar = !$escChar;
      } else {
        $escChar = false;
      }
      if (substr($sqlRow, $i, $delimiterLen) === $delimiter) {
        if (!$inString) {
          $sql = trim($sql);
          $delimiterMatch = [];
          if (preg_match('/^DELIMITER[[:space:]]*([^[:space:]]+)$/i', $sql, $delimiterMatch)) {
            $delimiter = $delimiterMatch[1];
            $delimiterLen = strlen($delimiter);
          } else {
            $queryLine[] = $sql;
          }
          $sql = '';
          continue;
        }
      }
      $sql .= $znak;
    }
  } while (next($sqlRows) !== false);

  foreach ($queryLine as $singleQuery) {
    if (!mysqli_query($singleQuery)) {
      // Errore durante l'esecuzione della query
      // Interrompi il ciclo foreach e segnala l'errore
      break;
    }
}
    if (mysqli_errno()) {
      file_put_contents($docroot . '/setup.log', "[". date("Y-m-d H:i:s") . "] " . $filename . PHP_EOL . $singleQuery . PHP_EOL . "ERR: " . mysqli_error() . PHP_EOL . PHP_EOL, FILE_APPEND);
    }
  }
}


	// Chiusura connessione al database
function close(){
		@mysqli_close();
  }

?>
