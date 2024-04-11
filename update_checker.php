<?php

//////////////////////////////////////
// test MySQL
	
	// se la configurazione esiste, tento la connessione
	if( file_exists("config.inc.php") ){

		$err = $dbo->connect();

if (is_string($err)) {
  echo "Errore: " . $err;
} else {
  // Connessione riuscita, procedere con il controllo aggiornamenti usando $dbo->database
  // ... la tua logica di controllo aggiornamenti ...
}
	
		//Se non ci si collega al db mostro l'errore...
		#if( $err != "ok" ){
		#	echo "<div class='ui-state-error ui-corner-all' style='width:600px; margin:auto; padding:10px;'>\n";
		#	echo _("Impossibile connettersi al database. Errore di connessione.<br />Probabilmente il servizio MySQL non &egrave; attivo.<br />Premi F5 per riprovare.")."<br /><br />\n";
		#	echo "connessione</div>\n";
		#	exit();
		#}
		

	} 
//////////////////////////////////////


	$action   = $_GET['action'];

	// se ci sono in corso operazioni sulla pagina index.php e non su questa pagina, ritorno a index
	if( ($op!='') && ($action=='') ) return;
	

	echo "<div style='text-align:center; width:800px; margin:auto; color:#333;'>\n";

	/*
		Script per l'aggiornamento del database dopo gli aggiornamenti del programma
	*/
	$file_to_search_old	= '';
	$file_to_search		= '';
	$module_to_update	= '';


	// Creazione file di configurazione
	if( $action=="updateconfig" ){
	
		$new_config = "";
		$db_host = save( $_POST['db_host'] );
		$db_name = save( $_POST['db_name'] );
		$_SESSION['osm_password'] = save( $_POST['osm_password'] );
		$_SESSION['osm_email'] = save( $_POST['osm_email'] );
		$db_username = save( $_POST['db_username'] );
		$db_password = save( $_POST['db_password'] );
		

		$new_config  = "<?php\n";
		$new_config .= "\t\$script_filename = str_replace( \"\\\\\", \"/\", __FILE__ );	//fix for Windows\n";
		$new_config .= "\t\$script_name = substr( \$script_filename, strrpos(\$script_filename, \"/\")+1, 20  );\n";
		$new_config .= "\t\$docroot = preg_replace( \"/\\/\$/\", \"\", str_replace( \"\\\\\", \"/\", \$_SERVER['DOCUMENT_ROOT'] ) );\n";
		$new_config .= "\t\$rootdir = str_replace( \$script_name, \"\", \$script_filename );\n";
		$new_config .= "\t\$rootdir = substr( str_replace( \$docroot, \"\", \$rootdir ), 0, -1 );\n";
		$new_config .= "\t\$docroot = \$docroot.\$rootdir;\n\n";
		
		$new_config .= "\t\$theme = \"default\";\n";
		$new_config .= "\t\$theme_path = \$rootdir.\"/share/themes/\".\$theme;\n\n";
		
		$new_config .= "	#es.\t\$backup_dir =\t\$docroot.\"/backup/\";\n";
		$new_config .= "\t\$backup_dir = \"\";\n";
		$new_config .= "\t\$auto_backup = false;\n";
		
		$new_config .= "\t\$db_engine = \"mysql\";\n";
		$new_config .= "\t\$db_host = \"$db_host\";\n";
		$new_config .= "\t\$db_username = \"$db_username\";\n";
		$new_config .= "\t\$db_password = \"$db_password\";\n";
		$new_config .= "\t\$db_name = \"$db_name\";\n";
		$new_config .= "?>";


		//Se riesco a scrivere il file di configurazione e a connettermi al db avvio la procedura di aggiornamento db
		if( file_put_contents( "config.inc.php", $new_config ) ){
			
			$dbo = new Dbo();
			$err = $dbo->connect();
			
			//Se non ci si collega al db mostro l'errore...
			if( $err != "ok" ){
				echo "<div class='ui-state-error ui-corner-all' style='width:600px; margin:auto; padding:10px;'>\n";
				echo _("Errore nei dati di connessione al database!")."<br /><br />\n";
				echo $err."</div><br />\n";
				unlink( "config.inc.php" );
				$db_name = "";
			}
		}
		
		//Se il file config non è scrivibile mostro un errore
		else{
			echo "<div class='ui-state-error ui-corner-all' style='width:600px; margin:auto; padding:10px;'>Sembra che non ci siano i permessi di scrittura sul file <b>config.inc.php</b></div>\n";
		}
	}



	//Leggo se ci sono dei file di aggiornamento dei moduli
	$modules = array();
	$handle = opendir( $docroot );
	while( false !== ( $file = readdir($handle) ) ){ 
		if( is_file( $docroot."/".$file) && preg_match( "/VERSION_([a-zA-Z0-9_]+)/", $docroot."/".$file, $m ) ){
			array_push( $modules, $m[1] );
		}
	}
	closedir($handle);


	$is_db_installed = 'no';
	$osm_installed = '0';

	// [CORE] la classe dbo non viene creata se manca ancora il file config.inc.php 
	if( $dbo_state == "ok" ) {

		// Verifico se il database è già installato
		$is_db_installed = $dbo->fetchNum("SHOW TABLES LIKE 'zz_modules'");

		
		// Verifico se il database è già stato marcato come installato se c'è la tabella `zz_impostazioni`
		// if( $dbo->fetchNum("DESCRIBE zz_impostazioni", 1)==true ){
		if( $dbo->fetchNum("SHOW TABLES LIKE 'zz_impostazioni'")>0 ){
			$osm_installed = get_var("osm_installed");
		}

	}
	else{
		$osm_installed = '0';
	}

	
	
	//Ges360 non ancora configurato: dò la possibilità di impostare il config.inc.php
	if( $db_name=="" ){
		echo "<div style='text-align:center; width:800px; margin:auto; color:#333;'>\n";
		echo "	<a name='top'></a>\n";
		echo "	<script>$(document).ready( function(){ $('#login-box').hide(); });</script>\n";
	?>
		<!-- controlli per essere sicuro che l'utente abbia letto la licenza -->
		<script>
			$(document).ready( function(){ 
				/*
				$('#licenza').scroll(function () {
					if ( $(this).scrollTop() >= $(this)[0].scrollHeight - 300) {
						$('#agree').removeAttr('disabled');
					}
				});
				
				$('#link-licenza').click(function () {
					$('#agree').removeAttr('disabled');
				});
				*/
				
				
				$('#login-box').hide();
				
				
				$("#installazione").steps({
					
					headerTag: "h3",
					bodyTag: "section",
					transitionEffect: "slideLeft",
					//stepsOrientation: "vertical",
					
					/* Behaviour */
					autoFocus: false,
					enableAllSteps: false,
					enableKeyNavigation: true,
					enablePagination: true,
					suppressPaginationOnFocus: true,
					enableContentCache: true,
					enableCancelButton: false,
					enableFinishButton: true,
					preloadContent: true,
					showFinishButtonAlways: false,
					forceMoveForward: false,
					saveState: false,
					startIndex: 0,
					
					  /* Transition Effects */
					transitionEffect: $.fn.steps.transitionEffect.none,
					transitionEffectSpeed: 400,

					  /* Events */
					onStepChanging: function (event, currentIndex, newIndex) {
						if (newIndex==1){ 
							if( !$('#agree').is(':checked') ){ 
								alert("<?php echo _("Prima di proseguire devi leggere e accettare la licenza.") ?>");
								$( "#agree" ).focus();
							}else{
								return true;
							}
						}
					},
					onStepChanged: function (event, currentIndex, priorIndex) { if (currentIndex==1){  $('#config_form').parsley(); } }, 
					onCanceled: function (event) { },
					onFinishing: function (event, currentIndex) { $('#config_form').submit(); }, 
					onFinished: function (event, currentIndex) {  },
	
					/* Labels */
					labels: {
						cancel: "Ricomincia",
						current: "step corrente:",
						pagination: "Paginazione",
						finish: "INSTALLA",
						next: "Successivo",
						previous: "Precedente",
						loading: "Caricamento ..."
					}
				
				
				});
				

		
				
			});
		</script>
		
		
		<?php
		
		echo "	<div class='row'>\n";
		echo "		<div class='col-md-6'>\n";
		echo "			<img class=\"pull-left\" src=\"".$rootdir."/share/themes/default/img/logo.png\" alt=\"Gestionale360\" />\n";
		echo "			<h1>"._("Gestionale360")."</h1>\n";
		echo "		</div>\n";
		echo "	</div><br>\n";
		
		//INSTALLAZIONE
		echo "<div style=\"text-align:center; width:800px; margin:auto; color:#333;\" id=\"installazione\" >\n";
		echo "	<a name='top'></a>\n";
		
		//SECTION LICENZA
		echo "<h3>"._("Licenza")."</h3>\n";
		echo "<section>\n";
		//echo "	<hr>\n";
		echo "	<textarea id='licenza'  class='form-control' readonly='true' rows='15'>".file_get_contents("LICENSE")."</textarea><br>\n";
		echo "	<a class='pull-left' id='link-licenza' href='http://katolaz.homeunix.net/gplv3/gplv3-".$lang."-final.html' target='_blank'>[ "._("Versione italiana")." ]</a><br><br>\n";
		
		echo "	<div class='row'>\n";
		echo "		<div class='col-md-8'>\n";
		echo "			<span class='pull-left'  title="._("Visiona&nbsp;e&nbsp;accetta&nbsp;la&nbsp;licenza&nbsp;per&nbsp;proseguire")." >"._("Accetti la licenza GPLv3 di Gestionale360?")."*</span>\n";
		echo "		</div>\n";
		echo "		<div class='col-md-4'>\n";
		echo "			<input type='checkbox' id='agree' name='agree'><label for='agree'>&nbsp;"._("Ho visionato e accetto").".</label>\n";
		echo "		</div>\n";
		echo "	</div>\n";
		echo "</section>\n";
		
		//echo "	<hr>\n";
		
		//SECTION PARAMETRI
		echo "<h3>"._("Configurazione")."</h3>\n";
		echo "<section>\n";
		
		echo "	<big>"._("Non hai ancora configurato Gestionale360").".</big><br>\n";
		echo "	<small class='help-block'>"._("Configura correttamente il software con i parametri di seguito (modificabili successivamente dal file <b>config.inc.php</b>)")."</small><br>\n";
		
		//Form dei parametri
		echo "	<form action='index.php?action=updateconfig&firstuse=true' method='post' id='config_form' class='col-md-12 col-md-offset-0'>\n";
		
		echo "		<div class='row'>\n";
		
		//db_host
		echo "			<div class='col-md-12'>\n";
		echo "				"._("Database host")."*:<br>\n";
		echo "				<input type='text' class='form-control' title=\""._("Digita l'indirizzo host del database su cui installare Gestionale 360")."\" required placeholder='Indirizzo database host...' name='db_host' id='db_host' value='".$db_host."'>\n";
		echo "				<span class='help-block pull-left'><small>"._("Es:")." <em>localhost</em></small></span><br><br>\n";
		echo "			</div>\n";
		echo "		</div>\n";
		
		echo "		<div class='row'>\n";
		
		//db_username
		echo "			<div class='col-md-4'>\n";
		echo "			"._("Database username")."*:<br>\n";
		echo "			<input type='text' class='form-control' title=\""._("Digita il nome utente per connettersi al database MySQL")."\" required placeholder='Nome utente MySQL...' name='db_username' id='db_username' value='".$db_username."'>\n";
		echo "			<span class='help-block pull-left'><small>"._("Es:")." <em>root</em></small></span><br><br>\n";
		echo "		</div>\n";

		//db_password
		echo "		<div class='col-md-4'>\n";
		echo "			"._("Database password").":<br>\n";
		echo "			<input type='password' class='form-control' title=\""._("Digita la password per connettersi al database MySQL")."\" placeholder='Password utente MySQL...' name='db_password' value='".$db_password."'>\n";
		echo "			<span class='help-block pull-left'><small>"._("Es:")." <em>mysql</em></small></span><br><br>\n";
		echo "		</div>\n";
		
		//db_name
		echo "		<div class='col-md-4'>\n";
		echo "			"._("Nome database")."*:<br>\n";
		echo "			<input type='text' class='form-control' title=\""._("Digita il nome del tuo database su cui installare Gestionale 360")."\" required placeholder='Nome database...' name='db_name' id='db_name' value='".$db_name."'>\n";
		echo "			<span class='help-block pull-left'><small>"._("Es:")." <em>Gestionale360</em></small></span><br><br>\n";
		echo "		</div>\n";
		echo "	</div>\n";
		
		echo "	<div class='row'>\n";
		
		//Password utente admin
		echo "		<div class='col-md-6'>\n";
		echo "			<b>"._("Password utente \"admin\"").":</b>\n";
		echo "			<input type='password' class='form-control' title=\""._("Digita una password per accedere a Gestionale 360 dopo l'installazione")."\" name='osm_password' placeholder='Scegli una password per accedere a Gestionale 360...' value='".$_SESSION['osm_password']."'>\n";
		echo "		</div>\n";
		
		//Email utente admin
		echo "		<div class='col-md-6'>\n";
		echo "			<b>"._("Email utente principale").":</b>\n";
		echo "			<input type='text' class='form-control' name='osm_email' title=\""._("Digita il tuo indirizzo email")."\" placeholder='Digita il tuo indirizzo email...' value='".$_SESSION['osm_email']."'>\n";
		echo "		</div>\n";
		echo "	</div>\n";
		
		/*echo "	<div class='row'>\n";
		echo "		<div class='col-md-12'>\n";
		echo "			<span class=\"pull-right\" >*<small><small>"._("Campi obbligatori")."</small></small></span><br><br>\n";
		echo "			<button id=\"salva\" type=\"button\" class='btn btn-primary col-md-3 pull-right' ><i class='fa fa-check'></i> "._("Salva e continua")."</button><br><br><br>\n";
		echo "		</div>\n";
		echo "	</div>\n";*/
		
		echo "	</form>\n";
		echo "</section>\n";
		
		
		echo "</div>\n";
		//FINE INSTALLAZIONE
	}



	//prima installazione: forzo gli aggiornamenti dalla prima release a quella attuale se ho configurato il db
	if( $is_db_installed == 'no' && $db_name!="" && $action!='do_update' ){
		if( $osm_installed=='0' ){
			echo "<script>$('#login-box').hide();</script>\n";

			echo "<br><span><big>"._("E' la prima volta che avvii Gestionale360 e non hai ancora installato il database").".</big></span><br><br>\n";

			//Forzo gli aggiornamenti dalla prima release
			file_put_contents( "VERSION.old", "base" );

			$frase_button = _("Installa!");
		}
	}
	
	else{
		$frase_button = _("Aggiorna!");
	}
	
	
	
	
	//Cerco se ci sono aggiornamenti di moduli da eseguire e li aggiungo alla lista dei moduli da aggiornare
	if( sizeof($modules) > 0 ){
		if( file_exists( $docroot."/VERSION_".$modules[0] ) ){
			$file_to_search_old	= "VERSION_".$modules[0].".old";

			//Se il file contenente la versione precedente del modulo non c'è, lo creo per installare il modulo da zero
			if( !file_exists($file_to_search_old) ){
				file_put_contents( $file_to_search_old, 'base' );
			}
			$file_to_search		= "VERSION_".$modules[0];
			$module_to_update	= $modules[0];
		}
	}
	
	
	//Se c'è il VERSION.old devo aggiornare prima il "core"
	if( file_exists($docroot."/VERSION.old") ){
		$file_to_search_old	= 'VERSION.old';
		$file_to_search		= 'VERSION';
		$module_to_update	= 'core';
	}


	//Procedo all'aggiornamento solo se selezionato un db
	if( $db_name!='' ){

		//Se c'è un aggiornamento da eseguire creo la tabella `updates` con i relativi aggiornamenti
		if( $action!='do_update' && file_exists($file_to_search) && file_exists($file_to_search_old) ){

			//Creo la tabella degli aggiornamenti
			$dbo->query("DROP TABLE IF EXISTS updates");
			if( $module_to_update=='core' )
				$sql = $docroot."/update/create_updates.sql";
			else
				$sql = $docroot."/modules/".$module_to_update."/update/create_updates.sql";

			$dbo->multiQuery( $sql );



			//Leggo qual è l'ultimo aggiornamento fatto e imposto a `done` tutti gli aggiornamenti già eseguiti
			if( $module_to_update=='core' ){
				$qv = "SELECT idupdate FROM updates WHERE `version`='".getVersion($file_to_search_old)."'";
				$rsv = $dbo->fetchArray( $qv );
				$first_idupdate = $rsv[0]['idupdate'];
			}
			else{
				$qv = "SELECT `idupdate`, (SELECT `new` FROM `zz_modules` AS m WHERE name='".$module_to_update."') AS `new` FROM updates WHERE `version`=(SELECT `version` FROM zz_modules AS m WHERE name='".$module_to_update."') ORDER BY `idupdate` ASC LIMIT 0,1";
				$rsv = $dbo->fetchArray( $qv );

				if( $rsv[0]['new']=='0' )
					$first_idupdate = $rsv[0]['idupdate'];
				else
					$first_idupdate = '-1';
			}

			$dbo->query( "UPDATE `updates` SET `done`=1 WHERE `idupdate` <= \"".$first_idupdate."\"" );
			
			
			if( $frase_button == _("Installa!") ){
				$firstuse = "true";
			}
			else{
				$firstuse = "false";
			}


		//	echo _("E' necessario eseguire l'aggiornamento del database dalla versione")." <b>".getVersion($file_to_search_old)."</b> "._("alla")." <b>".getVersion($file_to_search)."</b> "._("del modulo")." <b>".$module_to_update."</b>.<br>\n";
			echo _("Premi il tasto")." <b>&quot;".$frase_button."&quot;</b> "._("per procedere con l'aggiornamento").":<br><br>\n";
			echo "<input type=\"button\" class=\"btn btn-primary\" value=\"".$frase_button."\" onclick=\"if( confirm('"._("Continuare?")."') ){ $('#progress').show(); $('#result').load('index.php?action=do_update&firstuse=".$firstuse."'); $(this).remove(); }\">\n";

			echo "<div id='progress'>\n";
			echo "	<div class='bar'></div>\n";
			echo "	<div class='text'>0%</div>\n";
			echo "	<div class='info'></div>\n";
			echo "</div>\n";
			echo "<div id='result'></div>\n";
			
			echo "<script>\n";
			echo "	$(document).ready( function(){\n";
			echo "		$('#login-box').fadeOut();\n";
			echo "		$('#progress .bar').progressbar({ value: 0 });\n";
			echo "	});\n";
			echo "</script>\n";
		}






		/*
			Aggiornamento tramite ajax
		*/
		if( $action=='do_update' ){
			//Logout se non eseguito dall'utente
			unset( $_SESSION['idutente'] );
			
			//Seleziono tutti gli aggiornamenti successivi all'attuale e nel ciclo for() eseguo gli script ad uno ad uno
			$qm  = "SELECT * FROM updates WHERE `done`=0 ORDER BY idupdate ASC";
			$rsm = $dbo->fetchArray( $qm );
			$nm  = sizeof($rsm);



			//Nessun aggiornamento database disponibile
			if( $nm == 0 && !isset($_GET['continue']) ){
				echo _("Gestionale360 è aggiornato alla versione")." ".getVersion().".<br>\n";

				//Rimostro la finestra di login
				echo "<script> $('#login-box').fadeIn(); </script>\n";
			}



			//Aggiornamento appena terminato
			else if( $nm == 0 && isset($_GET['continue']) ){
				echo "<big>"._("Aggiornamento del modulo")." ".getVersion( $file_to_search_old )." "._("alla versione")." <b>".getVersion( $file_to_search )."</b> "._("completato")." <i class=\"fa fa-fw fa-smile-o\"></i> </big><br>\n";

				//Imposto la versione corrente nella tabella `zz_modules`
				$dbo->query( "UPDATE zz_modules SET `version`='".getVersion( $file_to_search )."', `new`=0 WHERE name=\"".$module_to_update."\" OR parent=( SELECT id FROM (SELECT id FROM `zz_modules` WHERE name=\"".$module_to_update."\") AS tmp )" );


				//Rimuovo file .old dopo l'aggiornamento
				@unlink( $file_to_search_old );

				//Rimuovo file VERSION_nomemodulo dopo aggiornamento
				if( $module_to_update!='core' )
					@unlink( $file_to_search );

				//Rimostro la finestra di login
				echo "<script> $('#login-box').fadeIn(); </script>\n";
				
				//Istruzioni per la prima installazione
				if( $_GET['firstuse']=='true' ){
					echo "<br>"._("Puoi procedere al login con questi dati:")."<br>"._("Username").": <em>admin</em><br>"._("Password").": <em>".$_SESSION['osm_password']."</em><br><br><span class='text-danger'>"._("Assicurati di togliere i permessi di scrittura del file")." <b>config.inc.php</b>.</span><br><br><button onclick=\"location.href =  '".$rootdir."' \" class=\"btn btn-block btn-success\">Continua</button> <script>$('#login-box').hide();</script> \n";
				}
				
				//Aggiornamento all'ultima release della versione e compatibilità moduli
				$dbo->query("UPDATE `zz_modules` SET `compatibility`='".getVersion()."', `version`='".getVersion()."' WHERE `default`='1';");
				
				//Rimuovo il flag `new` a tutti i moduli
				$dbo->query( "UPDATE `zz_modules` SET `new`=0" );
				
				//Imposto che il db di Ges360 è già stato installato
				$dbo->query( "UPDATE `zz_impostazioni` SET `valore`='1' WHERE nome='osm_installed'" );
				
				//Imposto la password di admin che l'utente ha selezionato all'inizio
				if( isset($_SESSION['osm_password']) ){
					$dbo->query( "UPDATE `zz_utenti` SET `password`=MD5(\"".$_SESSION['osm_password']."\") WHERE `username`='admin' ");
					unset( $_SESSION['osm_password'] );
				}
				
				if( isset($_SESSION['osm_email']) ){
					$dbo->query( "UPDATE `zz_utenti` SET `email`=\"".$_SESSION['osm_email']."\" WHERE `username`='admin' " );
					unset( $_SESSION['osm_email'] );
				}
				
				unset( $_SESSION['errors'] );
			}



			//Aggiornamento in progresso
			else{
				//Esecuzione query release
				if( $rsm[0]['filename'] != '' ){
					if( $module_to_update=='core' )
						$sql = $docroot."/update/".$rsm[0]['filename'];
					else
						$sql = $docroot."/modules/".$module_to_update."/update/".$rsm[0]['filename'];
					
					
					//Leggo quanti aggiornamenti totali ci sono da fare
					$rsc = $dbo->fetchArray( "SELECT COUNT(idupdate) AS da_fare FROM `updates` WHERE `idupdate` > (SELECT `idupdate` FROM updates WHERE `version`='".getVersion($file_to_search_old)."') AND `idupdate`<=(SELECT `idupdate` FROM updates WHERE `version`='".getVersion()."')" );
					$da_fare = $rsc[0]['da_fare'];


					//Leggo quanti aggiornamenti sono stati fatti
					$rsc = $dbo->fetchArray( "SELECT COUNT(idupdate) AS fatti FROM `updates` WHERE `idupdate` > (SELECT `idupdate` FROM updates WHERE `version`='".getVersion($file_to_search_old)."') AND `idupdate`< '".$rsm[0]['idupdate']."'" );
					$fatti = $rsc[0]['fatti'] + 1;
					$percent = intval($fatti/$da_fare*100);
					
					$dbo->multiQuery( $sql );
					echo "<script>\n";
					echo "	$('#progress .bar').children().show().width('".$percent."%');\n";
					echo "	$('#progress .text').text('".$percent."%');\n";
					
					echo "	$('#progress .info').html( $('#progress .info').html() + 'Aggiornamenti fatti: ".$fatti." su ".$da_fare." da fare...<br>' );\n";
					
					echo "	$('#progress .info').html( $('#progress .info').html() + '<small> >>> "._("aggiornamento versione")." <b>".$rsm[0]['version']."</b> (<span style=\"color:#000;\"><small>".$rsm[0]['filename']."</small></span>) <i class=\"fa fa-fw fa-check\"></i> </small><br>' );\n";
					
					echo "</script>\n";
				}


				//Esecuzione script release
				if( $rsm[0]['script'] != '' ){
					if( $module_to_update=='core' )
						$script = $docroot."/update/".$rsm[0]['script'];
					else
						$script = $docroot."/modules/".$module_to_update."/update/".$rsm[0]['script'];


					if( file_exists($script) ){
						echo "<script> $('#progress .info').html( $('#progress .info').html()+'<small> >>> "._("esecuzione script")." <b>".$rsm[0]['script']."</b> <i class=\"fa fa-fw fa-check\"></i> </small>' ); </script>\n";
						include_once($script);
					}
				}


				//Imposto questo aggiornamento come eseguito e ricarico il div per fare il successivo
				$dbo->query("UPDATE `updates` SET `done`=1 WHERE idupdate=\"".$rsm[0]['idupdate']."\"");
				echo "<script>$('#result').load('index.php?action=do_update&continue&firstuse=".$_GET['firstuse']."');</script>\n";
			}
			exit;
		}
	}
	echo "</div>\n";
?>