<?php
	header("Content-Type: text/html; charset=UTF-8");
	ob_start();
	session_start();
	
	$time = microtime();
	$time = explode(' ', $time);
	$time = $time[1] + $time[0];
	$__start = $time;

	ini_set("magic_quotes","Off");
	ini_set("register_globals","Off");
	ini_set( 'date.timezone', 'Europe/Rome' );
	#error_reporting( "E_ALL & ~E_NOTICE" );
	
	
	$script_filename = str_replace( "\\", "/", __FILE__ );	//fix for Windows
	$script_name = substr( $script_filename, strrpos($script_filename, "/")+1, 20  );
	$docroot = preg_replace( "/\/$/", "", str_replace( "\\", "/", $_SERVER['DOCUMENT_ROOT'] ) );
	$rootdir = str_replace( $script_name, "", $script_filename );
	$rootdir = substr( str_replace( $docroot, "", $rootdir ), 0, -1 );
	$docroot = $docroot.$rootdir;
	
	
	
	//short_open_tag deve essere a TRUE
	if( ini_get("short_open_tag")==FALSE ){
		echo "<center><span>Devi impostare la variabile <b>short_open_tag</b> del tuo <b>php.ini</b> a <big><b>On</b></big>.</span></center>\n";
		exit;
	}
	
	//estensione gettext deve essere abilitata 
	if (!function_exists("gettext"))
	{
		echo "<center><span>L&rsquo; estensione gettext non &grave; abilitata. Devi attivare la funzione <b>gettext</b> per far funzionare correttamente Gestionale 360</span></center>\n";
		exit;
	}
		
	@include("config.inc.php");
	
	//Se manca il tema nel config carico quello di default
	if( $theme_path=="" ){
		$theme = "default";
		$theme_path = $rootdir."/share/themes/".$theme;
	}
	
	
	$lang = "it";
	$jscript_modules = array();
	$css_modules = array();
	
	if( !isset($_SESSION['infos']) ){ $_SESSION['infos']=array(); }
	if( !isset($_SESSION['errors']) ){ $_SESSION['errors']=array(); }
	if( !isset($_SESSION['warnings']) ){ $_SESSION['warnings']=array(); }

	include($docroot."/lib/dbo.class.php");
	include($docroot."/lib/functions.php");
	include($docroot."/lib/widgets.class.php");
	include($docroot."/lib/photo.class.php");
	include($docroot."/lib/htmlbuilder.php");
	include($docroot."/lib/modulebuilder.php");
	include($docroot."/lib/html-helpers.class.php");
	include($docroot."/lib/class.phpmailer.php");
	include($docroot."/lib/PHPMailerAutoload.php");
	include($docroot."/lib/class.smtp.php");
	
	$html = new HTMLHelper();

	
//	array_push($css_modules, $theme_path."/css/AdminLTE.min.css");

	array_push($css_modules, $theme_path."/css/bootstrap.min.css");
	array_push($css_modules, $theme_path."/css/font-awesome.min.css");
	array_push($css_modules, $theme_path."/css/AdminLTE.css");
	array_push($css_modules, $theme_path."/css/jQueryUI/jquery-ui-1.10.3.custom.min.css");
	array_push($css_modules, $theme_path."/css/datatables/dataTables.bootstrap.css");
	array_push($css_modules, $theme_path."/css/daterangepicker/daterangepicker-bs3.css");
	array_push($css_modules, $theme_path."/css/timepicker/bootstrap-timepicker.min.css");
	array_push($css_modules, $theme_path."/css/parsley/parsley.css");
	array_push($css_modules, $theme_path."/css/chosen.css");
	array_push($css_modules, $theme_path."/css/tooltipster/tooltipster.css");
	array_push($css_modules, $theme_path."/css/tooltipster/tooltipster-shadow.css");
	array_push($css_modules, $theme_path."/css/style.css");

	
	// array_push($css_modules, $theme_path."/css/AdminLTE.min.css");
	
	
	array_push($jscript_modules, $rootdir."/lib/jscripts/jquery/jquery.js");
	array_push($jscript_modules, $rootdir."/lib/jscripts/bootstrap.min.js");
	array_push($jscript_modules, $rootdir."/lib/jscripts/jquery/jquery-ui.min.js");
	array_push($jscript_modules, $rootdir."/lib/jscripts/jquery/jquery.ui.datepicker-".$lang.".js");
	array_push($jscript_modules, $rootdir."/lib/jscripts/jquery/plugins/datatables/jquery.dataTables.js");
	array_push($jscript_modules, $rootdir."/lib/jscripts/jquery/plugins/datatables/dataTables.bootstrap.js");
	array_push($jscript_modules, $rootdir."/lib/jscripts/jquery/chosen.jquery.js");
	array_push($jscript_modules, $rootdir."/lib/jscripts/jquery/plugins/parsley/parsley.min.js");
	array_push($jscript_modules, $rootdir."/lib/jscripts/jquery/plugins/parsley/parsley.it.js");
	array_push($jscript_modules, $rootdir."/lib/jscripts/jquery/plugins/moment/moment.min.js");
	array_push($jscript_modules, $rootdir."/lib/jscripts/jquery/plugins/daterangepicker/daterangepicker.js");
	array_push($jscript_modules, $rootdir."/lib/jscripts/jquery/plugins/timepicker/bootstrap-timepicker.min.js");
	array_push($jscript_modules, $rootdir."/lib/jscripts/jquery/plugins/input-mask/jquery.inputmask.js");
	array_push($jscript_modules, $rootdir."/lib/jscripts/jquery/plugins/input-mask/jquery.inputmask.extensions.js");
	array_push($jscript_modules, $rootdir."/lib/jscripts/jquery/plugins/input-mask/jquery.inputmask.date.extensions.js");
	array_push($jscript_modules, $rootdir."/lib/jscripts/jquery/plugins/input-mask/jquery.inputmask.numeric.extensions.js");
	array_push($jscript_modules, $rootdir."/lib/jscripts/jquery/plugins/autosize/autosize.min.js");
	array_push($jscript_modules, $rootdir."/lib/jscripts/app.js");
	array_push($jscript_modules, $rootdir."/lib/jscripts/jquery/jquery.tooltipster.min.js");
	
	( $_GET['id_record'] == '' ) ? $type='controller' : $type='editor';
	array_push($jscript_modules, $rootdir."/lib/jscripts/functionsjs.php?id_module=".$_GET['id_module']."&id_record=".$_GET['prev_record']."&type=".$type);

		
	//Imposto il periodo di visualizzazione record dal 01-01-yyy al 31-12-yyyy
	if( !isset($_SESSION['period_start']) ){
		$_SESSION['period_start'] = date("Y")."-01-01";
		$_SESSION['period_end'] = date("Y")."-12-31";
	}
	
	if( isset($_GET['period_start']) ){
		$_SESSION['period_start'] = $html->form('period_start');
		$_SESSION['period_end'] = $html->form('period_end');
	}
	

	// devo verificare che esista il file config.inc.php per connettermi
	if( $db_name!='' ){

		$dbo = new Dbo();
		$dbo_state = $dbo->connect();		

		if( $dbo_state == "ok" ){
		
			// se sono ancora in fase di installazione, zz_modules non esiste ancora!
			$result = $dbo->fetchArray("SHOW TABLES LIKE 'zz_modules'");
			$tableExists = sizeof($result) > 0;
			if( $tableExists ){

				/*
					Creazione array con l'elenco dei moduli
					es. $modules['Anagrafiche']['nome_campo'];
				*/
				$rs = $dbo->fetchArray("SELECT * FROM zz_modules");
				$modules_info = array();
				
				for( $i=0; $i<sizeof($rs); $i++ ){
					foreach( $rs[$i] as $idx => $value ){
						$modules_info[ $rs[$i]['name'] ][$idx] = $value;
					}
				}			
			} // end if $tableExists
			
		}

	} 
	

	//Creazione widget
	$Widget = new Widgets();

?>
