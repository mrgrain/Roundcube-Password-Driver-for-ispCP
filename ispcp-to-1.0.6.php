<?php

/**
 * ispCP Password Driver
 *
 * Driver for passwords stored in ispCP database
 *
 * @version 1.3
 * @author Sweil <mail@sweil.de>
 * @thx2 Sascha alias TheCry, Aleksander 'A.L.E.C' Machniak <alec@alec.pl>
 * 
 */

function password_save($curpass, $passwd)
{
    $rcmail = rcmail::get_instance();

    $sql = "UPDATE `mail_users` SET `mail_pass` = %p , `status` = 'change' WHERE `mail_addr` = %u LIMIT 1";

    if ($dsn = $rcmail->config->get('password_db_dsn')) {
	    // #1486067: enable new_link option
	    if (is_array($dsn) && empty($dsn['new_link']))
	        $dsn['new_link'] = true;
	    else if (!is_array($dsn) && !preg_match('/\?new_link=true/', $dsn))
	        $dsn .= '?new_link=true';

        $db = new rcube_mdb2($dsn, '', FALSE);
        $db->set_debug((bool)$rcmail->config->get('sql_debug'));
        $db->db_connect('w');
    } else {
        $db = $rcmail->get_dbh();
    }

    if ($err = $db->is_error()) {
        return PASSWORD_CONNECT_ERROR;
    }
        
    // Make special ispCP things   
    if ($rcmail->config->get('ispcp_db_pass_key') == "" || $rcmail->config->get('ispcp_db_pass_iv') == "") {
        return PASSWORD_CONNECT_ERROR;
    }
    else {
        $passwd = encrypt_db_password($passwd,$rcmail->config->get('ispcp_db_pass_key'),$rcmail->config->get('ispcp_db_pass_iv'));
        $curpass = encrypt_db_password($curpass,$rcmail->config->get('ispcp_db_pass_key'),$rcmail->config->get('ispcp_db_pass_iv'));
    }        

    // at least we should always have the local part
    $sql = str_replace('%u', $db->quote($_SESSION['username'],'text'), $sql);
    $sql = str_replace('%p', $db->quote($passwd,'text'), $sql);
    $sql = str_replace('%o', $db->quote($curpass,'text'), $sql);

    $res = $db->query($sql);

    if (!$db->is_error()) {
	    if (strtolower(substr(trim($query),0,6))=='select') {
    	    if ($result = $db->fetch_array($res))
		        return PASSWORD_SUCCESS;
	    } else { 
    	    if ($db->affected_rows($res) == 1)
		        return PASSWORD_SUCCESS; // This is the good case: 1 row updated
	    }
    }

    return PASSWORD_ERROR;
}

function encrypt_db_password($db_pass,$ispcp_db_pass_key,$ispcp_db_pass_iv) {
    if (extension_loaded('mcrypt') || @dl('mcrypt.' . PHP_SHLIB_SUFFIX)) {
        $td = @mcrypt_module_open(MCRYPT_BLOWFISH, '', 'cbc', '');
        // Create key
        $key =  $ispcp_db_pass_key;
        // Create the IV and determine the keysize length
        $iv = $ispcp_db_pass_iv;
        // compatibility with used perl pads
        $block_size = @mcrypt_enc_get_block_size($td);
        $strlen = strlen($db_pass);

        $pads = $block_size-$strlen % $block_size;

        $db_pass .= str_repeat(' ', $pads);

        // Initialize encryption
        @mcrypt_generic_init($td, $key, $iv);
        // Encrypt string
        $encrypted = @mcrypt_generic ($td, $db_pass);
        @mcrypt_generic_deinit($td);
        @mcrypt_module_close($td);

        $text = @base64_encode("$encrypted");

        // Show encrypted string
        return trim($text);
    } else {
        return PASSWORD_CRYPT_ERROR;
    }
}

?>
