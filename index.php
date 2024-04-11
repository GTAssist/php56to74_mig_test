<?php
	include("core.php");
	
	$username = save($_POST['username']);
	$password = save($_POST['password']);
	$op = $_GET['op'];
	
	// [CORE] la classe dbo non viene creata se manca ancora il file config.inc.php 
	if( $dbo_state == "ok" ){

		//LOGIN
		switch ( $op ){
			case 'login':
				$query = "SELECT *, (SELECT nome FROM zz_gruppi WHERE id=idgruppo) AS gruppo FROM zz_utenti WHERE username=\"$username\" AND password=MD5(\"".$password."\")";
				$rs = $dbo->fetchArray( $query );
				
				//loggo gli accessi
				logaccessi($rs[0]['idutente'], $username, $password, sizeof($rs), $rs[0]['enabled'], $rs[0]['gruppo'] );
				
				if( sizeof($rs) <= 0 ){
					array_push( $_SESSION['errors'], _("Autenticazione fallita!") );
				}

				else if( $rs[0]['enabled']==0 ){
					array_push( $_SESSION['errors'], _("Utente non abilitato!") );
				}
				
				else{
					if( $_POST['keep_alive']=='on' ){
						$_SESSION['keep_alive'] = true;
					}
					
					$_SESSION['idutente'] = $rs[0]['idutente'];
					$_SESSION['idanagrafica'] = $rs[0]['idanagrafica'];
					$_SESSION['username'] = $rs[0]['username'];
					$_SESSION['gruppo'] = $rs[0]['gruppo'];
				
					if( $rs[0]['gruppo']=='Amministratori' ){
						$_SESSION['is_admin'] = true;
					}
				
					else{
						$_SESSION['is_admin'] = false;
					}
				
					//Auto backup del database giornaliero
					if( get_var("Backup automatico") ){
						
						/*$files  = glob( $backup_dir.'*.zip' );
						$regexp = '/'.date('Y\-m\-d').'/';

						//Controllo se esiste già un backup zip creato per oggi
						if ( !empty( $files ) ){
							$found = false;
							foreach ($files as $file ){
								if (preg_match( $regexp, $file, $matches )){
									$found = true;
								}
							}
						}*/
						
						$folders  =  glob( $backup_dir.'*' );
						$regexp = '/'.date('Y\-m\-d').'/';
						
						//Controllo se esiste già un backup zip o folder creato per oggi
						if ( !empty( $folders ) ){
							$found = false;
							foreach ($folders as $folder ){
								if (preg_match( $regexp, $folder, $matches )){
									$found = true;
								}
							}
						}
						
						
						if( $found ){
							
							array_push( $_SESSION['infos'], "Backup saltato perché già esistente!" );

						}else{
							
							if( do_backup() ){
									array_push( $_SESSION['infos'], "Backup automatico eseguito correttamente!" );
							}
							else{
								if ($backup_dir=="") { 
									array_push( $_SESSION['errors'], "<b>ATTENZIONE:</b> la cartella di backup non esiste! Non è possibile eseguire i backup!" );
								}else{
									if( !file_exists($backup_dir) ){
										if (mkdir( $backup_dir )){
											array_push( $_SESSION['infos'], "<b>INFORMAZIONE:</b> la cartella di backup è stata creata correttamente." );
											do_backup();
										}else{
											array_push( $_SESSION['errors'], "<b>ATTENZIONE:</b> non è stato possibile creare la cartella di backup!" );
										}
									}
								}
							}
						}
						
					}
				
					//Redirect al primo modulo abilitato su cui l'utente ha accesso
					if( $_SESSION['is_admin'] ){
						$q = "SELECT id as idmodulo, IF(options IS NULL or options = '', (SELECT id FROM zz_modules WHERE level='1' AND enabled='1' AND type='menu' AND parent = idmodulo ORDER BY `order` ASC LIMIT 0,1), id) AS id, module_dir, options FROM zz_modules WHERE level='0' AND enabled='1' AND type='menu' ORDER BY `order` ASC";
					}
				
					else{
						$q = "SELECT id as idmodulo, IF(options IS NULL or options = '', (SELECT id FROM zz_modules WHERE level='1' AND enabled='1' AND id IN (SELECT idmodule FROM zz_permessi WHERE idgruppo=(SELECT id FROM zz_gruppi WHERE nome='".$rs[0]['gruppo']."') AND permessi IN ('r', 'rw') ) AND type='menu' AND parent = idmodulo ORDER BY `order` ASC LIMIT 0,1), id) AS id, module_dir, options FROM zz_modules WHERE level='0' AND enabled='1' AND id IN (SELECT idmodule FROM zz_permessi WHERE idgruppo=(SELECT id FROM zz_gruppi WHERE nome='".$rs[0]['gruppo']."') AND permessi IN ('r', 'rw') ) AND type='menu' ORDER BY `order` ASC";
					}

					$rs = $dbo->fetchArray($q);

					if( sizeof($rs) <= 0 && !$_SESSION['is_admin'] ){
						array_push( $_SESSION['errors'], _("L'utente non ha nessun permesso impostato!") );
					}
				
					else{
						for( $i=0; $i<sizeof($rs); $i++ ){
							if( $rs[$i]['options'] != '' ){
								redirect( $rootdir."/controller.php?id_module=".$rs[0]['id'], "js" );
								exit;
							}
						}
					}
				}
				break;

			case 'logout':
				
				session_destroy(); // destroy session
				setcookie("PHPSESSID","",time()-3600,"/"); // delete session cookie
			
				break;
		}
	
	
		//Redirect al primo modulo su cui l'utente ha accesso se l'utente è già loggato
		if( isset($_SESSION['idutente']) ){
			if( $_SESSION['is_admin'] )
				$q = "SELECT id, module_dir, options FROM zz_modules WHERE level='0' AND enabled='1' ORDER BY `order` ASC";
			else
				$q = "SELECT id, module_dir, options FROM zz_modules WHERE level='0' AND enabled='1' AND id IN (SELECT idmodule FROM zz_permessi WHERE idgruppo=(SELECT id FROM zz_gruppi WHERE nome='".$rs[0]['gruppo']."') AND permessi IN ('r', 'rw') ) ORDER BY `order` ASC";
			
			$result = mysqli_query("SHOW TABLES LIKE 'zz_modules'");		
			if (mysqli_num_rows($result) > 0){
				$rs = $dbo->fetchArray($q);
			}

			if( sizeof($rs) <= 0 && !$_SESSION['is_admin'] ){
				if ($op!='logout')
					array_push( $errors, _("L'utente non ha nessun permesso impostato!") );
				else
					array_push( $infos, _("Arrivederci!") );
			}
		
			else{
				for( $i=0; $i<sizeof($rs); $i++ ){
					if( $rs[$i]['options'] != '' ){
						redirect( $rootdir."/controller.php?id_module=".$rs[0]['id'], "js" );
						exit;
					}
				}
			}
		}

	} // end if is_a $dbo
	
	
	
?><!DOCTYPE html>
<html class="bg-black">
    <head>
        <meta charset="UTF-8">
        <title>Gestionale360 Login</title>
        <meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>
        <link href="<?php echo $theme_path ?>/css/bootstrap.min.css" rel="stylesheet" type="text/css">
        <link href="<?php echo $theme_path ?>/css/font-awesome.min.css" rel="stylesheet" type="text/css">
        <link href="<?php echo $theme_path ?>/css/AdminLTE.css" rel="stylesheet" type="text/css">
        <link href="<?php echo $theme_path ?>/css/jQueryUI/jquery-ui-1.10.3.custom.min.css" rel="stylesheet" type="text/css">
		<link href="<?php echo $theme_path ?>/css/jquery.steps/jquery.steps.css" rel="stylesheet" type="text/css">
		<link href="<?php echo $theme_path ?>/css/parsley/parsley.css" rel="stylesheet" type="text/css">
        <link href="<?php echo $theme_path ?>/css/style.css" rel="stylesheet" type="text/css">
		
	<!--
		<link href="data:image/x-icon;base64,AAABAAEAEBAAAAEAIABoBAAAFgAAACgAAAAQAAAAIAAAAAEAIAAAAAAAAAQAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABOW5ykAAAAAFYfm/xZ/5a4AAAAAGHDkWgAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAABGe55cTluf8AAAAABWH5v8Wf+W2AAAAABhw5P8AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAD67o/wAAAAAOda0KE5bn/xSP5v8Vh+b/Fn/l/xd35f8YcOT/Aw0dARRHqxIcWOPWAAAAAAAAAAAAAAAAAAAAAA+u6LwQpuj/Ep7n/xOW5/8Uj+b/FYfm/xZ/5f8Xd+X/GHDk/xpo5P8bX+P/FUKqKQAAAAAAAAAAAAAAAAVEVwILga4cEKbo/xKe5/8Tluf/FI/m/xWH5v4Wf+X+F3fl/xhw5P8aaOT/G1/j/wAAAAASMo41AAAAAA2+6aoOtuj/D6/o/xCm6P8Snuf/E5bn9w9rrQEAAAAAAAAAABRnyAMYcOT7Gmjk/xtf4/8cWOP/HVDj/xMujWwUMVr/FDFa/xQxWv8UU5j/FFOY/xQ0YP8UPnP/FFOY/xRTmP8UMl3/FEqG/xQxWv8UMFn/E0mF/xQxWv8UMVr/FDFa/xQxWv8TU5b/FDFa/xQxWv8TXKf/FDFa/xQxWv8UMVr/E3fY/xJ53P8ShO3/Ennd/xJ74P8UMVr/FDFa/xQxWv8UMVr/E1OW/xQxWv8UMVr/E1yn/xNvyv8UMVr/FDFa/xQxWv8SeNv/FDJb/xQxWv8ShvP/FDFa/xQxWv8UMVrzFDFa8xQ3Yf8UN2L/FDdi/xQxWvMUMVrzFDJc8xQyXPMUMVrzFDJb8xQ0Yf8UM2H/FTVj/xQxWvMUMVrzDb3pRw626P0Pr+j/EKbo/xKe5/8Tluf9AAAAAAAAAAAAAAAAAAAAABhv5P8aaOT/G1/j/xxY4/8dUOP/Ey6NYwAAAAAOtuicD63oehCm6P8Snuf/E5bn/xSP5v8Vh+b9Fn/l/Rd35f8YcOT/Gmjk/xtf4/8VQqoYFjyqSxMvjQIAAAAAAAAAAAIWHQ8Qpuj/Ep7n/xOW5/8Uj+b/FYfm/xZ/5f8Xd+X/GHDk/xpo5P8bX+P/BAscCwAAAAAAAAAAAAAAAAAAAAAPr+j3DH2uSxKe55wTluf/FI/m/xWH5v8Wf+X/F3fl/xhw5P8UTqskG1/kWBxY4/YAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAASnOcQE5bn/xJ8yRQVh+b/Fn/l8xRnyBMYcOT/EUKPIAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAC2KQAxOW59cAAAAAFYfm/xZ/5a4AAAAAGHDkvwAAAAAAAAAAAAAAAAAAAAAAAAAA/n8AAPJfAADYGwAAwAcAAOAHAAADwQAAAAAAAAAAAAAAAAAAAAAAAIPBAACgBwAA4AcAANAbAAD6XwAA+l8AAA==" rel="icon" type="image/x-icon" />
	-->	
        <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
        <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
        <!--[if lt IE 9]>
          <script src="<?php echo $rootdir ?>/lib/jscripts/html5shiv.js"></script>
          <script src="<?php echo $rootdir ?>/lib/jscripts/respond.min.js"></script>
        <![endif]-->
        
        <script src="<?php echo $rootdir ?>/lib/jscripts/jquery/jquery.js"></script>
        <script src="<?php echo $rootdir ?>/lib/jscripts/jquery/jquery-ui.min.js"></script>
		<script src="<?php echo $rootdir ?>/lib/jscripts/jquery/plugins/jquery.steps/jquery.steps.js"></script>
		<script src="<?php echo $rootdir ?>/lib/jscripts/jquery/jquery.cookie-1.3.1.js"></script>
        <script src="<?php echo $rootdir ?>/lib/jscripts/bootstrap.min.js"></script>
		<script src="<?php echo $rootdir ?>/lib/jscripts/jquery/jquery.ui.shake.js"></script>
		<script src="<?php echo $rootdir ?>/lib/jscripts/jquery/plugins/parsley/parsley.min.js"></script>
		<script src="<?php echo $rootdir ?>/lib/jscripts/jquery/plugins/parsley/parsley.it.js"></script>
		
		
    </head>
    
    
    <body class="bg-black">
    	<br>
		
    	<?php
	
		#include($docroot."/update/update_checker.php");
		?php
//correzione fetchnum da verificare
// Inclusione dei file necessari
require_once('lib/config.php');
require_once('lib/dbo.class.php');
require_once('functions.php'); // Assuming functions.php is required

// Connessione al database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Controllo della connessione
if ($conn->connect_error) {
  die("Errore di connessione al database: " . $conn->connect_error);
}

// Impostazione del charset
$conn->set_charset("utf8");

// Query per recuperare le informazioni sulle tabelle
$sql = "SHOW TABLES LIKE '%'";

// **Correzione:**
$result = $dbo->fetchNum($sql, $conn); // Passaggio di $conn come secondo argomento

// Controllo del risultato
if (!$result) {
  echo "Errore durante l'esecuzione della query.";
  exit;
}

		$is_db_installed = $dbo->fetchNum("SHOW TABLES LIKE 'zz_modules'",$conn);
    	 
		?>
		<script>
			$(document).ready(function() {
				$('#login').click(function(){
					<?php
					if( $is_db_installed == 1 ){
						if( get_var("Backup automatico") ){
							$str = _("Backup automatico in corso...");
						}
						else{
							$str = _("Autenticazione...");
						}
					}
					else{
						$str = _("Autenticazione...");
					}
					?>
				
					$("#login").val('<?php echo $str ?>...');
				
				});
			});
		</script>
		
		<!-- controllo se è una beta e in caso mostro un warning -->
		<?php
			if (strpos(getVersion(),'beta') !== false) {
		?>
			<script>$(document).ready( function(){ $("#beta").addClass("in"); });</script>
			<div id="beta" class="alert alert-warning alert-dismissable pull-right fade">
				<i class="fa fa-warning"></i>
				<button aria-hidden="true" data-dismiss="alert" class="close" type="button">×</button>
				<b><?php echo _("Attenzione!") ?></b> <?php echo _("Stai utilizzando una versione <b>non stabile</b> di Gestionale 360.") ?>
			</div>
		<?php
			}
		?>		
			
        <div class="form-box" id="login-box">
            <div class="header_login">
				<img src="<?php echo $theme_path ?>/img/logo.png" alt="OSM Logo">
				Gestionale360
			</div>
            <form action="?op=login" method="post">
                <div class="body bg-gray">
                	<?php
					for( $i=0; $i<sizeof($_SESSION['infos']); $i++ ){
						echo "	<div class='alert alert-success'><i class='fa fa-check'></i> ".$_SESSION['infos'][$i]."</div>";
					}
					for( $i=0; $i<sizeof($_SESSION['errors']); $i++ ){
						echo "	<div class='alert alert-danger'><i class='fa fa-warning'></i> ".$_SESSION['errors'][$i]."</div>";
						if ($i==0)
						echo "<script> $(document).ready( function(){ $('#login-box').shake(); }); </script>\n";
					}
					unset( $_SESSION['infos'] );
					unset( $_SESSION['errors'] );
					?>
                    <div class="form-group input-group">
                        <span class="input-group-addon"><i class="fa fa-user"></i> </span>
                        <input type="text" name="username" autocomplete="off" class="form-control" placeholder="<?php echo _("Nome utente") ?>" value="<?php echo $html->form('username','post') ?>">
                    </div>
                    <div class="form-group input-group">
                    	<span class="input-group-addon"><i class="fa fa-lock"></i> </span>
                        <input type="password" name="password" autocomplete="off" class="form-control" placeholder="<?php echo _("Password") ?>">
                    </div>          
                   
                </div>
                <div class="footer">                                                   
                    <input type="submit" id="login" class="btn btn-danger btn-block" value="<?php echo _("Accedi") ?>" />  
                </div>
            </form>
        </div>
        <script>
			$(document).ready( function(){
				if( $('input[name=username]').val() == '' ){
					$('input[name=username]').focus();
				}
				else{
					$('input[name=password]').focus();
				}
			});
		</script>

    </body>
</html>
