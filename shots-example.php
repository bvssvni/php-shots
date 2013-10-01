<?php include("login.php"); ?>
<?php include("shots.php"); ?>
<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
		<title>Cutout Pro Shots</title>
		<style>
		#editLink {
			color: #EEEEEE;
			text-decoration: none;
		}
		</style>
	</head>
	<body>
<?php shots($_SESSION[$login_admin_flag] === TRUE, "pics", 700); ?><br />
<?php login(); ?>
	</body>
</html>
