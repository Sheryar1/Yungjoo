<?php
define('CRYPT_RC4_MODE_INTERNAL', 1);
define('CRYPT_RC4_MODE_MCRYPT', 2);
define('CRYPT_RC4_ENCRYPT', 0);
define('CRYPT_RC4_DECRYPT', 1);
class Crypt_RC4 {
    var $key = "\0";
    var $encryptStream = false;
    var $decryptStream = false;
    var $encryptIndex = 0;
    var $decryptIndex = 0;
    var $mcrypt = array('', '');
    var $mode;
    function Crypt_RC4()
    {
        if ( !defined('CRYPT_RC4_MODE') ) {
            switch (true) {
                case extension_loaded('mcrypt') && (defined('MCRYPT_ARCFOUR') || defined('MCRYPT_RC4')):
                    // i'd check to see if rc4 was supported, by doing in_array('arcfour', mcrypt_list_algorithms('')),
                    // but since that can be changed after the object has been created, there doesn't seem to be
                    // a lot of point...
                    define('CRYPT_RC4_MODE', CRYPT_RC4_MODE_MCRYPT);
                    break;
                default:
                    define('CRYPT_RC4_MODE', CRYPT_RC4_MODE_INTERNAL);
            }
        }

        switch ( CRYPT_RC4_MODE ) {
            case CRYPT_RC4_MODE_MCRYPT:
                switch (true) {
                    case defined('MCRYPT_ARCFOUR'):
                        $this->mode = MCRYPT_ARCFOUR;
                        break;
                    case defined('MCRYPT_RC4');
                        $this->mode = MCRYPT_RC4;
                }
        }
    }
    function setKey($key)
    {
        $this->key = $key;

        if ( CRYPT_RC4_MODE == CRYPT_RC4_MODE_MCRYPT ) {
            return;
        }

        $keyLength = strlen($key);
        $keyStream = array();
        for ($i = 0; $i < 256; $i++) {
            $keyStream[$i] = $i;
        }
        $j = 0;
        for ($i = 0; $i < 256; $i++) {
            $j = ($j + $keyStream[$i] + ord($key[$i % $keyLength])) & 255;
            $temp = $keyStream[$i];
            $keyStream[$i] = $keyStream[$j];
            $keyStream[$j] = $temp;
        }

        $this->encryptIndex = $this->decryptIndex = array(0, 0);
        $this->encryptStream = $this->decryptStream = $keyStream;
    }
    function setIV($iv)
    {
    }
    function setMCrypt($algorithm_directory = '', $mode_directory = '')
    {
        if ( CRYPT_RC4_MODE == CRYPT_RC4_MODE_MCRYPT ) {
            $this->mcrypt = array($algorithm_directory, $mode_directory);
            $this->_closeMCrypt();
        }
    }
    function encrypt($plaintext)
    {
        return $this->_crypt($plaintext, CRYPT_RC4_ENCRYPT);
    }
    function decrypt($ciphertext)
    {
        return $this->_crypt($ciphertext, CRYPT_RC4_DECRYPT);
    }
    function _crypt($text, $mode)
    {
        if ( CRYPT_RC4_MODE == CRYPT_RC4_MODE_MCRYPT ) {
            $keyStream = $mode == CRYPT_RC4_ENCRYPT ? 'encryptStream' : 'decryptStream';

            if ($this->$keyStream === false) {
                $this->$keyStream = mcrypt_module_open($this->mode, $this->mcrypt[0], MCRYPT_MODE_STREAM, $this->mcrypt[1]);
                mcrypt_generic_init($this->$keyStream, $this->key, '');
            } else if (!$this->continuousBuffer) {
                mcrypt_generic_init($this->$keyStream, $this->key, '');
            }
            $newText = mcrypt_generic($this->$keyStream, $text);
            if (!$this->continuousBuffer) {
                mcrypt_generic_deinit($this->$keyStream);
            }

            return $newText;
        }

        if ($this->encryptStream === false) {
            $this->setKey($this->key);
        }

        switch ($mode) {
            case CRYPT_RC4_ENCRYPT:
                $keyStream = $this->encryptStream;
                list($i, $j) = $this->encryptIndex;
                break;
            case CRYPT_RC4_DECRYPT:
                $keyStream = $this->decryptStream;
                list($i, $j) = $this->decryptIndex;
        }

        $newText = '';
        for ($k = 0; $k < strlen($text); $k++) {
            $i = ($i + 1) & 255;
            $j = ($j + $keyStream[$i]) & 255;
            $temp = $keyStream[$i];
            $keyStream[$i] = $keyStream[$j];
            $keyStream[$j] = $temp;
            $temp = $keyStream[($keyStream[$i] + $keyStream[$j]) & 255];
            $newText.= chr(ord($text[$k]) ^ $temp);
        }

        if ($this->continuousBuffer) {
            switch ($mode) {
                case CRYPT_RC4_ENCRYPT:
                    $this->encryptStream = $keyStream;
                    $this->encryptIndex = array($i, $j);
                    break;
                case CRYPT_RC4_DECRYPT:
                    $this->decryptStream = $keyStream;
                    $this->decryptIndex = array($i, $j);
            }
        }

        return $newText;
    }
    function enableContinuousBuffer()
    {
        $this->continuousBuffer = true;
    }
    function disableContinuousBuffer()
    {
        if ( CRYPT_RC4_MODE == CRYPT_RC4_MODE_INTERNAL ) {
            $this->encryptIndex = $this->decryptIndex = array(0, 0);
            $this->setKey($this->key);
        }

        $this->continuousBuffer = false;
    }
    function enablePadding()
    {
    }
    function disablePadding()
    {
    }
    function __destruct()
    {
        if ( CRYPT_RC4_MODE == CRYPT_RC4_MODE_MCRYPT ) {
            $this->_closeMCrypt();
        }
    }
    function _closeMCrypt()
    {
        if ( $this->encryptStream !== false ) {
            if ( $this->continuousBuffer ) {
                mcrypt_generic_deinit($this->encryptStream);
            }

            mcrypt_module_close($this->encryptStream);

            $this->encryptStream = false;
        }

        if ( $this->decryptStream !== false ) {
            if ( $this->continuousBuffer ) {
                mcrypt_generic_deinit($this->decryptStream);
            }

            mcrypt_module_close($this->decryptStream);

            $this->decryptStream = false;
        }
    }
}