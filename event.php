<?php
require_once('cred.php');
error_reporting(E_ALL);
ini_set('display_errors', 'on');
ini_set('html_errors', 'off');

$headers = getallheaders();
$hub_signature = @$headers['X-Hub-Signature'];
if (!$hub_signature) {
	exit('missing signature');
}
list($algo, $hash) = explode('=', $hub_signature, 2);

try {
	$payload = file_get_contents('php://input');
	$data = json_decode($payload);
} catch(Exception $e) {
	http_response_code(400);
	exit('invalid payload format');
}

if ($hash !== hash_hmac($algo, $payload, WEBHOOK_SECRET)) {
	http_response_code(403);
	exit('invalid secret');
}

switch ($headers['X-GitHub-Event']) {
case 'ping':
	echo 'pong';
case 'pull_request':
	$delivery_id = $headers['X-GitHub-Delivery'];
	if (handle_pull_request($data, $delivery_id) === false) {
		http_response_code(500);
	}
	break;
default:
	echo 'unknown event type';
}

function github($path, $params) {
	$ch = curl_init();
	curl_setopt_array($ch, array( 
		CURLOPT_POST => 1, 
		CURLOPT_HEADER => 0, 
		CURLOPT_HTTPHEADER => array(
			'Authorization: token '.GITHUB_TOKEN,
			'User-Agent: '.APP_NAME
		),
		CURLOPT_URL => $path, 
		CURLOPT_FRESH_CONNECT => 1, 
		CURLOPT_RETURNTRANSFER => 1, 
		CURLOPT_FORBID_REUSE => 1, 
		CURLOPT_TIMEOUT => 4, 
		CURLOPT_POSTFIELDS => json_encode($params)
	)); 
	$result = curl_exec($ch);
	if (!$result) {
		echo curl_error($ch); 
	}
	curl_close($ch); 
	return $result ? json_decode($result) : null; 
}

/* create/update our status for the repo/commit */
function create_status($status_url, $state, $url=null) {
	return github($status_url, array(
		'state' => $state,
		'target_url' => $url,
		'context' => APP_NAME,
		//'description' => $msg
	));
}

/* build the project */
function exec_to($cmd, $dir, $outfile) {
	$descriptors = array(1 => array('file', $outfile, 'w'));
	$proc = proc_open($cmd, $descriptors, $pipes, $dir);
	if (!is_resource($proc)) {
		throw new Exception('failed to create process');
	}
	return proc_close($proc);
}

/* pull request hook */
function handle_pull_request($event, $delivery_id) {
	switch($event->action) {
	case 'opened':
	case 'synchronized':
		break;
	case 'closed':
		// TODO: Remove URL from PR status?
		// Remove output data?
	default:
		return;
	}

	$pr = $event->pull_request;
	$sha = $pr->head->sha;
	$ref = $pr->head->ref;
	$repo = $pr->base->repo;
	$repo_name = $repo->full_name;
	$clone_url = $repo->clone_url;
	$head_clone_url = $pr->head->repo->clone_url
	$status_url = str_replace('{sha}', $sha, $repo->statuses_url);

	// Find place to put output
	$out_name = $delivery_id.'.txt';
	$out_file = RESULTS_DIR.'/'.$out_name;
	$out_url = RESULTS_URL.'/'.$out_name;

	// Create pending PR status
	$resp = create_status($status_url, 'pending');
	if ($resp && $resp->state == 'success') {
		echo 'Successfully created status';
	}

	// Clone repo locally
	$dir = REPOS_DIR.'/'.$repo_name;
	if (!is_dir($dir.'/.git')) {
		passthru('git clone '.
			escapeshellarg($clone_url).' '.
			escapeshellarg($dir), $ret);
		if ($ret != 0) {
			echo "failed to clone repo $clone_url to $dir";
			create_status($status_url, 'error');
			return false;
		}
	}

	// Checkout PR branch
	passthru(implode(' && ', array(
		'cd '.escapeshellarg($dir),
		'git fetch '.escapeshellarg($head_clone_url).' '.escapeshellarg($ref),
		'git reset --hard '.escapeshellarg($sha),
		'git clean -dxf'
	)), $ret);
	if ($ret != 0) {
		echo "failed to check out $repo_name $ref ($sha)";
		create_status($status_url, 'error');
		return false;
	}

	// Run test command
	try {
		$ret = exec_to(TEST_CMD.' 2>&1', $dir, $out_file);
		$state = ($ret == 0) ? 'success' : 'failure';
		echo "command returned $ret\n";
	} catch (Exception $e) {
		echo 'error: '.$e->getMessage();
		$state = 'error';
		$out_url = null;
	}

	// Set PR status result
	$resp = create_status($status_url, $state, $out_url);
	if ($resp && $resp->state == 'success') {
		echo "Successfully updated status ($state)";
	}
}
