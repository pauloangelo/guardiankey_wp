<?php

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

define('AES_256_CBC', 'aes-256-cbc');

function guardiankey_options_page_html() {
	
    // check user capabilities
    if (!current_user_can('manage_options')) {
        return;
    }
    if (esc_attr( get_option('hashid')) == '' ) {
		guardiankey_register();
	} else {
		
    ?>
    <div class="wrap">
        <h1><? echo $GKTitle; ?></h1>
        <form action="options.php" method="post">
            <?php
            
            settings_fields('guardiankey_options');
          
            do_settings_sections('guardiankey_options'); ?>
            <table class="form-table">
        <tr valign="top">
		<h2>GuardianKey</h2>
		 <th scope="row">Registration Email</th>
        <td><input type="text" name="guardiankey_emailRegister" value="<?php echo esc_attr( get_option('guardiankey_emailRegister') ); ?>" /></td>
        </tr>
        <th scope="row">Hash ID</th>
        <td><input type="text" name="hashid" value="<?php echo esc_attr( get_option('hashid') ); ?>" /></td>
        </tr>
         
        <tr valign="top">
        <th scope="row">KEY</th>
        <td><input type="text" name="key" value="<?php echo esc_attr( get_option('key') ); ?>" /></td>
        </tr>
        
        <tr valign="top">
        <th scope="row">IV</th>
        <td><input type="text" name="iv" value="<?php echo esc_attr( get_option('iv') ); ?>" /></td>
        </tr>
         <tr valign="top">
        <th scope="row">Salt</th>
        <td><input type="text" name="salt" value="<?php echo esc_attr( get_option('salt') ); ?>" /></td>
        </tr>
         <tr valign="top">
        <th scope="row">DNS reverse lookup</th>
        <td><select name="dnsreverse">
			<?php $selected = esc_attr( get_option('dnsreverse') ); ?>
				<option value="Yes" <?php if ($selected == "Yes") { echo "SELECTED";}?> >Yes</option>
				<option value="No" <?php if ($selected == "No") { echo "SELECTED";}?> >No</option>
        </select></td>
        </tr>
         <tr valign="top">
        <th scope="row">Email subject:</th>
        <td><input type="text" name="mailsubject" value="<?php echo esc_attr( get_option('mailsubject') ); ?>" /></td>
        </tr>
            <tr valign="top">
        <th scope="row">Email text:</th>
        <td><textarea name="mailhtml" cols=80 rows=10 ><?php echo esc_attr( get_option('mailhtml') ); ?></textarea></td>
        </tr>
    </table>
			
             <?php 
        
         
             submit_button('Save Settings');
            ?>
        </form>
    </div>
    <?php
}
}

function guardiankey_options_page() {
    add_submenu_page(
        'tools.php',
        'GuardianKey',
        'GuardianKey',
        'manage_options',
        'guardiankey',
        'guardiankey_options_page_html'
    );
}

function register_mysettings() { // whitelist options
  register_setting( 'guardiankey_options', 'hashid' );
  register_setting( 'guardiankey_options', 'key' );
  register_setting( 'guardiankey_options', 'iv' );
  register_setting( 'guardiankey_options', 'salt' );
  register_setting( 'guardiankey_options', 'dnsreverse' );
  register_setting( 'guardiankey_options', 'mailsubject' );
  register_setting( 'guardiankey_options', 'mailhtml' );
  register_setting( 'guardiankey_options', 'guardiankey_emailRegister' );

}


function guardiankey_register() {
	?>
	    <div class="wrap">
		<h1>GuardianKEY</h1>	
		<h2>Register</h2>
		<form action="<?php echo get_admin_url().'admin-post.php';?>" method='post'>
		<p>You need register your GuardianKEY Instalation. Please fill in the fields below:</p>
		
		<p><b>E-mail:  </b><input type=text name=email></p>
		<input type='hidden' name='action' value='submit-ck' />
		<input type=submit name=submit> 
		</form>
		</div>
		<?php
	}

function create_key() {
	
	$options= array( 'location' =>  'http://ws.ids-hogzilla.com/ws/',
	                  'uri'      =>  'http://ws.ids-hogzilla.com/ws/');
	$client=new SoapClient(NULL,$options);

	define('AES_256_CBC', 'aes-256-cbc');
	 $key = openssl_random_pseudo_bytes(32);
     $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length(AES_256_CBC));
     $keyb64 = base64_encode($key);
     $ivb64 =  base64_encode($iv);
     $email = addslashes($_POST['email']); 
	 $adminuser = addslashes($_POST['nome']);
	 $hashid =  $client->register($email,$keyb64,$ivb64);
     $salt = md5(rand().rand().rand().rand().$hashid);
       
     update_option( 'hashid', $hashid, 'yes' );
     update_option( 'key' , $keyb64, 'yes' );
     update_option( 'iv' , $ivb64, 'yes' );
     update_option( 'salt' , $salt, 'yes' );
     update_option( 'guardiankey_emailRegister' , $email, 'yes' );

	global $wpdb;

	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE IF NOT EXISTS guardiankey (
	userhash varchar(40) NOT NULL,
	username varchar(200) NOT NULL
	)$charset_collate;";

	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
	dbDelta( $sql );

    wp_redirect(admin_url('/tools.php?page=guardiankey', 'http'), 301);

     
}

function guardiankey_checkUser() {
	  if (esc_attr( get_option('hashid')) <> '' ) {
		 $current_user = wp_get_current_user();
		 $AuthUser =$current_user->ID;
		 $keyb64 = get_option('key');
		 $salt = get_option('salt');
		 $ivb64 = get_option('iv');
		 $hashid = get_option('hashid');
		 $reverse = get_option('dnsreverse');
		 $timestamp = time();
		 $usernamehash=md5($AuthUser.$salt);
		 
		 global $wpdb;
		 $result = $wpdb->get_results( "SELECT username FROM guardiankey WHERE userhash ='".$usernamehash."'" , OBJECT );
		 $exists = count($result);
		 if ($exists <> 1) {
				$wpdb->insert(
					'guardiankey',
						array(
							'username' => $AuthUser,
							'userhash' => $usernamehash
							)
				);
		 }
	 	define('AES_256_CBC', 'aes-256-cbc');
		 $key=base64_decode($keyb64);
          $iv=base64_decode($ivb64);
          $agent=$hashid;
          $service="WordPress";
          $ip=$_SERVER['REMOTE_ADDR'];
          $clientreverse= ($reverse == 'Yes')?  gethostbyaddr($ip) : "";
          $usernamehash;
          $authmethod="";
          $loginfailed="0";
          $ua=str_replace("'","",$_SERVER['HTTP_USER_AGENT']);
          $ua=str_replace("|","",$ua);
          $ua=substr($ua,0,50);
          $message = $timestamp."|". $agent."|". $service."|". $clientreverse."|". $ip."|". $usernamehash."|". $authmethod."|". $loginfailed."|". $ua."|";
          $cipher = openssl_encrypt($message, AES_256_CBC, $key, 0, $iv);
          $payload=$hashid."|".$cipher;
          $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
          socket_sendto($socket, $payload, strlen($payload), 0, "collector.ids-hogzilla.com", "8888");
				
		}
}

function getGKevents() {
	 if (esc_attr( get_option('hashid')) <> '' ) {
	    $options= array( 'location' =>  'http://ws.ids-hogzilla.com/ws/',
	                       'uri'      =>  'http://ws.ids-hogzilla.com/ws/');
	    $client=new SoapClient(NULL,$options);
	    $keyb64 = esc_attr( get_option('key'));
	    $hashid  = esc_attr( get_option('hashid'));
		$ivb64 = esc_attr( get_option('iv'));
		$key = base64_decode($keyb64);
		$iv = base64_decode($ivb64);
		$timestamp = time();
        $timestampcipher = openssl_encrypt($timestamp, AES_256_CBC, $key, 0, $iv);
        $events= $client->listevents($hashid,$timestampcipher);
        foreach( $events as $event ){
              $this->processEvent($event);
            }
}
}

function processEvent($event) {
	 global $wpdb;
	 $result = $wpdb->get_results( "SELECT username FROM guardiankey WHERE userhash ='".$event['userhash']."'" , OBJECT );
	 foreach ( $result as $page ) {
		 $user = $page->username;
	 }
	 $emailsubject = esc_attr( get_option('mailsubject') );
	 $emailhtml = esc_attr( get_option('mailhtml'));
	 $date = userdate($event["time"], get_string('strftimedatetimeshort', 'langconfig'));
     $time = userdate($event["time"], get_string('strftimetime', 'langconfig'));
     $emailhtml=str_replace("[IP]",$event["ip"],$emailhtml);
      $emailhtml=str_replace("[IP_REVERSE]",$event["ip_reverse"],$emailhtml);
      $emailhtml=str_replace("[CITY]",$event["city"],$emailhtml);
      $emailhtml=str_replace("[USER_AGENT]",$event["useragent"],$emailhtml);
      $emailhtml=str_replace("[SYSTEM]",$event["system"],$emailhtml);
      $emailhtml=str_replace("[DATE]",$date,$emailhtml);
      $emailhtml=str_replace("[TIME]",$time,$emailhtml);
      $emailhtml=str_replace("[]","",$emailhtml);
      $emailhtml=str_replace("()","",$emailhtml);
    
		$info = get_userdata($user);
      $emailuser = $info->user_email;
      add_filter( 'wp_mail_content_type','text/html');
     wp_mail($emailuser,$emailsubject,$emailhtml);
 }
    
	 
function createJob() {
    if (! wp_next_scheduled ( 'getGKevents' )) {
	wp_schedule_event(time(), 'every_three_minutes', 'getGKevents');
    }
}
