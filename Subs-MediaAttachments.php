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

/*******************************************************************************/
// Functions dealing with detecting MIME types on audio/video files:
/*******************************************************************************/
function PMAt_ValidMediaTypes()
{
	return array('mp3', 'wav', 'ogg', 'oga', 'mp4', 'ogv', 'webm', 'm4a', 'm4v', 'wma', 'wmv', 'aac');
}

function PMAt_CreateAttachment(&$attachmentOptions)
{
	global $sourcedir;
	if (empty($attachmentOptions['mime_type']) && in_array($attachmentOptions['fileext'], PMAt_ValidMediaTypes()) && empty($attachmentOptions['width']))
	{
		$temp_mime = PMAt_mime_type($attachmentOptions['tmp_name'], $attachmentOptions['fileext'], $attachmentOptions['name']);
		if (!empty($temp_mime))
			$attachmentOptions['mime_type'] = $temp_mime;
	}
}

/*******************************************************************************/
// Proper MIME detection for HTML5 audio/video files:
// SOURCE: https://en.wikipedia.org/wiki/List_of_file_signatures
/*******************************************************************************/
function PMAt_mime_type($filename, $ext, $original = false)
{
	// Set up for audio/video detection:
	$mime = false;
	$ext = pathinfo($original, PATHINFO_EXTENSION);
	$signatures = array(
	// Audio file signatures:
		/*  wav  */ "0|\x52\x49\x46\x46" => 'audio/wav|8|' . "\x57\x41\x56\x45",
		/*  mp3  */ "0|\xFF\xFB" => 'audio/mpeg',
		/*  mp3  */ "0|\x49\x44\x33" => 'audio/mpeg',
		/*  m4a  */ "4|\x66\x74\x79\x70\x4D\x53\x4E\x56" => 'audio/mp4',
		/*  aac  */ "0|\xFF\xF1" => 'audio/aac',
		/*  aac  */ "0|\xFF\xF9" => 'audio/aac',
		/*  aac  */ "0|\xFF\xFE" => 'audio/aac',
	// Video file signatures:
		/*  mp4  */ "4|\x66\x74\x79\x70\x69\x73\x6F\x6D" => 'video/mp4',
		/*  m4v  */ "4|\x66\x74\x79\x70\x6D\x70\x34\x32" => 'video/mp4',
		/*  webm */ "0|\x1A\x45\xDF\xA3" => 'video/webm',
	// Audio/Video file signature (could be either):
		/*  ogg  */ "0|\x4F\x67\x67\x53" => 'audio/ogg',
		/* wma/v */ "0|\x30\x26\xB2\x75\x8E\x66\xCF\x11" => 'audio/wma',
	// ALWAYS LAST CASE!  Must return "FALSE" if we get here!
		/* N/A  */ "0|" => false,
	);

	// Start checking against known signatures:
	if ($handle = @fopen($filename, 'rb'))
	{
		$contents = @fread($handle, 64);
		@fclose($handle);
		foreach ($signatures as $id => $mime_type)
		{
			list($start1, $magic_bytes) = explode('|', $id, 2);
			list($mime, $start2, $extra) = explode('|', $mime_type . '||');
			if (substr($contents, intval($start1), strlen($magic_bytes)) == $magic_bytes)
			{
				if (empty($mime) || substr($contents, intval($start2), strlen($extra)) == $extra)
					break;
			}
		}
	}

	// Since a file few signatures appear in both video and audio formats, we need to
	// look at the file extension to determine which mime type to return:
	if ($mime == 'audio/ogg')
		return $ext == 'ogv' ? 'video/ogg' : $mime;
	elseif ($mime == 'audio/wma')
		return $ext == 'wmv' ? 'video/wmv' : $mime;
	else
		return $mime;
}

/*******************************************************************************/
// Admin Functions of the Play Media Attachments mod:
/*******************************************************************************/
function PMAt_settings(&$config_vars)
{
	$config_vars[] = '';
	$config_vars[] = array('int', 'attachmentAudioPlayerWidth', 6);
	$config_vars[] = array('int', 'attachmentVideoPlayerWidth', 6);
}

function PMAt_Attach_Actions(&$subActions)
{
	loadLanguage('ManageMaintenance');
	$subActions['r_redetect'] = 'PMAt_Redirect1';
	$subActions['p_redetect'] = 'PMAt_Redirect1';
	$subActions['b_redetect'] = 'PMAt_Redetect2';
}

function PMAt_Redirect1()
{
	checkSession('get');
	PMAt_Redetect2(false);
}

function PMAt_Redetect2($both = true)
{
	global $smcFunc, $context;

	// We need to find all audio and videos files as attachments and mark them as such:
	$table = ($_REQUEST['sa'] == 'p_redetect' ? 'pm_attachments' : 'attachments');
	$request = $smcFunc['db_query']('', '
		SELECT 
			filename, id_attach, id_folder, file_hash, fileext, mime_type
		FROM {db_prefix}' . $table . '
		WHERE fileext IN ({array_string:extensions})',
		array(
			'extensions' => PMAt_ValidMediaTypes(),
		)
	);
	while ($row = $smcFunc['db_fetch_assoc']($request))
	{
		$mime = PMAt_mime_type(getAttachmentFilename($row['filename'], $row['id_attach'], $row['id_folder'], false, $row['file_hash']), $row['fileexit'], $row['filename']);
		if (!empty($mime))
			$smcFunc['db_query']('', '
				UPDATE {db_prefix}' . $table . '
				SET mime_type = {string:mime}
				WHERE id_attach = {int:attach}',
				array(
					'mime' => $mime,
					'attach' => $row['id_attach'],
				)
			);
	}

	// Go back to the attachment maintenance screen:
	redirectexit('action=admin;area=manageattachments;sa=' . ($both ? 'p_redetect' : 'maintenance') . ';' . $context['session_var'] . '=' . $context['session_id']);
}

?>