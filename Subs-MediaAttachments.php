<?php
/********************************************************************************
* Subs-MediaAttachments.php - Subs of the Play Audio Attachments mod
*********************************************************************************
* This program is distributed in the hope that it is and will be useful, but
* WITHOUT ANY WARRANTIES; without even any implied warranty of MERCHANTABILITY
* or FITNESS FOR A PARTICULAR PURPOSE,
**********************************************************************************/
if (!defined('SMF'))
	die('Hacking attempt...');

function PMA_settings(&$config_vars)
{
	$config_vars[] = '';
	$config_vars[] = array('text', 'attachmentAudioPlayerWidth', 6);
}

/*******************************************************************************/
// Proper MIME detection for HTML5 audio/video files:
// SOURCE: https://en.wikipedia.org/wiki/List_of_file_signatures
/*******************************************************************************/
function PMA_mime_type($filename, $original = false)
{
	$mime = false;
	$path = pathinfo($original);
	$signatures = array(
		"\x52\x49\x46\x46" => 'audio/wav|8|' . "\x57\x41\x56\x45",
		"\xFF\xFB" => 'audio/mpeg',
		"\x49\x44\x33" => 'audio/mpeg',
		"\x4F\x67\x67\x53" => 'audio/ogg',
		"\x1A\x45\xDF\xA3" => 'video/webm',
		"\x00\x00\x00\x14\x66\x74\x79\x70\x71\x74\x20\x20" => 'video/mp4',
		"\x00\x00\x00\x18\x66\x74\x79\x70\x6D\x70\x34\x32" => 'video/mp4',
		"\x00\x00\x00\x20\x66\x74\x79\x70\x33\x67\x70" => 'video/mp4',
	);
	if ($handle = @fopen($filename, 'rb'))
	{
		$contents = @fread($handle, 64);
		@fclose($handle);
		foreach ($signatures as $magic_bytes => $mime_type)
		{
			list($mime, $start, $extra) = explode('|', $mime_type . '||');
			if (substr($contents, 0, strlen($magic_bytes)) == $magic_bytes)
			{
				$mime = !empty($start) ? (substr($contents, $start, strlen($extra)) == $extra ? $mime : false) : $mime;
				break;
			}
		}
	}
	return $mime == 'audio/ogg' ? (isset($path['extension']) && $path['extension'] == 'ogv' ? 'video/ogg' : $mime) : $mime;
}

?>