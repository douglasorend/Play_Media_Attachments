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
	$config_vars[] = array('int', 'attachmentAudioPlayerWidth', 6);
	$config_vars[] = array('int', 'attachmentVideoPlayerWidth', 6);
}

/*******************************************************************************/
// Proper MIME detection for HTML5 audio/video files:
// SOURCE: https://en.wikipedia.org/wiki/List_of_file_signatures
/*******************************************************************************/
function PMA_mime_type($filename, $original = false)
{
	$mime = false;
	$ext = pathinfo($original, PATHINFO_EXTENSION);
	$signatures = array(
	// Audio file signatures:
		/* wav  */ "0|\x52\x49\x46\x46" => 'audio/wav|8|' . "\x57\x41\x56\x45",
		/* mp3  */ "0|\xFF\xFB" => 'audio/mpeg',
		/* mp3  */ "0|\x49\x44\x33" => 'audio/mpeg',
		/* m4a  */ "4|\x66\x74\x79\x70\x4D\x53\x4E\x56" => 'audio/mp4',
	// Video file signatures:
		/* mp4  */ "4|\x66\x74\x79\x70\x69\x73\x6F\x6D" => 'video/mp4',
		/* m4v  */ "4|\x66\x74\x79\x70\x6D\x70\x34\x32" => 'video/mp4',
		/* webm */ "0|\x1A\x45\xDF\xA3" => 'video/webm',
	// Audio/Video file signature (could be either):
		/* ogg  */ "0|\x4F\x67\x67\x53" => 'audio/ogg',
	// ALWAYS LAST CASE!  Must return "FALSE" if we get here!
		/* N/A  */ "0|" => false,
	);
	if ($handle = @fopen($filename, 'rb'))
	{
		$contents = @fread($handle, 64);
		@fclose($handle);
		foreach ($signatures as $id => $mime_type)
		{
			list($start, $magic_bytes) = explode('|', $id, 2);
			list($mime, $start, $extra) = explode('|', $mime_type . '||');
			if (substr($contents, $start, strlen($magic_bytes)) == $magic_bytes)
			{
				if (empty($mime) || substr($contents, $start, strlen($extra)) == $extra))
					break;
			}
		}
	}
	return $mime == 'audio/ogg' ? (isset($ext) && $ext == 'ogv' ? 'video/ogg' : $mime) : $mime;
}

?>