<?php
require_once('cred.php');
ini_set('display_errors', '1');
error_reporting(E_ALL);

$headers = getallheaders();
$hub_signature = $headers['X-Hub-Signature'];
list($algo, $hash) = explode('=', $hub_signature, 2);

try {
	$payload = file_get_contents('php://input');
	$data = json_decode($payload);
} catch(Exception $e) {
	http_response_code(400);
	exit('invalid payload format');
}

if ($hash !== hash_hmac($algo, $payload, $secret)) {
	http_response_code(403);
	exit('invalid secret');
}

switch ($headers['X-GitHub-Event']) {
case 'ping':
	exit('pong');
case 'pull_request':
	$delivery_id = $headers['X-GitHub-Delivery'];
	handle_pull_request($data, $delivery_id);
	break;
default:
	exit('unknown event type');
}

function build($dir, $outfile) {
	$descriptors = array(1 => array('file', $outfile, 'w'));
	$proc = proc_open('make test 2>&1', $descriptors, $pipes, $dir);
	if (!is_resource($proc)) {
		exit('failed to create process');
	}
	return proc_close($proc);
}

function handle_pull_request($pr, $delivery_id) {
	if ($data->action != 'opened') {
		exit;
		// assigned, unassigned, labeled, unlabeled, opened, 
		// closed, reopened, or synchronized 
	}
	switch($data->action) {
	case 'opened':
	case 'synchronized':
		$dir = '.';
		// Find place to put output
		$out = 'out.txt';
		// Clone repo if it doesn't exist
		// Checkout PR branch
		// Run test command
		$ret = build($dir, $out);
		echo "command returned $ret\n";
		// Set PR status
		break;
	case 'closed':
		// Remove URL from PR status?
		// Remove output data
	}
}
