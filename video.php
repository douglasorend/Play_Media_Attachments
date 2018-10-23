<?php
require_once('SSI.php');

//- turn off compression on the server
@apache_setenv('no-gzip', 1);
@ini_set('zlib.output_compression', 'Off');

if (!isset($_REQUEST['attach']) && !isset($_REQUEST['id']))
	fatal_lang_error('no_access', false);
$_REQUEST['attach'] = isset($_REQUEST['attach']) ? (int) $_REQUEST['attach'] : (int) $_REQUEST['id'];

// This checks only the current board for $board/$topic's permissions.
isAllowedTo('view_attachments');

// Make sure this attachment is on this board.
// NOTE: We must verify that $topic is the attachment's topic, or else the permission check above is broken.
$request = $smcFunc['db_query']('', '
	SELECT a.id_folder, a.filename, a.file_hash, a.fileext, a.id_attach, a.attachment_type, a.mime_type, a.approved, m.id_member
	FROM {db_prefix}attachments AS a
		INNER JOIN {db_prefix}messages AS m ON (m.id_msg = a.id_msg AND m.id_topic = {int:current_topic})
		INNER JOIN {db_prefix}boards AS b ON (b.id_board = m.id_board AND {query_see_board})
	WHERE a.id_attach = {int:attach}
	LIMIT 1',
	array(
		'attach' => $_REQUEST['attach'],
		'current_topic' => $topic,
	)
);
if ($smcFunc['db_num_rows']($request) == 0)
{
	header("HTTP/1.0 400 Bad Request");
	exit;
}
list ($id_folder, $real_filename, $file_hash, $file_ext, $id_attach, $attachment_type, $mime_type, $is_approved, $id_member) = $smcFunc['db_fetch_row']($request);
$smcFunc['db_free_result']($request);

// If it isn't yet approved, do they have permission to view it?
if (!$is_approved && ($id_member == 0 || $user_info['id'] != $id_member) && ($attachment_type == 0 || $attachment_type == 3))
	isAllowedTo('approve_posts');

$file_path = getAttachmentFilename($real_filename, $_REQUEST['attach'], $id_folder, false, $file_hash);

// allow a file to be streamed instead of sent as an attachment
$is_attachment = isset($_REQUEST['stream']) ? false : true;

// make sure the file exists
$file_size  = filesize($file_path);
$file = @fopen($file_path,"rb");
if (!$file)
{
	// file couldn't be opened
	header("HTTP/1.0 500 Internal Server Error");
	exit;
}

// set the headers, prevent caching
header("Pragma: public");
header("Expires: -1");
header("Cache-Control: public, must-revalidate, post-check=0, pre-check=0");
header("Content-Disposition: attachment; filename=\"$file_name\"");

// set appropriate headers for attachment or streamed file
if ($is_attachment)
		header("Content-Disposition: attachment; filename=\"$file_name\"");
else 
{
		header('Content-Disposition: inline;');
		header('Content-Transfer-Encoding: binary');
}

// send the content-type, as specified by the SMF database:
header("Content-Type: " . $mime_type);

// check if http_range is sent by browser (or download manager)
if(isset($_SERVER['HTTP_RANGE']))
{
	list($size_unit, $range_orig) = explode('=', $_SERVER['HTTP_RANGE'], 2);
	if ($size_unit == 'bytes')
	{
		//multiple ranges could be specified at the same time, but for simplicity only serve the first range
		//http://tools.ietf.org/id/draft-ietf-http-range-retrieval-00.txt
		list($range, $extra_ranges) = explode(',', $range_orig, 2);
	}
	else
	{
		$range = '';
		header('HTTP/1.1 416 Requested Range Not Satisfiable');
		exit;
	}
}
else
{
	$range = '';
}

//figure out download piece from range (if set)
list($seek_start, $seek_end) = explode('-', $range, 2);

//set start and end based on range (if set), else set defaults
//also check for invalid ranges.
$seek_end   = (empty($seek_end)) ? ($file_size - 1) : min(abs(intval($seek_end)),($file_size - 1));
$seek_start = (empty($seek_start) || $seek_end < abs(intval($seek_start))) ? 0 : max(abs(intval($seek_start)),0);

//Only send partial content header if downloading a piece of the file (IE workaround)
if ($seek_start > 0 || $seek_end < ($file_size - 1))
{
	header('HTTP/1.1 206 Partial Content');
	header('Content-Range: bytes '.$seek_start.'-'.$seek_end.'/'.$file_size);
	header('Content-Length: '.($seek_end - $seek_start + 1));
}
else
	header("Content-Length: $file_size");

header('Accept-Ranges: bytes');

set_time_limit(0);
fseek($file, $seek_start);

while(!feof($file)) 
{
	print(@fread($file, 1024*8));
	ob_flush();
	flush();
	if (connection_status()!=0) 
	{
		@fclose($file);
		exit;
	}			
}

// file save was a success
@fclose($file);

?>