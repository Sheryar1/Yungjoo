<?php
require_once('Net/SSH2.php');
define('NET_SFTP_LOG_SIMPLE',  NET_SSH2_LOG_SIMPLE);
define('NET_SFTP_LOG_COMPLEX', NET_SSH2_LOG_COMPLEX);
define('NET_SFTP_LOCAL_FILE', 1);
define('NET_SFTP_STRING',  2);
class Net_SFTP extends Net_SSH2 {
    var $packet_types = array();
    var $status_codes = array();
    var $window_size = 0x7FFFFFFF;
    var $packet_size_server_to_client = 0x4000;
    var $request_id = false;
    var $packet_type = -1;
    var $packet_buffer = '';
    var $extensions = array();
    var $version;
    var $pwd = false;
    var $packet_type_log = array();
    var $packet_log = array();
    function Net_SFTP($host, $port = 22, $timeout = 10)
    {
        parent::Net_SSH2($host, $port, $timeout);
        $this->packet_types = array(
            1  => 'NET_SFTP_INIT',
            2  => 'NET_SFTP_VERSION',
            3  => 'NET_SFTP_OPEN',
            4  => 'NET_SFTP_CLOSE',
            5  => 'NET_SFTP_READ',
            6  => 'NET_SFTP_WRITE',
            8  => 'NET_SFTP_FSTAT',
            9  => 'NET_SFTP_SETSTAT',
            11 => 'NET_SFTP_OPENDIR',
            12 => 'NET_SFTP_READDIR',
            13 => 'NET_SFTP_REMOVE',
            14 => 'NET_SFTP_MKDIR',
            15 => 'NET_SFTP_RMDIR',
            16 => 'NET_SFTP_REALPATH',
            17 => 'NET_SFTP_STAT',
            18 => 'NET_SFTP_RENAME',

            101=> 'NET_SFTP_STATUS',
            102=> 'NET_SFTP_HANDLE',
            103=> 'NET_SFTP_DATA',
            104=> 'NET_SFTP_NAME',
            105=> 'NET_SFTP_ATTRS',

            200=> 'NET_SFTP_EXTENDED'
        );
        $this->status_codes = array(
            0 => 'NET_SFTP_STATUS_OK',
            1 => 'NET_SFTP_STATUS_EOF'
        );
        // http://tools.ietf.org/html/draft-ietf-secsh-filexfer-13#section-7.1
        // the order, in this case, matters quite a lot - see Net_SFTP::_parseAttributes() to understand why
        $this->attributes = array(
            0x00000001 => 'NET_SFTP_ATTR_SIZE',
            0x00000002 => 'NET_SFTP_ATTR_UIDGID', // defined in SFTPv3, removed in SFTPv4+
            0x00000004 => 'NET_SFTP_ATTR_PERMISSIONS',
            0x00000008 => 'NET_SFTP_ATTR_ACCESSTIME',
            0x80000000 => 'NET_SFTP_ATTR_EXTENDED'
        );
        // http://tools.ietf.org/html/draft-ietf-secsh-filexfer-04#section-6.3
        // the flag definitions change somewhat in SFTPv5+.  if SFTPv5+ support is added to this library, maybe name
        // the array for that $this->open5_flags and similarily alter the constant names.
        $this->open_flags = array(
            0x00000001 => 'NET_SFTP_OPEN_READ',
            0x00000002 => 'NET_SFTP_OPEN_WRITE',
            0x00000008 => 'NET_SFTP_OPEN_CREATE',
            0x00000010 => 'NET_SFTP_OPEN_TRUNCATE'
        );
        $this->_define_array(
            $this->packet_types,
            $this->status_codes,
            $this->attributes,
            $this->open_flags
        );
    }
    function login($username, $password = '')
    {
        if (!parent::login($username, $password)) {
            return false;
        }

        $packet = pack('CNa*N3',
            NET_SSH2_MSG_CHANNEL_OPEN, strlen('session'), 'session', $this->client_channel, $this->window_size, $this->packet_size_server_to_client);

        if (!$this->_send_binary_packet($packet)) {
            return false;
        }

        $response = $this->_get_binary_packet();
        if ($response === false) {
            user_error('Connection closed by server', E_USER_NOTICE);
            return false;
        }

        extract(unpack('Ctype', $this->_string_shift($response, 1)));

        switch ($type) {
            case NET_SSH2_MSG_CHANNEL_OPEN_CONFIRMATION:
                $this->_string_shift($response, 4); // skip over client channel
                extract(unpack('Nserver_channel', $this->_string_shift($response, 4)));
                $this->server_channels[$this->client_channel] = $server_channel;
                $this->_string_shift($response, 4); // skip over (server) window size
                extract(unpack('Npacket_size_client_to_server', $this->_string_shift($response, 4)));
                $this->packet_size_client_to_server = $packet_size_client_to_server;
                break;
            case NET_SSH2_MSG_CHANNEL_OPEN_FAILURE:
                user_error('Unable to open channel', E_USER_NOTICE);
                return $this->_disconnect(NET_SSH2_DISCONNECT_BY_APPLICATION);
        }

        $packet = pack('CNNa*CNa*',
            NET_SSH2_MSG_CHANNEL_REQUEST, $server_channel, strlen('subsystem'), 'subsystem', 1, strlen('sftp'), 'sftp');
        if (!$this->_send_binary_packet($packet)) {
            return false;
        }

        $response = $this->_get_binary_packet();
        if ($response === false) {
            user_error('Connection closed by server', E_USER_NOTICE);
            return false;
        }

        extract(unpack('Ctype', $this->_string_shift($response, 1)));

        switch ($type) {
            case NET_SSH2_MSG_CHANNEL_SUCCESS:
                break;
            case NET_SSH2_MSG_CHANNEL_FAILURE:
            default:
                user_error('Unable to initiate SFTP channel', E_USER_NOTICE);
                return $this->_disconnect(NET_SSH2_DISCONNECT_BY_APPLICATION);
        }

        if (!$this->_send_sftp_packet(NET_SFTP_INIT, "\0\0\0\3")) {
            return false;
        }

        $response = $this->_get_sftp_packet();
        if ($this->packet_type != NET_SFTP_VERSION) {
            user_error('Expected SSH_FXP_VERSION', E_USER_NOTICE);
            return false;
        }

        extract(unpack('Nversion', $this->_string_shift($response, 4)));
        $this->version = $version;
        while (!empty($response)) {
            extract(unpack('Nlength', $this->_string_shift($response, 4)));
            $key = $this->_string_shift($response, $length);
            extract(unpack('Nlength', $this->_string_shift($response, 4)));
            $value = $this->_string_shift($response, $length);
            $this->extensions[$key] = $value;
        }

        $this->request_id = 1;
        if ($this->version != 3) {
            return false;
        }

        $this->pwd = $this->_realpath('.');

        return true;
    }
    function pwd()
    {
        return $this->pwd;
    }
    function _realpath($dir)
    {
        $file = '';
        if ($this->pwd !== false) {
            // if the SFTP server returned the canonicalized path even for non-existant files this wouldn't be necessary
            // on OpenSSH it isn't necessary but on other SFTP servers it is.  that and since the specs say nothing on
            // the subject, we'll go ahead and work around it with the following.
            if ($dir[strlen($dir) - 1] != '/') {
                $file = basename($dir);
                $dir = dirname($dir);
            }

            if ($dir == '.' || $dir == $this->pwd) {
                return $this->pwd . $file;
            }

            if ($dir[0] != '/') {
                $dir = $this->pwd . '/' . $dir;
            }
            // on the surface it seems like maybe resolving a path beginning with / is unnecessary, but such paths
            // can contain .'s and ..'s just like any other.  we could parse those out as appropriate or we can let
            // the server do it.  we'll do the latter.
        }
        // http://tools.ietf.org/html/draft-ietf-secsh-filexfer-13#section-8.9
        if (!$this->_send_sftp_packet(NET_SFTP_REALPATH, pack('Na*', strlen($dir), $dir))) {
            return false;
        }

        $response = $this->_get_sftp_packet();
        switch ($this->packet_type) {
            case NET_SFTP_NAME:
                // although SSH_FXP_NAME is implemented differently in SFTPv3 than it is in SFTPv4+, the following
                // should work on all SFTP versions since the only part of the SSH_FXP_NAME packet the following looks
                // at is the first part and that part is defined the same in SFTP versions 3 through 6.
                $this->_string_shift($response, 4); // skip over the count - it should be 1, anyway
                extract(unpack('Nlength', $this->_string_shift($response, 4)));
                $realpath = $this->_string_shift($response, $length);
                break;
            case NET_SFTP_STATUS:
                // skip over the status code - hopefully the error message will give us all the info we need, anyway
                $this->_string_shift($response, 4);
                extract(unpack('Nlength', $this->_string_shift($response, 4)));
                $this->debug_info.= "\r\n\r\nSSH_FXP_STATUS:\r\n" . $this->_string_shift($response, $length);
                return false;
            default:
                user_error('Expected SSH_FXP_NAME or SSH_FXP_STATUS', E_USER_NOTICE);
                return false;
        }

        // if $this->pwd isn't set than the only thing $realpath could be is for '.', which is pretty much guaranteed to
        // be a bonafide directory
        return $realpath . '/' . $file;
    }
    function chdir($dir)
    {
        if (!($this->bitmap & NET_SSH2_MASK_LOGIN)) {
            return false;
        }

        if ($dir[strlen($dir) - 1] != '/') {
            $dir.= '/';
        }
        $dir = $this->_realpath($dir);

        // confirm that $dir is, in fact, a valid directory
        if (!$this->_send_sftp_packet(NET_SFTP_OPENDIR, pack('Na*', strlen($dir), $dir))) {
            return false;
        }

        // see Net_SFTP::nlist() for a more thorough explanation of the following
        $response = $this->_get_sftp_packet();
        switch ($this->packet_type) {
            case NET_SFTP_HANDLE:
                $handle = substr($response, 4);
                break;
            case NET_SFTP_STATUS:
                return false;
            default:
                user_error('Expected SSH_FXP_HANDLE or SSH_FXP_STATUS', E_USER_NOTICE);
                return false;
        }

        if (!$this->_send_sftp_packet(NET_SFTP_CLOSE, pack('Na*', strlen($handle), $handle))) {
            return false;
        }

        $this->_get_sftp_packet();
        if ($this->packet_type != NET_SFTP_STATUS) {
            user_error('Expected SSH_FXP_STATUS', E_USER_NOTICE);
            return false;
        }

        $this->pwd = $dir;
        return true;
    }
    function nlist($dir = '.')
    {
        if (!($this->bitmap & NET_SSH2_MASK_LOGIN)) {
            return false;
        }

        $dir = $this->_realpath($dir);
        if ($dir === false) {
            return false;
        }

        // http://tools.ietf.org/html/draft-ietf-secsh-filexfer-13#section-8.1.2
        if (!$this->_send_sftp_packet(NET_SFTP_OPENDIR, pack('Na*', strlen($dir), $dir))) {
            return false;
        }

        $response = $this->_get_sftp_packet();
        switch ($this->packet_type) {
            case NET_SFTP_HANDLE:
                // http://tools.ietf.org/html/draft-ietf-secsh-filexfer-13#section-9.2
                // since 'handle' is the last field in the SSH_FXP_HANDLE packet, we'll just remove the first four bytes that
                // represent the length of the string and leave it at that
                $handle = substr($response, 4);
                break;
            case NET_SFTP_STATUS:
                // presumably SSH_FX_NO_SUCH_FILE or SSH_FX_PERMISSION_DENIED
                return false;
            default:
                user_error('Expected SSH_FXP_HANDLE or SSH_FXP_STATUS', E_USER_NOTICE);
                return false;
        }

        $contents = array();
        while (true) {
            // http://tools.ietf.org/html/draft-ietf-secsh-filexfer-13#section-8.2.2
            // why multiple SSH_FXP_READDIR packets would be sent when the response to a single one can span arbitrarily many
            // SSH_MSG_CHANNEL_DATA messages is not known to me.
            if (!$this->_send_sftp_packet(NET_SFTP_READDIR, pack('Na*', strlen($handle), $handle))) {
                return false;
            }

            $response = $this->_get_sftp_packet();
            switch ($this->packet_type) {
                case NET_SFTP_NAME:
                    extract(unpack('Ncount', $this->_string_shift($response, 4)));
                    for ($i = 0; $i < $count; $i++) {
                        extract(unpack('Nlength', $this->_string_shift($response, 4)));
                        $contents[] = $this->_string_shift($response, $length);
                        extract(unpack('Nlength', $this->_string_shift($response, 4)));
                        $this->_string_shift($response, $length); // we don't care about the longname
                        $this->_parseAttributes($response); // we also don't care about the attributes
                        // SFTPv6 has an optional boolean end-of-list field, but we'll ignore that, since the
                        // final SSH_FXP_STATUS packet should tell us that, already.
                    }
                    break;
                case NET_SFTP_STATUS:
                    extract(unpack('Nstatus', $this->_string_shift($response, 4)));
                    if ($status != NET_SFTP_STATUS_EOF) {
                        extract(unpack('Nlength', $this->_string_shift($response, 4)));
                        $this->debug_info.= "\r\n\r\nSSH_FXP_STATUS:\r\n" . $this->_string_shift($response, $length);
                        return false;
                    }
                    break 2;
                default:
                    user_error('Expected SSH_FXP_NAME or SSH_FXP_STATUS', E_USER_NOTICE);
                    return false;
            }
        }

        if (!$this->_send_sftp_packet(NET_SFTP_CLOSE, pack('Na*', strlen($handle), $handle))) {
            return false;
        }

        // "The client MUST release all resources associated with the handle regardless of the status."
        //  -- http://tools.ietf.org/html/draft-ietf-secsh-filexfer-13#section-8.1.3
        $this->_get_sftp_packet();
        if ($this->packet_type != NET_SFTP_STATUS) {
            user_error('Expected SSH_FXP_STATUS', E_USER_NOTICE);
            return false;
        }

        return $contents;
    }
    function chmod($mode, $filename)
    {
        if (!($this->bitmap & NET_SSH2_MASK_LOGIN)) {
            return false;
        }

        $filename = $this->_realpath($filename);
        if ($filename === false) {
            return false;
        }

        // SFTPv4+ has an additional byte field - type - that would need to be sent, as well. setting it to
        // SSH_FILEXFER_TYPE_UNKNOWN might work. if not, we'd have to do an SSH_FXP_STAT before doing an SSH_FXP_SETSTAT.
        $attr = pack('N2', NET_SFTP_ATTR_PERMISSIONS, $mode & 07777);
        if (!$this->_send_sftp_packet(NET_SFTP_SETSTAT, pack('Na*a*', strlen($filename), $filename, $attr))) {
            return false;
        }
        $this->_get_sftp_packet();
        if ($this->packet_type != NET_SFTP_STATUS) {
            user_error('Expected SSH_FXP_STATUS', E_USER_NOTICE);
            return false;
        }

        // rather than return what the permissions *should* be, we'll return what they actually are.  this will also
        // tell us if the file actually exists.
        // incidentally ,SFTPv4+ adds an additional 32-bit integer field - flags - to the following:
        $packet = pack('Na*', strlen($filename), $filename);
        if (!$this->_send_sftp_packet(NET_SFTP_STAT, $packet)) {
            return false;
        }

        $response = $this->_get_sftp_packet();
        switch ($this->packet_type) {
            case NET_SFTP_ATTRS:
                $attrs = $this->_parseAttributes($response);
                return $attrs['permissions'];
            case NET_SFTP_STATUS:
                return false;
        }

        user_error('Expected SSH_FXP_ATTRS or SSH_FXP_STATUS', E_USER_NOTICE);
        return false;
    }
    function mkdir($dir)
    {
        if (!($this->bitmap & NET_SSH2_MASK_LOGIN)) {
            return false;
        }

        $dir = $this->_realpath(rtrim($dir, '/'));
        if ($dir === false) {
            return false;
        }

        // by not providing any permissions, hopefully the server will use the logged in users umask - their 
        // default permissions.
        if (!$this->_send_sftp_packet(NET_SFTP_MKDIR, pack('Na*N', strlen($dir), $dir, 0))) {
            return false;
        }

        $response = $this->_get_sftp_packet();
        if ($this->packet_type != NET_SFTP_STATUS) {
            user_error('Expected SSH_FXP_STATUS', E_USER_NOTICE);
            return false;
        }

        extract(unpack('Nstatus', $this->_string_shift($response, 4)));
        if ($status != NET_SFTP_STATUS_OK) {
            extract(unpack('Nlength', $this->_string_shift($response, 4)));
            $this->debug_info.= "\r\n\r\nSSH_FXP_STATUS:\r\n" . $this->_string_shift($response, $length);
            return false;
        }

        return true;
    }
    function rmdir($dir)
    {
        if (!($this->bitmap & NET_SSH2_MASK_LOGIN)) {
            return false;
        }

        $dir = $this->_realpath($dir);
        if ($dir === false) {
            return false;
        }

        if (!$this->_send_sftp_packet(NET_SFTP_RMDIR, pack('Na*', strlen($dir), $dir))) {
            return false;
        }

        $response = $this->_get_sftp_packet();
        if ($this->packet_type != NET_SFTP_STATUS) {
            user_error('Expected SSH_FXP_STATUS', E_USER_NOTICE);
            return false;
        }

        extract(unpack('Nstatus', $this->_string_shift($response, 4)));
        if ($status != NET_SFTP_STATUS_OK) {
            // presumably SSH_FX_NO_SUCH_FILE or SSH_FX_PERMISSION_DENIED?
            return false;
        }

        return true;
    }
    function put($remote_file, $data, $mode = NET_SFTP_STRING)
    {
        if (!($this->bitmap & NET_SSH2_MASK_LOGIN)) {
            return false;
        }

        $remote_file = $this->_realpath($remote_file);
        if ($remote_file === false) {
            return false;
        }

        $packet = pack('Na*N2', strlen($remote_file), $remote_file, NET_SFTP_OPEN_WRITE | NET_SFTP_OPEN_CREATE | NET_SFTP_OPEN_TRUNCATE, 0);
        if (!$this->_send_sftp_packet(NET_SFTP_OPEN, $packet)) {
            return false;
        }

        $response = $this->_get_sftp_packet();
        switch ($this->packet_type) {
            case NET_SFTP_HANDLE:
                $handle = substr($response, 4);
                break;
            case NET_SFTP_STATUS:
                $this->_string_shift($response, 4);
                extract(unpack('Nlength', $this->_string_shift($response, 4)));
                $this->debug_info.= "\r\n\r\nSSH_FXP_STATUS:\r\n" . $this->_string_shift($response, $length);
                return false;
            default:
                user_error('Expected SSH_FXP_HANDLE or SSH_FXP_STATUS', E_USER_NOTICE);
                return false;
        }

        $initialize = true;

        // http://tools.ietf.org/html/draft-ietf-secsh-filexfer-13#section-8.2.3
        if ($mode == NET_SFTP_LOCAL_FILE) {
            if (!is_file($data)) {
                user_error("$data is not a valid file", E_USER_NOTICE);
                return false;
            }
            $fp = fopen($data, 'r');
            if (!$fp) {
                return false;
            }
            $sent = 0;
            $size = filesize($data);
        } else {
            $sent = 0;
            $size = strlen($data);
        }

        $i = 0;
        $sftp_packet_size = 34000; // PuTTY uses 4096
        while ($sent < $size) {
            $temp = $mode == NET_SFTP_LOCAL_FILE ? fread($fp, $sftp_packet_size) : $this->_string_shift($data, $sftp_packet_size);
            $packet = pack('Na*N3a*', strlen($handle), $handle, 0, $sent, strlen($temp), $temp);
            if (!$this->_send_sftp_packet(NET_SFTP_WRITE, $packet)) {
                fclose($fp);
                return false;
            }
            $sent+= strlen($temp);

            $i++;
        }

        while ($i-- > 0){
            $this->_get_sftp_packet();
            if ($this->packet_type != NET_SFTP_STATUS) {
                user_error('Expected SSH_FXP_STATUS', E_USER_NOTICE);
                return false;
            }
        }

        if ($mode == NET_SFTP_LOCAL_FILE) {
            fclose($fp);
        }

        if (!$this->_send_sftp_packet(NET_SFTP_CLOSE, pack('Na*', strlen($handle), $handle))) {
            return false;
        }

        $this->_get_sftp_packet();
        if ($this->packet_type != NET_SFTP_STATUS) {
            user_error('Expected SSH_FXP_STATUS', E_USER_NOTICE);
            return false;
        }

        return true;
    }
    function get($remote_file, $local_file = false)
    {
        if (!($this->bitmap & NET_SSH2_MASK_LOGIN)) {
            return false;
        }

        $remote_file = $this->_realpath($remote_file);
        if ($remote_file === false) {
            return false;
        }

        $packet = pack('Na*N2', strlen($remote_file), $remote_file, NET_SFTP_OPEN_READ, 0);
        if (!$this->_send_sftp_packet(NET_SFTP_OPEN, $packet)) {
            return false;
        }

        $response = $this->_get_sftp_packet();
        switch ($this->packet_type) {
            case NET_SFTP_HANDLE:
                $handle = substr($response, 4);
                break;
            case NET_SFTP_STATUS: // presumably SSH_FX_NO_SUCH_FILE or SSH_FX_PERMISSION_DENIED
                return false;
            default:
                user_error('Expected SSH_FXP_HANDLE or SSH_FXP_STATUS', E_USER_NOTICE);
                return false;
        }

        $packet = pack('Na*', strlen($handle), $handle);
        if (!$this->_send_sftp_packet(NET_SFTP_FSTAT, $packet)) {
            return false;
        }

        $response = $this->_get_sftp_packet();
        switch ($this->packet_type) {
            case NET_SFTP_ATTRS:
                $attrs = $this->_parseAttributes($response);
                break;
            case NET_SFTP_STATUS:
                return false;
            default:
                user_error('Expected SSH_FXP_ATTRS or SSH_FXP_STATUS', E_USER_NOTICE);
                return false;
        }


        if ($local_file !== false) {
            $fp = fopen($local_file, 'w');
            if (!$fp) {
                return false;
            }
        } else {
            $content = '';
        }

        $read = 0;
        while ($read < $attrs['size']) {
            $packet = pack('Na*N3', strlen($handle), $handle, 0, $read, 1 << 20);
            if (!$this->_send_sftp_packet(NET_SFTP_READ, $packet)) {
                return false;
            }

            $response = $this->_get_sftp_packet();
            switch ($this->packet_type) {
                case NET_SFTP_DATA:
                    $temp = substr($response, 4);
                    $read+= strlen($temp);
                    if ($local_file === false) {
                        $content.= $temp;
                    } else {
                        fputs($fp, $temp);
                    }
                    break;
                case NET_SFTP_STATUS:
                    $this->_string_shift($response, 4);
                    extract(unpack('Nlength', $this->_string_shift($response, 4)));
                    $this->debug_info.= "\r\n\r\nSSH_FXP_STATUS:\r\n" . $this->_string_shift($response, $length);
                    return false;
                default:
                    user_error('Expected SSH_FXP_DATA or SSH_FXP_STATUS', E_USER_NOTICE);
                    return false;
            }
        }

        if (!$this->_send_sftp_packet(NET_SFTP_CLOSE, pack('Na*', strlen($handle), $handle))) {
            return false;
        }

        $this->_get_sftp_packet();
        if ($this->packet_type != NET_SFTP_STATUS) {
            user_error('Expected SSH_FXP_STATUS', E_USER_NOTICE);
            return false;
        }

        if (isset($content)) {
            return $content;
        }

        fclose($fp);
        return true;
    }
    function delete($path)
    {
        if (!($this->bitmap & NET_SSH2_MASK_LOGIN)) {
            return false;
        }

        $remote_file = $this->_realpath($path);
        if ($path === false) {
            return false;
        }

        // http://tools.ietf.org/html/draft-ietf-secsh-filexfer-13#section-8.3
        if (!$this->_send_sftp_packet(NET_SFTP_REMOVE, pack('Na*', strlen($path), $path))) {
            return false;
        }

        $response = $this->_get_sftp_packet();
        if ($this->packet_type != NET_SFTP_STATUS) {
            user_error('Expected SSH_FXP_STATUS', E_USER_NOTICE);
            return false;
        }

        extract(unpack('Nstatus', $this->_string_shift($response, 4)));

        // if $status isn't SSH_FX_OK it's probably SSH_FX_NO_SUCH_FILE or SSH_FX_PERMISSION_DENIED
        return $status == NET_SFTP_STATUS_OK;
    }
    function rename($oldname, $newname)
    {
        if (!($this->bitmap & NET_SSH2_MASK_LOGIN)) {
            return false;
        }

        $oldname = $this->_realpath($oldname);
        $newname = $this->_realpath($newname);
        if ($oldname === false || $newname === false) {
            return false;
        }

        // http://tools.ietf.org/html/draft-ietf-secsh-filexfer-13#section-8.3
        $packet = pack('Na*Na*', strlen($oldname), $oldname, strlen($newname), $newname);
        if (!$this->_send_sftp_packet(NET_SFTP_RENAME, $packet)) {
            return false;
        }

        $response = $this->_get_sftp_packet();
        if ($this->packet_type != NET_SFTP_STATUS) {
            user_error('Expected SSH_FXP_STATUS', E_USER_NOTICE);
            return false;
        }

        extract(unpack('Nstatus', $this->_string_shift($response, 4)));

        // if $status isn't SSH_FX_OK it's probably SSH_FX_NO_SUCH_FILE or SSH_FX_PERMISSION_DENIED
        return $status == NET_SFTP_STATUS_OK;
    }
    function _parseAttributes(&$response)
    {
        $attr = array();
        extract(unpack('Nflags', $this->_string_shift($response, 4)));
        // SFTPv4+ have a type field (a byte) that follows the above flag field
        foreach ($this->attributes as $key => $value) {
            switch ($flags & $key) {
                case NET_SFTP_ATTR_SIZE: // 0x00000001
                    // size is represented by a 64-bit integer, so we perhaps ought to be doing the following:
                    //$attr['size'] = new Math_BigInteger($this->_string_shift($response, 8), 256);
                    // of course, you shouldn't be using Net_SFTP to transfer files that are in excess of 4GB
                    // (0xFFFFFFFF bytes), anyway.  as such, we'll just represent all file sizes that are bigger than
                    // 4GB as being 4GB.
                    extract(unpack('Nupper/Nsize', $this->_string_shift($response, 8)));
                    if ($upper) {
                        $attr['size'] = 0xFFFFFFFF;
                    } else {
                        $attr['size'] = $size;
                    }
                    break;
                case NET_SFTP_ATTR_UIDGID: // 0x00000002 (SFTPv3 only)
                    $attr+= unpack('Nuid/Ngid', $this->_string_shift($response, 8));
                    break;
                case NET_SFTP_ATTR_PERMISSIONS: // 0x00000004
                    $attr+= unpack('Npermissions', $this->_string_shift($response, 4));
                    break;
                case NET_SFTP_ATTR_ACCESSTIME: // 0x00000008
                    $attr+= unpack('Natime/Nmtime', $this->_string_shift($response, 8));
                    break;
                case NET_SFTP_ATTR_EXTENDED: // 0x80000000
                    extract(unpack('Ncount', $this->_string_shift($response, 4)));
                    for ($i = 0; $i < $count; $i++) {
                        extract(unpack('Nlength', $this->_string_shift($response, 4)));
                        $key = $this->_string_shift($response, $length);
                        extract(unpack('Nlength', $this->_string_shift($response, 4)));
                        $attr[$key] = $this->_string_shift($response, $length);                        
                    }
            }
        }
        return $attr;
    }
    function _send_sftp_packet($type, $data)
    {
        $packet = $this->request_id !== false ?
            pack('NCNa*', strlen($data) + 5, $type, $this->request_id, $data) :
            pack('NCa*',  strlen($data) + 1, $type, $data);

        $start = strtok(microtime(), ' ') + strtok(''); // http://php.net/microtime#61838
        $result = $this->_send_channel_packet($packet);
        $stop = strtok(microtime(), ' ') + strtok('');

        if (defined('NET_SFTP_LOGGING')) {
            $this->packet_type_log[] = '-> ' . $this->packet_types[$type] . 
                                       ' (' . round($stop - $start, 4) . 's)';
            $this->packet_log[] = $data;
        }

        return $result;
    }
    function _get_sftp_packet()
    {
        $start = strtok(microtime(), ' ') + strtok(''); // http://php.net/microtime#61838

        // SFTP packet length
        while (strlen($this->packet_buffer) < 4) {
            $temp = $this->_get_channel_packet();
            if (is_bool($temp)) {
                $this->packet_type = false;
                $this->packet_buffer = '';
                return false;
            }
            $this->packet_buffer.= $temp;
        }
        extract(unpack('Nlength', $this->_string_shift($this->packet_buffer, 4)));
        $tempLength = $length;
        $tempLength-= strlen($this->packet_buffer);

        // SFTP packet type and data payload
        while ($tempLength > 0) {
            $temp = $this->_get_channel_packet();
            if (is_bool($temp)) {
                $this->packet_type = false;
                $this->packet_buffer = '';
                return false;
            }
            $this->packet_buffer.= $temp;
            $tempLength-= strlen($temp);
        }

        $stop = strtok(microtime(), ' ') + strtok('');

        $this->packet_type = ord($this->_string_shift($this->packet_buffer));

        if (defined('NET_SFTP_LOGGING')) {
            $this->packet_type_log[] = '<- ' . $this->packet_types[$this->packet_type] . 
                                       ' (' . round($stop - $start, 4) . 's)';
            $this->packet_log[] = $packet;
        }

        if ($this->request_id !== false) {
            $this->_string_shift($this->packet_buffer, 4); // remove the request id
            $length-= 5; // account for the request id and the packet type
        } else {
            $length-= 1; // account for the packet type
        }

        return $this->_string_shift($this->packet_buffer, $length);
    }
    function getSFTPLog($type = NET_SFTP_LOG_COMPLEX)
    {
        $message_number_log = $this->message_number_log;
        $message_log = $this->message_log;

        $this->message_number_log = $this->packet_type_log;
        $this->message_log = $this->packet_log;

        $return = $this->getLog($type);

        $this->message_number_log = $message_number_log;
        $this->message_log = $message_log;

        return $return;
    }
    function getSupportedVersions()
    {
        $temp = array('version' => $this->version);
        if (isset($this->extensions['versions'])) {
            $temp['extensions'] = $this->extensions['versions'];
        }
        return $temp;
    }
    function _disconnect($reason)
    {
        $this->pwd = false;
        parent::_disconnect($reason);
    }
}