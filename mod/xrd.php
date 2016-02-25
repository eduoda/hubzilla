<?php

require_once('include/crypto.php');

function xrd_init(&$a) {

	$uri = urldecode(notags(trim($_GET['uri'])));
	logger('xrd: ' . $uri,LOGGER_DEBUG);

	if(substr($uri,0,4) === 'http')
		$name = basename($uri);
	else {
		$local = str_replace('acct:', '', $uri);
		if(substr($local,0,2) == '//')
			$local = substr($local,2);

		$name = substr($local,0,strpos($local,'@'));
	}

	$r = q("SELECT * FROM channel WHERE channel_address = '%s' LIMIT 1",
		dbesc($name)
	);
	if(! $r) 
		killme();

	$dspr = replace_macros(get_markup_template('xrd_diaspora.tpl'),array(
		'$baseurl' => $a->get_baseurl(),
		'$dspr_guid' => $r[0]['channel_guid'] . str_replace('.','',$a->get_hostname()),
		'$dspr_key' => base64_encode(pemtorsa($r[0]['channel_pubkey']))
	));

	$salmon_key = salmon_key($r[0]['channel_pubkey']);

	header('Access-Control-Allow-Origin: *');
	header("Content-type: application/xrd+xml");


	$o = replace_macros(get_markup_template('xrd_person.tpl'), array(
		'$nick'        => $r[0]['channel_address'],
		'$accturi'     => $uri,
		'$profile_url' => $a->get_baseurl() . '/channel/'       . $r[0]['channel_address'],
		'$hcard_url'   => $a->get_baseurl() . '/hcard/'         . $r[0]['channel_address'],
		'$atom'        => $a->get_baseurl() . '/feed/'          . $r[0]['channel_address'],
		'$zot_post'    => $a->get_baseurl() . '/post/'          . $r[0]['channel_address'],
		'$poco_url'    => $a->get_baseurl() . '/poco/'          . $r[0]['channel_address'],
		'$photo'       => $a->get_baseurl() . '/photo/profile/l/' . $r[0]['channel_id'],
		'$dspr'        => $dspr,
//		'$salmon'      => $a->get_baseurl() . '/salmon/'        . $r[0]['channel_address'],
//		'$salmen'      => $a->get_baseurl() . '/salmon/'        . $r[0]['channel_address'] . '/mention',
		'$modexp'      => 'data:application/magic-public-key,'  . $salmon_key,
		'$subscribe'   => $a->get_baseurl() . '/follow?url={uri}',
		'$bigkey'      =>  salmon_key($r[0]['channel_pubkey'])
	));


	$arr = array('user' => $r[0], 'xml' => $o);
	call_hooks('personal_xrd', $arr);

	echo $arr['xml'];
	killme();

}
