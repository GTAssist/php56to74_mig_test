<?php

class HTMLHelper{
	/**
	  * Function to read parameter from GET or POST request, escape it and filter it by its rules:
	  * string		every string
	  * int			integer value
	  * decimal		decimal value (force conversion of commas into points: 0,01 become 0.01)
	  */
	public function form( $param, $method='get', $escape=true, $rule='text' ){
		//method
		if( $method == 'get' ){
			$value = $_GET[$param];
		}
		
		else{
			$value = $_POST[$param];
		}
		
		if( $value == 'undefined' ){
			$value = '';
		}


		//Rules filter
		if( $rule=='int' ){
			$value = intval( $value );
		}

		else if( $rule=='decimal' ){
			$value = str_replace( ",", ".", $value );
		}
		
		else if( $rule=='date' ){
			if( preg_match("/^([0-9]{2})\/([0-9]{2})\/([0-9]{4})$/", $value, $m) ){
				$value = $m[3]."-".$m[2]."-".$m[1];
			}
			
			else if( preg_match("/^([0-9]{1})\-([0-9]{2})\-([0-9]{2})$/", $value, $m) ){
				$value = $m[1]."-".$m[2]."-".$m[3];
			}
		}


		//escape
		if( $escape )
			$value = mysqli_escape_string( $value );

		return $value;
	}
}

?>
