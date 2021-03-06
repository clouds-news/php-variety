<?php
//////////////////////////////////////////////
//                                          //
// EasyWP WordPress Installer v1.2          //
// Copyright Â©2008 - 2010 Michael VanDeMar  //
// http://www.funscripts.net/               //
// All rights reserved.                     //
//                            
// Gilbert changed the name of the file to:  autoinstallwp.php    June 27/2013           //
//////////////////////////////////////////////

function rkg() { // random key generator
	$secret_ar = str_split("0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz!@#$%^&*()-=+[]{};:<>,.?");
	$secret = "";
	for($i=0;$i<66;$i++){
		$secret .= $secret_ar[rand(0,85)];
	}
	return substr($secret,0,64);
}

function getLatestWP(){
	$head = "";

	$fp = @fsockopen("wordpress.org", 80, $errno, $errstr, 15);
	if(!$fp){
		$response[0] = -999;
		$response[1] = "$errstr ($errno).";
		$response[2] = 0;
	}else{
		stream_set_timeout($fp, 5);
		$out = "GET /latest.tar.gz HTTP/1.0\r\n";
		$out .= "Host: wordpress.org\r\n";
		$out .= "User-agent: Bad-Neighborhood Easy Wp Installer (http://www.bad-neighborhood.com/)\r\n";
		$out .= "Connection: Close\r\n\r\n";

		fwrite($fp, $out);
		while(!feof($fp)){
			$head .= fgets($fp, 128);
		}
		fclose($fp);

		$tresponse = split("\r\n\r\n",$head);
		// headers returned
		$response[0] = $tresponse[0];
		// content
		$response[1] = trim(str_replace($tresponse[0], "", $head));
		//parse out response code
		preg_match("|.*\s([0-9]*)\s[^\n]*\n.*|Uis", $response[0], $out);
		if($out[1]!="" && !(is_null($out[1]))){
			$response[2] = $out[1];
		}else{
			$response[2] = 0;
		}
		if($response[2]==200){
			preg_match("/Content-Disposition: attachment; filename=(.*)\n/Ui", $response[0], $fname);
			if($fname[1]!=""){
				$fp = fopen(dirname(__FILE__)."/".trim($fname[1]),"a+");
				if($fp){
					fwrite($fp, $response[1]);
					fclose($fp);
					return trim($fname[1]);
				}else{
					die("Failed to create local file: ".trim($fname[1]));
				}
			}else{
				die("Problem parsing filename.");
			}
		}else{
			die("Problem with downloading file, header returned:\n".$response[0]);
		}
	}
}

$dir = dirname(__FILE__);

if(isset($_POST["process"]) && $_POST["process"]=="true"){
	if(!isset($_POST["doanyways"]) && @mysql_connect($_POST["DB_HOST"], $_POST["DB_USER"], $_POST["DB_PASSWORD"])===FALSE){
?>
<html><head><title>EasyWP WordPress Installer</title>
<style>
.container {
	width: 700px;
	margin: 0px auto;
	font-family: Verdana;
	font-size: 12px;
}
.container input {
	margin-top: 5px;	
}
H2 {
	font-family: Verdana;
	text-align: center;
	margin: 26px;
}
.gray {
	background-color: #F0F0F0;
}	
</style>
</head>
<body>
<h2>EasyWP WordPress Installer</h1>
<div class="container">
<br /><br />
The connection to the database failed with the settings you entered.<br \><br \>
<form action="autoinstallwp.php" method="post">
<input type="hidden" name="doanyways" value="true">
<input type="hidden" name="wpfile" value="<?php echo $_POST["wpfile"]; ?>">
<input type="hidden" name="DB_NAME" value="<?php echo $_POST["DB_NAME"]; ?>">
<input type="hidden" name="DB_USER" value="<?php echo $_POST["DB_USER"]; ?>">
<input type="hidden" name="DB_PASSWORD" value="<?php echo $_POST["DB_PASSWORD"]; ?>">
<input type="hidden" name="DB_HOST" value="localhost" value="<?php echo $_POST["DB_HOST"]; ?>">
<input type="hidden" name="SECRET_KEY" value="<?php echo $_POST["SECRET_KEY"]; ?>">
<input type="hidden" name="AUTH_KEY" value="<?php echo $_POST["AUTH_KEY"]; ?>">
<input type="hidden" name="SECURE_AUTH_KEY" value="<?php echo $_POST["SECURE_AUTH_KEY"]; ?>">
<input type="hidden" name="LOGGED_IN_KEY" value="<?php echo $_POST["LOGGED_IN_KEY"]; ?>">
<input type="hidden" name="NONCE_KEY" value="<?php echo $_POST["NONCE_KEY"]; ?>">
<input type="hidden" name="AUTH_SALT" value="<?php echo $_POST["AUTH_SALT"]; ?>">
<input type="hidden" name="SECURE_AUTH_SALT" value="<?php echo $_POST["SECURE_AUTH_SALT"]; ?>">
<input type="hidden" name="LOGGED_IN_SALT" value="<?php echo $_POST["LOGGED_IN_SALT"]; ?>">
<input type="hidden" name="NONCE_SALT" value="<?php echo $_POST["NONCE_SALT"]; ?>">
<input type="hidden" name="table_prefix" value="<?php echo $_POST["table_prefix"]; ?>">
<input type="radio" name="process" value="false" checked>&nbsp;Let me edit the values.<br />
<input type="radio" name="process" value="true">&nbsp;That's ok, proceed anyways.<br /><br />
<input type="submit" value="Continue">
</form>
<?php
	} else {
		exec("tar xvfz ".$_POST["wpfile"], $buff);
		exec("mv -f ".$dir."/wordpress/* ".$dir."", $buff2);
		exec("rm -rf wordpress", $buff3);
		if(!file_exists(dirname(__FILE__)."/wp-config-sample.php")){
			echo "Operation appears to have failed.<br />\n";
		}else{
			$config = file_get_contents(dirname(__FILE__)."/wp-config-sample.php");

			// pre-3.0 replacements
			$config = str_replace("putyourdbnamehere", $_POST["DB_NAME"], $config);
			$config = str_replace("usernamehere", $_POST["DB_USER"], $config);
			$config = str_replace("yourpasswordhere", $_POST["DB_PASSWORD"], $config);
			$config = str_replace("localhost", $_POST["DB_HOST"], $config);
			$config = str_replace("'SECRET_KEY', 'put your unique phrase here'", "'SECRET_KEY', '".$_POST["SECRET_KEY"]."'", $config);
			$config = str_replace("'AUTH_KEY', 'put your unique phrase here'", "'AUTH_KEY', '".$_POST["AUTH_KEY"]."'", $config);
			$config = str_replace("'SECURE_AUTH_KEY', 'put your unique phrase here'", "'SECURE_AUTH_KEY', '".$_POST["SECURE_AUTH_KEY"]."'", $config);
			$config = str_replace("'LOGGED_IN_KEY', 'put your unique phrase here'", "'LOGGED_IN_KEY', '".$_POST["LOGGED_IN_KEY"]."'", $config);
			$config = str_replace("'NONCE_KEY', 'put your unique phrase here'", "'NONCE_KEY', '".$_POST["NONCE_KEY"]."'", $config);

			// 3.0 replacements
			$config = str_replace("database_name_here", $_POST["DB_NAME"], $config);
			$config = str_replace("username_here", $_POST["DB_USER"], $config);
			$config = str_replace("password_here", $_POST["DB_PASSWORD"], $config);
			$config = str_replace("localhost", $_POST["DB_HOST"], $config);
			$config = str_replace("'AUTH_KEY',         'put your unique phrase here'", "'AUTH_KEY',         '".$_POST["AUTH_KEY"]."'", $config);
			$config = str_replace("'SECURE_AUTH_KEY',  'put your unique phrase here'", "'SECURE_AUTH_KEY',  '".$_POST["SECURE_AUTH_KEY"]."'", $config);
			$config = str_replace("'LOGGED_IN_KEY',    'put your unique phrase here'", "'LOGGED_IN_KEY',    '".$_POST["LOGGED_IN_KEY"]."'", $config);
			$config = str_replace("'NONCE_KEY',        'put your unique phrase here'", "'NONCE_KEY',        '".$_POST["NONCE_KEY"]."'", $config);
			$config = str_replace("'AUTH_SALT',        'put your unique phrase here'", "'AUTH_SALT',        '".$_POST["AUTH_SALT"]."'", $config);
			$config = str_replace("'SECURE_AUTH_SALT', 'put your unique phrase here'", "'SECURE_AUTH_SALT', '".$_POST["SECURE_AUTH_SALT"]."'", $config);
			$config = str_replace("'LOGGED_IN_SALT',   'put your unique phrase here'", "'LOGGED_IN_SALT',   '".$_POST["LOGGED_IN_SALT"]."'", $config);
			$config = str_replace("'NONCE_SALT',       'put your unique phrase here'", "'NONCE_SALT',       '".$_POST["NONCE_SALT"]."'", $config);

			if(substr($_POST["table_prefix"], strlen($_POST["table_prefix"])-1)=="_"){
				$config = str_replace("\$table_prefix  = 'wp_';", "\$table_prefix  = '".$_POST["table_prefix"]."';", $config);
			}else{
				$config = str_replace("\$table_prefix  = 'wp_';", "\$table_prefix  = '".$_POST["table_prefix"]."_';", $config);
			}
			$fp = fopen(dirname(__FILE__)."/wp-config-sample.php", "w+");
			fwrite($fp, $config);
			fclose($fp);
		}
		rename(dirname(__FILE__)."/wp-config-sample.php", dirname(__FILE__)."/wp-config.php");
		exec("rm -f autoinstallwp.php", $buff3);
		header("Location: wp-admin/install.php");
		die();
		//echo "Done.";
	}
}else{
	//form here, and checks

	if(@filetype($dir."/wp-config-sample.php")=="file" || @filetype($dir."/wp-config.php")=="file"){
		echo "It appears that Wordpress has already been already uploaded and/or installed.<br />\n";
		echo "This utility is designed for clean installs only.<br />";
		die();
	}

	if(is_writable($dir)===false){
		echo "It does not appear that the current directory is writable.<br />\n";
		echo "Please correct and re-run this script.<br />\n";
		die();
	}

	$availfiles = array();

	if($dh = opendir($dir)){
		while(($file = readdir($dh)) !== false){
			if(filetype($dir."/".$file)=="file" && substr($file, 0, 9)=="wordpress"){
				if(substr($file, strlen($file)-3)==".gz" || substr($file, strlen($file)-4)==".zip"){
					$availfiles[] = $file;
				}
			}
		}
		closedir($dh);
	}

	if(count($availfiles)==0){
		$availfiles[] = getLatestWP();
	}elseif(count($availfiles)>1){
		echo "Multiple verions of Wordpress archives detected.<br />\n";
		echo "Please delete all but the one you wish to install, or delete all of them and allow this script to<br />\n";
		echo "download the latest version available from Wordpress.org.<br />\n";
		die();
	}

	//we appear to be good, provide the form

	//in case we are re-editing
	if(isset($_POST["process"]) && $_POST["process"]=="false"){
		$db_name = $_POST["DB_NAME"];
		$db_user = $_POST["DB_USER"];
		$db_password = $_POST["DB_PASSWORD"];
		$db_host = $_POST["DB_HOST"];
		$secret_key = $_POST["SECRET_KEY"];
		$auth_key = $_POST["AUTH_KEY"];
		$secure_auth_key = $_POST["SECURE_AUTH_KEY"];
		$logged_in_key = $_POST["LOGGED_IN_KEY"];
		$nonce_key = $_POST["NONCE_KEY"];
		$auth_salt = $_POST["AUTH_SALT"];
		$secure_auth_salt = $_POST["SECURE_AUTH_SALT"];
		$logged_in_salt = $_POST["LOGGED_IN_SALT"];
		$nonce_salt = $_POST["NONCE_SALT"];
		$table_prefix = $_POST["table_prefix"];
	} else {
		$db_name = "";
		$db_user = "";
		$db_password = "";
		$db_host = "localhost";
		$secret_key = rkg();
		$auth_key = rkg();
		$secure_auth_key = rkg();
		$logged_in_key = rkg();
		$nonce_key = rkg();
		$auth_salt = rkg();
		$secure_auth_salt = rkg();
		$logged_in_salt = rkg();
		$nonce_salt = rkg();
		$table_prefix = "wp_";
	}
?>
<html><head><title>EasyWP WordPress Installer</title>
<style>
.container {
	width: 700px;
	margin: 0px auto;
	font-family: Verdana;
	font-size: 12px;
}
.container input {
	margin-top: 5px;	
}
H2 {
	font-family: Verdana;
	text-align: center;
	margin: 26px;
}
.gray {
	background-color: #F0F0F0;
}	
</style>
</head>
<body>
<h2>EasyWP WordPress Installer</h1>
<div class="container">
<form action="autoinstallwp.php" method="post">
<input type="hidden" name="process" value="true">
<input type="hidden" name="wpfile" value="<?php echo $availfiles[0]; ?>">
<div class="gray">
The name of the database you wish to install WordPress into (<em>database must already exist</em>):<br />
<input type="text" name="DB_NAME" size="25" value="<?php echo $db_name; ?>"><br /><br />
</div>
<div class="white">
The MySQL username (<em>must be a valid user for the database entered above</em>):<br />
<input type="text" name="DB_USER" size="25" value="<?php echo $db_user; ?>"><br /><br />
</div>
<div class="gray">
The MySQL password (<em>this is the password for the database user entered above</em>):<br >
<input type="text" name="DB_PASSWORD" size="25" value="<?php echo $db_password; ?>"><br /><br />
</div>
<div class="white">
The host MySQL runs on (99% chance you won't need to change this value):<br />
<input type="text" name="DB_HOST" value="localhost" size="25" value="<?php echo $db_host; ?>"><br /><br />
</div>
<div class="gray">
Change these to long complicated unique phrases. You don't have to remember them later.<br />
These were randomly generated for you, but you can change them if you like:<br />
<input type="text" name="SECRET_KEY" value="<?php echo $secret_key; ?>" size="25"> SECRET_KEY (for older versions)<br />
<input type="text" name="AUTH_KEY" value="<?php echo $auth_key; ?>" size="25"> AUTH_KEY<br />
<input type="text" name="SECURE_AUTH_KEY" value="<?php echo $secure_auth_key; ?>" size="25"> SECURE_AUTH_KEY<br />
<input type="text" name="LOGGED_IN_KEY" value="<?php echo $logged_in_key; ?>" size="25"> LOGGED_IN_KEY<br />
<input type="text" name="NONCE_KEY" value="<?php echo $nonce_key; ?>" size="25"> NONCE_KEY<br />
<input type="text" name="AUTH_SALT" value="<?php echo $auth_salt; ?>" size="25"> AUTH_SALT<br />
<input type="text" name="SECURE_AUTH_SALT" value="<?php echo $secure_auth_salt; ?>" size="25"> SECURE_AUTH_SALT<br />
<input type="text" name="LOGGED_IN_SALT" value="<?php echo $logged_in_salt; ?>" size="25"> LOGGED_IN_SALT<br />
<input type="text" name="NONCE_SALT" value="<?php echo $nonce_salt; ?>" size="25"> NONCE_SALT<br /><br />
</div>
<div class="white">
You can have multiple WordPress installations in one database if you give each a unique prefix (wp_ is the default):<br />
<input type="text" name="table_prefix" value="<?php echo $table_prefix; ?>" size="25"> (Only numbers, letters, and underscores please!)<br /><br />
</div>
<input type="submit" value="Go!">
</form>
<?php
}
?>
</div>
</body>
</html>
