<?php
/**********************************************************************************
* mod_settings.php                                                                *
***********************************************************************************
* This program is distributed in the hope that it is and will be useful, but      *
* WITHOUT ANY WARRANTIES; without even any implied warranty of MERCHANTABILITY    *
* or FITNESS FOR A PARTICULAR PURPOSE.                                            *
*                                                                                 *
* This file is a simplified database installer. It does what it is suppoed to.    *
**********************************************************************************/
// If we have found SSI.php and we are outside of SMF, then we are running standalone.
if (file_exists(dirname(__FILE__) . '/SSI.php') && !defined('SMF'))
	require_once(dirname(__FILE__) . '/SSI.php');
elseif (!defined('SMF')) // If we are outside SMF and can't find SSI.php, then throw an error
	die('<b>Error:</b> Cannot install - please verify you put this file in the same place as SMF\'s SSI.php.');
require_once(dirname(__FILE__) . '/Subs-MediaAttachments.php');

// We need to find all MP3, WAV and OGG files in the attachment list and mark them as such:
$request = $smcFunc['db_query']('', '
	SELECT 
		filename, id_attach, id_folder, file_hash, fileext, mime_type
	FROM {db_prefix}attachments
	WHERE fileext IN ({array_string:extensions})',
	array(
		'extensions' => array('mp3', 'wav', 'ogg', 'oga', 'mp4', 'ogv', 'webm', 'm4a', 'm4v'),
	)
);
while ($row = $smcFunc['db_fetch_assoc']($request))
{
	$mime = PMA_mime_type(getAttachmentFilename($row['filename'], $row['id_attach'], $row['id_folder'], false, $row['file_hash']), $row['filename']);
	if (!empty($mime))
		$smcFunc['db_query']('', '
			UPDATE {db_prefix}attachments
			SET mime_type = {string:mime}
			WHERE id_attach = {int:attach}',
			array(
				'mime' => $mime,
				'attach' => $row['id_attach'],
			)
		);
}

if (SMF == 'SSI')
   echo 'Congratulations! You have successfully installed the mod settings!';

?>