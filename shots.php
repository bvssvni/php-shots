
<?php ///////////////////////////// SHOTS /////////////////

/* USAGE
// shots(edit, directory, width);
<?php shots($_SESSION[$login_admin_flag] === TRUE, "pics", 700); ?><br />
<?php login(); ?>
*/

$shots_language = "no";

$shots_interface_text = array(
	"newSerieButton" => array(
		"en" => "New Image Serie",
		"no" => "Ny bildeserie"
	),
	"uploadImageButton" => array(
		"en" => "Upload Image",
		"no" => "Last opp bilde"
	),
	"moveFirstButton" => array(
		"en" => "Move First",
		"no" => "Flytt først"
	),
	"moveUpButton" => array(
		"en" => "Move Up",
		"no" => "Flytt oppover"
	),
	"moveDownButton" => array(
		"en" => "Move Down",
		"no" => "Flytt nedover"
	),
	"moveLastButton" => array(
		"en" => "Move Last",
		"no" => "Flytt sist"
	),
	"deleteButton" => array(
		"en" => "Delete",
		"no" => "Slett"
	),
	"uploadingFailedError" => array(
		"en" => "Uploading failed. Please try again.",
		"no" => "Det skjedde en feil under opplasting. Vennligst prøv igjen."
	),
	"backLink" => array(
		"en" => "Back to image overview",
		"no" => "Tilbake til bildeoversikten"
	),
	"clickImageToWatchSerie" => array(
		"en" => "Click on a picture to watch serie",
		"no" => "Trykk på et av bildene for å se på en bildeserie",
	),
);

function shots_text($str)
{
	global $shots_interface_text;
	global $shots_language;
	if (is_null($shots_interface_text[$str]))
	{
		echo "Can not find " . $str . " in interface dictionary.<br />\n";
		return NULL;
	}
	
	return $shots_interface_text[$str][$shots_language];
}

function shots_action($action, $file)
{
	$dst = $_SERVER["PATH_INFO"];
	return "<div style=\"float: left;\"><form action=\"" . $dst . "\" method=\"POST\">\n" .
	"<input type=\"hidden\" value=\"" . $action . "\" name=\"action\" />\n" .
	"<input type=\"hidden\" value=\"" . $file . "\" name=\"file\" />\n" .
	"<input type=\"submit\" value=\"" . shots_text($action) . "\" />\n" .
	"</form></div>\n";
}

function shots_print_image($edit, $file, $width)
{
	$tr_start = "<tr>\n";
	$td_start = "<td>\n";
	// Add timestamp in end of name to avoid storing it in the buffer.
	$img = "<img src=\"" . $file . "?t=" . time() . "\" width=\"" . $width . "\" />\n";
	$moveFirst = shots_action("moveFirstButton", $file);
	$moveUp = shots_action("moveUpButton", $file);
	$moveDown = shots_action("moveDownButton", $file);
	$moveLast = shots_action("moveLastButton", $file);
	$delete = shots_action("deleteButton", $file);
	$controls = "<br />" . $moveFirst . $moveUp . $moveDown . $moveLast . $delete . "<br />\n";
	if (!$edit)
	{
		$controls = "";
	}
	
	$td_end = "</td>\n";
	$tr_end = "<tr>\n";
	return $tr_start . $td_start . $img . $controls . $td_end . $tr_end;
}

function shots_print_serie($edit, $file, $thumbnail, $width)
{
	$tr_start = "<tr>\n";
	$td_start = "<td>\n";
	// Add timestamp in end of name to avoid storing it in the buffer.
	$img = "<img border=\"0\" src=\"" . $thumbnail . "?t=" . time() . "\" width=\"" . $width . "\" />\n";
	$link = "<a href=\"" . $_SERVER["PHP_SELF"] . "?images=" . urlencode($file) . "\">" . $img . "</a>";
	$moveFirst = shots_action("moveFirstButton", $file);
	$moveUp = shots_action("moveUpButton", $file);
	$moveDown = shots_action("moveDownButton", $file);
	$moveLast = shots_action("moveLastButton", $file);
	$delete = shots_action("deleteButton", $file);
	$controls = "<br />" . $moveFirst . $moveUp . $moveDown . $moveLast . $delete . "<br />\n";
	if (!$edit)
	{
		$controls = "";
	}
	
	$td_end = "</td>\n";
	$tr_end = "<tr>\n";
	return $tr_start . $td_start . $link . $controls . $td_end . $tr_end;
}

function shots_get_pics($dir)
{
	$handle = opendir($dir);
	if ($handle === false) {return array();}
	
	$list = array();
	while (false !== ($entry = readdir($handle)))
	{
		if (strpos($entry, ".") === 0) {continue;}
		
		$file = $dir . "/" . $entry;
		if (is_dir($file)) {continue;}
		
        $list[] = $file;
    }
    
    closedir($handle);
    sort($list);
    return $list;
}

function shots_file_id($id) {
	return 100000 + $id;
}

function shots_pic_file($dir, $id) {
	if (is_null($dir)) {echo("shots_pic_file{dir is null}"); return NULL;}
	if (is_null($id)) {echo("shots_pic_file{id is null}"); return NULL;}

	return $dir . "/img" . shots_file_id($id) . ".jpg";
}

function shots_check_pics_is_sorted($dir, $files) {
	$n = count($files);
	for ($i = 0; $i < $n; ++$i) {
		$name = shots_pic_file($dir, $i);
		if ($files[$i] !== $name) {
			return FALSE;
		}
	}
	
	return TRUE;
}

function shots_force_pics_sorted($dir, $files) {
	if (shots_check_pics_is_sorted($dir, $files)) {return;}
	
	$n = count($files);
	for ($i = 0; $i < $n; ++$i) {
		$name = shots_pic_file($dir, $i);
		if ($files[$i] !== $name) {
			rename($files[$i], $name);
			$files[$i] = $name;
		}
	}
}

function shots_get_series($dir)
{
	$handle = opendir($dir);
	if ($handle === false) {return array();}
	
	$list = array();
	while (false !== ($entry = readdir($handle)))
	{
		if (strpos($entry, ".") === 0) {continue;}
		
		$subdir = $dir . "/" . $entry;
		if (!is_dir($subdir)) {continue;}
		
        $list[] = $subdir;
    }
    
    closedir($handle);
    sort($list);
    return $list;
}

$shots_delete_image_done = FALSE;
function shots_delete_image($edit, $dir)
{
	// Protect against non-admins and multiple updates.
	global $shots_delete_image_done;
	if ($shots_delete_image_done || !$edit) {return;}
	$shots_delete_image_done = TRUE;

	$action = $_POST["action"];
	if ($action !== "deleteButton") {return;}
	
	$file = $_POST["file"];
	// Make sure file is inside the current directory.
	if (strpos($file, $dir . "/") !== 0) {return;}
	
	unlink($file);
	
	// Rename images so new images are inserted in correct order.
	$pics = shots_get_pics($dir);
	$n = count($pics);
	for ($i = 0; $i < $n; ++$i)
	{
		$newName = shots_pic_file($dir, $i);
		rename($pics[$i], $newName);
	}
}

function rrmdir($dir) { 
   if (is_dir($dir)) { 
     $objects = scandir($dir); 
     foreach ($objects as $object) { 
       if ($object != "." && $object != "..") { 
         if (filetype($dir."/".$object) == "dir") rrmdir($dir."/".$object); else unlink($dir."/".$object); 
       } 
     } 
     reset($objects); 
     rmdir($dir); 
   } 
 }

$shots_delete_serie_done = FALSE;
function shots_delete_serie($edit, $dir)
{
	// Protect against non-admins and multiple updates.
	global $shots_delete_serie_done;
	if ($shots_delete_serie_done || !$edit) {return;}
	$shots_delete_serie_done = TRUE;
	
	$shots_delete_serie_done = TRUE;
	
	$action = $_POST["action"];
	if ($action !== "deleteButton") {return;}
	
	$file = $_POST["file"];
	// Make sure serie is inside the current directory.
	if (strpos($file, $dir . "/") !== 0) {return;}
	
	rrmdir($file);
	
	// Rename series so new series are inserted in correct order.
	$series = shots_get_series($dir);
	$n = count($series);
	for ($i = 0; $i < $n; ++$i)
	{
		$newName = $dir . "/ser" . shots_file_id($i);
		rename($series[$i], $newName);
	}
}

$shots_move_image_first_done = FALSE;
function shots_move_image_first($edit, $dir)
{
	// Protect against non-admins and multiple updates.
	global $shots_move_image_first_done;
	if ($shots_move_image_first_done || !$edit) {return;}
	$shots_move_image_first_done = TRUE;

	$action = $_POST["action"];
	if ($action !== "moveFirstButton") {return;}
	
	$file = $_POST["file"];
	// Make sure file is inside the current directory.
	if (strpos($file, $dir . "/") !== 0) {return;}
	
	$pics = shots_get_pics($dir);
	$n = count($pics);
	if ($n === 0) {return;}
	
	// Swap files with first picture.
	$first = $pics[0];
	if ($prev === $first) {return;}
	
	$tmp = $dir . "/tmp.jpg";
	rename($first, $tmp);
	rename($file, $first);
	rename($tmp, $file);
}

$shots_move_serie_first_done = FALSE;
function shots_move_serie_first($edit, $dir)
{
	// Protect against non-admins and multiple updates.
	global $shots_move_serie_first_done;
	if ($shots_move_serie_first_done || !$edit) {return;}
	$shots_move_serie_first_done = TRUE;

	$action = $_POST["action"];
	if ($action !== "moveFirstButton") {return;}
	
	$file = $_POST["file"];
	// Make sure serie is inside the current directory.
	if (strpos($file, $dir . "/") !== 0) {return;}
	
	$series = shots_get_series($dir);
	$n = count($series);
	if ($n === 0) {return;}
	
	// Swap files with first serie.
	$first = $series[0];
	if ($prev === $first) {return;}
	
	$tmp = $dir . "/tmp.jpg";
	rename($first, $tmp);
	rename($file, $first);
	rename($tmp, $file);
}

$shots_move_image_up_done = FALSE;
function shots_move_image_up($edit, $dir)
{
	// Protect against non-admins and multiple updates.
	global $shots_move_image_up_done;
	if ($shots_move_image_up_done || !$edit) {return;}
	$shots_move_image_up_done = TRUE;

	$action = $_POST["action"];
	if ($action !== "moveUpButton") {return;}
		
	$file = $_POST["file"];
	// Make sure file is inside the current directory.
	if (strpos($file, $dir . "/") !== 0) {return;}
	
	$pics = shots_get_pics($dir);
	$n = count($pics);
	if ($n === 0) {return;}
	
	for ($i = 1; $i < $n; ++$i)
	{
		$pic = $pics[$i];
		if ($pic !== $file) {continue;}
		
		// Swap files with previous picture.
		$prev = $pics[$i - 1];
		
		$tmp = $dir . "/tmp.jpg";
		rename($prev, $tmp);
		rename($file, $prev);
		rename($tmp, $file);
		break;
	}
}

$shots_move_serie_up_done = FALSE;
function shots_move_serie_up($edit, $dir)
{
	// Protect against non-admins and multiple updates.
	global $shots_move_serie_up_done;
	if ($shots_move_serie_up_done || !$edit) {return;}
	$shots_move_serie_up_done = TRUE;

	$action = $_POST["action"];
	if ($action !== "moveUpButton") {return;}
	
	$file = $_POST["file"];
	// Make sure serie is inside the current directory.
	if (strpos($file, $dir . "/") !== 0) {return;}
	
	$series = shots_get_series($dir);
	$n = count($series);
	if ($n === 0) {return;}
	
	for ($i = 1; $i < $n; ++$i)
	{
		$serie = $series[$i];
		if ($serie !== $file) {continue;}
		
		// Swap files with previous serie.
		$prev = $series[$i - 1];
		
		$tmp = $dir . "/tmp.jpg";
		rename($prev, $tmp);
		rename($file, $prev);
		rename($tmp, $file);
		break;
	}
}

$shots_move_image_down_done = FALSE;
function shots_move_image_down($edit, $dir)
{
	// Protect against non-admins and multiple updates.
	global $shots_move_image_down_done;
	if ($shots_move_image_down_done || !$edit) {return;}
	$shots_move_image_down_done = TRUE;

	$action = $_POST["action"];
	if ($action !== "moveDownButton") {return;}
		
	$file = $_POST["file"];
	// Make sure file is inside the current directory.
	if (strpos($file, $dir . "/") !== 0) {return;}
	
	$pics = shots_get_pics($dir);
	$n = count($pics);
	if ($n === 0) {return;}
	
	for ($i = 0; $i < $n - 1; ++$i)
	{
		$pic = $pics[$i];
		if ($pic !== $file) {continue;}
		
		// Swap files with next picture.
		$next = $pics[$i + 1];
		
		$tmp = $dir . "/tmp.jpg";
		rename($next, $tmp);
		rename($file, $next);
		rename($tmp, $file);
		break;
	}
}

$shots_move_serie_down_done = FALSE;
function shots_move_serie_down($edit, $dir)
{
	// Protect against non-admins and multiple updates.
	global $shots_move_serie_down_done;
	if ($shots_move_serie_down_done || !$edit) {return;}
	$shots_move_serie_down_done = TRUE;

	$action = $_POST["action"];
	if ($action !== "moveDownButton") {return;}
		
	$file = $_POST["file"];
	// Make sure file is inside the current directory.
	if (strpos($file, $dir . "/") !== 0) {return;}
	
	$series = shots_get_series($dir);
	$n = count($series);
	if ($n === 0) {return;}
	
	for ($i = 0; $i < $n - 1; ++$i)
	{
		$serie = $series[$i];
		if ($serie !== $file) {continue;}
		
		// Swap files with next serie.
		$next = $series[$i + 1];
		
		$tmp = $dir . "/tmp.jpg";
		rename($next, $tmp);
		rename($file, $next);
		rename($tmp, $file);
		break;
	}
}

$shots_move_image_last_done = FALSE;
function shots_move_image_last($edit, $dir)
{
	// Protect against non-admins and multiple updates.
	global $shots_move_image_last_done;
	if ($shots_move_image_last_done || !$edit) {return;}
	$shots_move_image_last_done = TRUE;

	$action = $_POST["action"];
	if ($action !== "moveLastButton") {return;}
	
	$file = $_POST["file"];
	// Make sure file is inside the current directory.
	if (strpos($file, $dir . "/") !== 0) {return;}
	
	$pics = shots_get_pics($dir);
	$n = count($pics);
	if ($n === 0) {return;}
	
	$last = $pics[$n - 1];
	if ($prev === $last) {return;}
	
	
	// Rename all other images to correct sequence.
	$tmp = $dir . "/tmp.jpg";
	
	// First, rename the selected file to a temporary image.
	rename($file, $tmp);
	$id = 0;
	// Second, rename all the others to follow correct sequence.
	for ($i = 0; $i < $n; ++$i) {
		$file_to_move = $pics[$i];
		if ($file_to_move === $file) {continue;}
		
		$newname = shots_pic_file($dir, $id);
		if ($file_to_move === $newname) {continue;}
		rename($file_to_move, $newname);
		$id++;
	}
	
	// Third, rename the temporary image to the last id.
	rename($tmp, shots_pic_file($dir, $id));
}

$shots_move_serie_last_done = FALSE;
function shots_move_serie_last($edit, $dir)
{
	// Protect against non-admins and multiple updates.
	global $shots_move_serie_last_done;
	if ($shots_move_serie_last_done || !$edit) {return;}
	$shots_move_serie_last_done = TRUE;

	$action = $_POST["action"];
	if ($action !== "moveLastButton") {return;}
	
	$file = $_POST["file"];
	// Make sure serie is inside the current directory.
	if (strpos($file, $dir . "/") !== 0) {return;}
	
	$series = shots_get_series($dir);
	$n = count($series);
	if ($n === 0) {return;}
	
	// Swap files with last serie.
	$last = $series[$n - 1];
	
	if ($prev === $last) {return;}
	
	$tmp = $dir . "/tmp.jpg";
	rename($last, $tmp);
	rename($file, $last);
	rename($tmp, $file);
}

$shots_upload_image_done = FALSE;
function shots_upload_image($edit, $dir)
{
	// Protect against non-admins and multiple updates.
	global $shots_upload_image_done;
	if ($shots_upload_image_done || !$edit) {return;}
	$shots_upload_image_done = TRUE;

	$action = $_POST["action"];
	if ($action !== "uploadImageButton") {return;}
	
	// Check for image being uploaded.
	$uploadedimage = $_FILES["uploadedimage"]["tmp_name"];
	if (is_null($uploadedimage)) {return;}
	
	$pics = shots_get_pics($dir);
	$n = count($pics);
	
	// Find new id for uploaded picture.
	$id = 0;
	for ($i = 0; $i < $n; ++$i)
	{
		for ($j = 0; $j < $n; ++$j) {
			$pic = $pics[$j];
			if ($pic === shots_pic_file($dir, $id))
			{
				++$id;
				break;
			}
		}
	}
	
	$pic = shots_pic_file($dir, $id);
	
	if (move_uploaded_file($uploadedimage, $pic))
	{
		// Do nothing.
	}
	else
	{
		echo "<font color=\"red\">" . shots_text("uploadingFailedError") . "</font><br />\n";
	}
}

$shots_new_serie_done = FALSE;
function shots_new_serie($edit, $dir)
{
	// Protect against non-admins and multiple updates.
	global $shots_new_serie_done;
	if ($shots_new_serie_done || !$edit) {return;}
	$shots_new_serie_done = TRUE;

	$action = $_POST["action"];
	if ($action !== "newSerieButton") {return;}
	
	$series = shots_get_series($dir);
	$n = count($series);
	
	// Find new id for serie.
	// Do this by searching first for gaps in the sequence.
	$id = 0;
	for ($j = 0; $j < $n; ++$j)
	{
		for ($i = 0; $i < $n; ++$i)
		{
			$serie = $series[$i];
			if ($serie === $dir . "/ser" . shots_file_id($j))
			{
				++$id;
				break;
			}
		}
	}
	
	// Create new id if no gaps.
	if ($id === $n - 1)
	{
		$id = $n;
	}
	
	$serie = $dir . "/ser" . shots_file_id($id);
	mkdir($serie);
}

function shots_series($edit, $dir, $width)
{
	$series = shots_get_series($dir);
	$n = count($series);
	for ($i = 0; $i < $n; ++$i)
	{
		$serie = $series[$i];
		$pics = shots_get_pics($serie);
		$m = count($pics);
		// Use default path as thumbnail.
		// This will show up as broken image when there are no uploaded images.
		$thumbnail = "noimage.jpg";
		if ($m !== 0)
		{
			$thumbnail = $pics[0];
		}
		
		echo shots_print_serie($edit, $serie, $thumbnail, $width);
	}
}

function shots_images($edit, $dir, $width)
{
	// Show images from directory.
	$pics = shots_get_pics($dir);
	$n = count($pics);
	for ($i = 0; $i < $n; ++$i)
	{
		$pic = $pics[$i];
		echo shots_print_image($edit, $pic, $width);
	}
}

function shots_link_to_overview()
{
	$images = $_GET["images"];
	if (is_null($images))
	{
		echo shots_text("clickImageToWatchSerie") . "<br />";
	}
	else
	{
		echo "<a href=\"" . $_SERVER["PHP_SELF"] . "\">" . shots_text("backLink") . "</a><br />";
	}
}

function shots($edit, $dir, $width)
{
	$images = $_GET["images"];
	if (is_null($images))
	{
		shots_new_serie($edit, $dir);
		shots_delete_serie($edit, $dir);
		shots_move_serie_first($edit, $dir);
		shots_move_serie_up($edit, $dir);
		shots_move_serie_down($edit, $dir);
		shots_move_serie_last($edit, $dir);
	}
	else
	{
		shots_upload_image($edit, $images);
		shots_delete_image($edit, $images);
		shots_move_image_first($edit, $images);
		shots_move_image_up($edit, $images);
		shots_move_image_down($edit, $images);
		shots_move_image_last($edit, $images);
	}

	echo "<!-- start shots -->\n";
	
	shots_link_to_overview();
	
	echo "<table>\n";
	
	if (is_null($images))
	{
		shots_series($edit, $dir, $width);
	}
	else
	{
		shots_images($edit, $images, $width);
	}
	
	echo "</table>\n";
	
	shots_link_to_overview();
	
	if ($edit)
	{
		echo "<hr />\n";
		if (is_null($images))
		{
			// Show form for adding new image serie.
			echo shots_action("newSerieButton", $dir);
		}
		else
		{
			// Show form for uploading new image.
			echo "<form enctype=\"multipart/form-data\" action=\"" . $_SERVER['PHP_SELF'] . "?images=" . urlencode($images) . "\" method=\"POST\">\n";
			echo "<input type=\"hidden\" name=\"action\" value=\"uploadImageButton\" />\n";
			echo "<input type=\"hidden\" name=\"MAX_FILE_SIZE\" value=\"5000000\" />\n";
			echo "<input name=\"uploadedimage\" type=\"file\" accept=\"image/jpeg\" />\n";
			echo "<input value=\"" . shots_text("uploadImageButton") . "\" type=\"submit\" />\n";
			echo "</form>\n";
		}
	}
	
	echo "<!-- end shots -->\n";
}

?>
