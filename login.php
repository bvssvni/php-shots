<?php ///////////////////////////// LOGIN /////////////////

/**
 * php-simple-login, Login using a hard coded hashed password in PHP file without username.
 *
 * @version 0.000, http://isprogrammingeasy.blogspot.no/2012/08/angular-degrees-versioning-notation.html
 * @license GNU Lesser General Public License, http://www.gnu.org/copyleft/lesser.html
 * @author  Sven Nilsen, http://www.cutoutpro.com
 * @link    https://github.com/bvssvni/php-simple-login
 *
 */

/* USAGE

Include this at beginning of PHP file:

	<?php include("login.php"); ?>

Include this where you want to have the 'edit' link.

	<?php login(); ?>

*/

session_start();

$login_hash = "5baa61e4c9b93f3f0682250b6cf8331b7ee68fd8";
$login_admin_flag = "admin";
$login_language = "en";
$login_interface_text = array(
	"editLink" => array(
		"en" => "edit",
		"no" => "rediger",
	),
	"loginButton" => array(
		"en" => "Log In",
		"no" => "Logg inn",
	),
	"logoutButton" => array(
		"en" => "Log Out",
		"no" => "Logg ut",
	),
	"wrongPasswordError" => array(
		"en" => "wrong password",
		"no" => "feil passord",
	),
);

$login_tmp_error_message = "";

login_login();
login_logout();

function login_text($str)
{
	global $login_interface_text;
	global $login_language;
	if (is_null($login_interface_text[$str]))
	{
		echo "Can not find " . $str . " in interface dictionary.<br />\n";
		return NULL;
	}
	
	return $login_interface_text[$str][$login_language];
}

function login_password_to_hash($pwd)
{
	return sha1($pwd);
}

function login_login()
{
	global $login_hash;
	global $login_admin_flag;
	global $login_tmp_error_message;
	$action = $_POST["action"];
	if ($action !== "login") {return;}
	
	$pwd = $_POST["pwd"];
	$try_hash = login_password_to_hash($pwd);
	if ($try_hash === $login_hash)
	{
		$_SESSION[$login_admin_flag] = TRUE;
	}
	else
	{
		$login_tmp_error_message = login_text("wrongPasswordError");
		$_SESSION[$login_admin_flag] = FALSE;
	}
}

function login_logout()
{
	global $login_admin_flag;
	$action = $_POST["action"];
	if ($action !== "logout") {return;}
	
	$_SESSION[$login_admin_flag] = FALSE;
}

function login()
{
	global $login_admin_flag;
	global $login_tmp_error_message;
	
	echo "<div id=\"loginContainer\">\n";
	
	$admin = $_SESSION[$login_admin_flag];
	if ($admin)
	{
		echo "<form id=\"logoutForm\" action=\"" . $_SERVER["PATH_INFO"] . "\" method=\"POST\">\n";
		echo "<input type=\"hidden\" value=\"logout\" name=\"action\" />\n";
		echo "<input type=\"submit\" value=\"" . login_text("logoutButton") . "\" />\n";
		echo "</form>\n";
	}
	else
	{
		echo "<a id=\"editLink\" href=\"#\" onclick=\"document.getElementById('loginForm').style.display = 'block';" .
		"document.getElementById('editLink').style.display = 'none'; document.getElementById('loginErrorLabel').style.display = 'none';" .
		"return false;\">" . login_text("editLink") . "</a>\n";
		echo "<div id=\"loginForm\" style=\"display: none;\">\n";
	
		echo "<form action=\"" . $_SERVER["PATH_INFO"] . "\" method=\"POST\">\n";
		echo "<input type=\"password\" name=\"pwd\" />\n";
		echo "<input type=\"hidden\" value=\"login\" name=\"action\" />\n";
		echo "<input type=\"submit\" value=\"" . login_text("loginButton") . "\" />\n";
		echo "</form>\n";
		
		echo "</div>\n";
	}
	
	if ($login_tmp_error_message !== "")
	{
		echo "<font id=\"loginErrorLabel\">" . $login_tmp_error_message . "</font>\n";
	}

	echo "</div>\n";
}

?>
