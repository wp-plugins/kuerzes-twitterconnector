<?php

/*
Plugin Name: Kuerz.Es TwitterConnector
Plugin URI: http://kuerz.es/
Description: Tweets your posts in your twitter stream with a URL shortening by kuerz.es
Version: 1.0.1
Author: Knut Ahlers
Author URI: http://blog.knut.me/
*/

require('EpiCurl.php');
require('EpiOAuth.php');
require('EpiTwitter.php');

//----- Get your fingers off these lines! I don't joke! -----\\
define('TWITTER_CONSUMER_KEY', 'FCqZ7EW8xYANG1GfYdc6w');
define('TWITTER_CONSUMER_SECRET', 'bnIp0BTgvKCdbQDORneLB7Yzb67MNzClR9ZqqRTsoA');
//----- So now you better still keep off your fingers :P -----\\

function action_KETC($postID) {
	// Set username and password
	$username = get_option('KETC_username');
	$password = get_option('KETC_password');
	
	// Please keep your fingers off this!
	
	if(get_post_meta($postID, 'TwitterWasNotifiedKETC', true) == "1")
		return;
	
	$permalink = get_permalink($postID);
	$surl = KETC_retrieve_surl_ptt($permalink);
	
	$post = get_post($postID); 
	$title = $post->post_title;
	
	$message = "";
	
	if(get_option('KETC_prefix', 'Freshly from my blog:') != "") {
		$message .= get_option('KETC_prefix', 'Freshly from my blog:') . ' ';
	}
	
	$message .= $title . ' ' . $surl;
	
	if(get_option('KETC_suffix', '#blogpost') != "") {
		$message .= ' ' . get_option('KETC_suffix', '#blogpost');
	}
	
	$twitterObj = new EpiTwitter(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET);
	$twitterObj->setToken(get_option('KETC_oatoken'), get_option('KETC_oasecret'));
	$twitterObj->post_statusesUpdate(array('status' => $message));
	
	add_post_meta($postID, 'TwitterWasNotifiedKETC', "1", true) or update_post_meta($postID, 'TwitterWasNotifiedKETC', "1");
}

function KETC_retrieve_surl_ptt($longurl) {
	$longurl = urlencode($longurl);
	$url = "http://kuerz.es/api.rb?action=create&url=$longurl";
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	$output = curl_exec($ch);
	
	if($output === false)
		return "";
	
	return $output;
}

function KETC_add_pages() {
	add_options_page('Kuerz.Es Twitter Options', 'Kuerz.Es Twitter', 8, 'KETC', 'KETC_options');
}

function KETC_register_mysettings() {
	register_setting( 'KETC_option-group', 'KETC_oatoken' );
	register_setting( 'KETC_option-group', 'KETC_oasecret' );
	register_setting( 'KETC_option-group', 'KETC_prefix' );
	register_setting( 'KETC_option-group', 'KETC_suffix' );
}

function KETC_options() {
	
	$twitterObj = new EpiTwitter(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET);
	$oauthnick = null;
	
	if(isset($_POST['pin'])) {
		$twitterObj->setToken($_POST['token']);
		$token = $twitterObj->getAccessToken(array('oauth_verifier' => $_POST['pin']));
		
		update_option('KETC_oatoken', $token->oauth_token);
		update_option('KETC_oasecret', $token->oauth_token_secret);
	}
	
	if($_GET['reset'] == 'true') {
		delete_option('KETC_oatoken');
		delete_option('KETC_oasecret');
	}
	
	if($_POST['action'] == 'settings') {
		update_option('KETC_prefix', $_POST['prefix']);
		update_option('KETC_suffix', $_POST['suffix']);
	}
	
	if(get_option('KETC_oatoken', '') !== ''){
		$twitterObj->setToken(get_option('KETC_oatoken'), get_option('KETC_oasecret'));
		$twitterInfo= $twitterObj->get_accountVerify_credentials();
		$twitterInfo->response;
		$oauthnick = $twitterInfo->screen_name;
	} else {
		$token = $twitterObj->getRequestToken();
	}
	
?>
		<div class="wrap">
		<h2>Kuerz.es TwitterConnector</h2>
		<?php if($oauthnick == null) { ?>
		You have to do only two things to get twitter access into this plugin.
		<h3>1. Click the link below</h3>
		This will open a new window containing a page of twitter which asks you to confirm you really want to do this.<br />
		Click "Allow" on that page and you will get a pin. Please enter that pin in step 2.<br /><br />
		<a href="<?php echo $twitterObj->getAuthorizationUrl($token,array('oauth_callback' => 'oob')); ?>" target="_blank">I am the link you have to click!</a>
		
		<h3>2. Enter your pin from step 1 and submit.</h3>
		<form action="?page=KETC" method="post">
			<input type="hidden" name="token" value="<?php echo $token->oauth_token; ?>" />
			Pin goes here: <input type="text" name="pin" /><input type="submit" value="Send that pin!" />
		</form>
		<?php } else { ?>
		
		<h3>Finally</h3>
		So if you can see this you should see your twitter nick here: <?php echo $oauthnick; ?>
		
		<h3>Change the account?</h3>
		If you really, really want to do this simply click here: <a href="?page=KETC&amp;reset=true">Forget my Twitter name!</a>
		<?php } ?>
		
		<h3>Other Options</h3>
		<form action="?page=KETC" method="post">
			<input type="hidden" name="action" value="settings" />
			Prefix for your tweet: <input type="text" name="prefix" value="<?php echo get_option('KETC_prefix', 'Freshly from my blog:'); ?>" /> (Something like 'Freshly from my blog:')<br />
			Suffix for your tweet: <input type="text" name="suffix" value="<?php echo get_option('KETC_suffix', '#blogpost'); ?>" /> (For example a hashtag...)<br />
			This will result in: "<?php echo get_option('KETC_prefix', 'Freshly from my blog:'); ?> Title of your Post http://kuerz.es/xxx <?php echo get_option('KETC_suffix', '#blogpost'); ?>"<br /><br />
			<input type="submit" value="Change my settings." />
		</form>
		
		</div>
		<div class="wrap">
			<h3>Please support me:</h3>
			<p>If you like this plugin please think about donating me a small amount of money by clicking on the PayPal-button below:</p>
			<p><form action="https://www.paypal.com/cgi-bin/webscr" method="post">
			<input type="hidden" name="cmd" value="_s-xclick">
			<input type="hidden" name="hosted_button_id" value="7820666">
			<input type="image" src="https://www.paypal.com/de_DE/DE/i/btn/btn_donate_SM.gif" border="0" name="submit" alt="Jetzt einfach, schnell und sicher online bezahlen – mit PayPal.">
			<img alt="" border="0" src="https://www.paypal.com/de_DE/i/scr/pixel.gif" width="1" height="1">
			</form></p>
			<p><a href="http://knut.in/twitter" target="_blank">Knut Ahlers</a> -
			<a href="http://www.kahlers.de" target="_blank">Software developer &amp; Webhoster</a> -
			<a href="http://blog.knut.me/" target="_blank">Blogger</a></p>
		</div>
<?php
}


add_action('publish_post', 'action_KETC');
if ( is_admin() ){ // admin actions
	add_action('admin_menu', 'KETC_add_pages');
	add_action('admin_init', 'KETC_register_mysettings');
}

?>
