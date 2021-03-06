<?php
/**
 * ispCP Password Driver
 *
 * Driver for passwords stored in ispCP database
 *
 * @version 1.4
 * @author Sweil <mail@sweil.de>
 * @thx2 Sascha alias TheCry, Aleksander 'A.L.E.C' Machniak <alec@alec.pl>
 * 
 */

class rcube_ispcp_password
{
    function save($curpass, $passwd)
    {
        $rcmail = rcmail::get_instance();

        $sql = "UPDATE `mail_users` SET `mail_pass` = %p , `status` = 'change' WHERE `mail_addr` = %u AND `mail_pass` = %o LIMIT 1";

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
        } else {
            $passwd = $this->encrypt_db_password($passwd);
            $curpass = $this->encrypt_db_password($curpass);
        }        

        // at least we should always have the local part
        $sql = str_replace('%u', $db->quote($_SESSION['username'],'text'), $sql);
        $sql = str_replace('%p', $db->quote($passwd,'text'), $sql);
        $sql = str_replace('%o', $db->quote($curpass,'text'), $sql);

        // execute SQL query
        $res = $db->query($sql);

        // If ok, send request to ispcp daemon
        if (!$db->is_error()) { 
            if ($db->affected_rows($res) == 1) {
                $this->send_request();

            }

         return PASSWORD_SUCCESS; // This is the good case: 1 row updated
        }

        return PASSWORD_ERROR;
    }

    private function encrypt_db_password($db_pass) {

        if (extension_loaded('mcrypt')) {
            
                  $rcmail = rcmail::get_instance();
            $td = @mcrypt_module_open(MCRYPT_BLOWFISH, '', 'cbc', '');
            $key = $rcmail->config->get('ispcp_db_pass_key');
            $iv = $rcmail->config->get('ispcp_db_pass_iv');

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


    /**
     * ISPcp imported functions
     */


    private function read_line(&$socket) {
        $ch = '';
        $line = '';
        do {
            $ch = socket_read($socket, 1);
            $line = $line . $ch;
        } while ($ch != "\r" && $ch != "\n");

        return $line;
    }

    private function send_request() {
        
        //config
        $url = '127.0.0.1';
        $port = 9876;
        $version = '1.0.7';


        @$socket = socket_create (AF_INET, SOCK_STREAM, getprotobyname('telnet'));
        if ($socket < 0) {
            $errno = "socket_create() failed.\n";
            return $errno;
        }
        
        @$result = socket_connect ($socket, $url, $port);
        if ($result == false) {
            $errno = "socket_connect() failed.\n";
            return $errno;
        }

        // read one line with welcome string
        $out = $this->read_line($socket);
        list($code) = explode(' ', $out);
        if ($code == 999) {
            return $out;
        }

        // send helo line
        $query = "helo ".$version."\r\n";
        socket_write ($socket, $query, strlen ($query));

        // read one line key replay
        $execute_reply = $this->read_line($socket);
        list($code) = explode(' ', $execute_reply);
        if ($code == 999) {
            return $out;
        }

        // send reg check query
        $query = "execute query\r\n";
        socket_write ($socket, $query, strlen ($query));

        // read one line key replay
        $execute_reply = $this->read_line($socket);
        list($code) = explode(' ', $execute_reply);
        if ($code == 999) {
            return $out;
        }

        // send quit query
        $quit_query = "bye\r\n";
        socket_write ($socket, $quit_query, strlen($quit_query));

        // read quit answer
        $quit_reply = $this->read_line($socket);
        list($code) = explode(' ', $quit_reply);
        if ($code == 999) {
            return $out;
        }

        list($answer) = explode(' ', $execute_reply);
        socket_close ($socket);
        return $answer;
    }
}
?>
