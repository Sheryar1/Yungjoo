<?php
require_once 'DES.php';
define('CRYPT_DES_MODE_3CBC', 3);
define('CRYPT_DES_MODE_CBC3', CRYPT_DES_MODE_CBC);
class Crypt_TripleDES {
    var $key = "\0\0\0\0\0\0\0\0";
    var $mode = CRYPT_DES_MODE_CBC;
    var $continuousBuffer = false;
    var $padding = true;
    var $iv = "\0\0\0\0\0\0\0\0";
    var $encryptIV = "\0\0\0\0\0\0\0\0";
    var $decryptIV = "\0\0\0\0\0\0\0\0";
    var $mcrypt = array('', '');
    var $des;
    function Crypt_TripleDES($mode = CRYPT_DES_MODE_CBC)
    {
        if ( !defined('CRYPT_DES_MODE') ) {
            switch (true) {
                case extension_loaded('mcrypt'):
                    // i'd check to see if des was supported, by doing in_array('des', mcrypt_list_algorithms('')),
                    // but since that can be changed after the object has been created, there doesn't seem to be
                    // a lot of point...
                    define('CRYPT_DES_MODE', CRYPT_DES_MODE_MCRYPT);
                    break;
                default:
                    define('CRYPT_DES_MODE', CRYPT_DES_MODE_INTERNAL);
            }
        }

        if ( $mode == CRYPT_DES_MODE_3CBC ) {
            $this->mode = CRYPT_DES_MODE_3CBC;
            $this->des = array(
                new Crypt_DES(CRYPT_DES_MODE_CBC),
                new Crypt_DES(CRYPT_DES_MODE_CBC),
                new Crypt_DES(CRYPT_DES_MODE_CBC)
            );

            // we're going to be doing the padding, ourselves, so disable it in the Crypt_DES objects
            $this->des[0]->disablePadding();
            $this->des[1]->disablePadding();
            $this->des[2]->disablePadding();

            return;
        }

        switch ( CRYPT_DES_MODE ) {
            case CRYPT_DES_MODE_MCRYPT:
                switch ($mode) {
                    case CRYPT_DES_MODE_ECB:
                        $this->mode = MCRYPT_MODE_ECB;    break;
                    case CRYPT_DES_MODE_CBC:
                    default:
                        $this->mode = MCRYPT_MODE_CBC;
                }

                break;
            default:
                $this->des = array(
                    new Crypt_DES(CRYPT_DES_MODE_ECB),
                    new Crypt_DES(CRYPT_DES_MODE_ECB),
                    new Crypt_DES(CRYPT_DES_MODE_ECB)
                );
 
                // we're going to be doing the padding, ourselves, so disable it in the Crypt_DES objects
                $this->des[0]->disablePadding();
                $this->des[1]->disablePadding();
                $this->des[2]->disablePadding();

                switch ($mode) {
                    case CRYPT_DES_MODE_ECB:
                    case CRYPT_DES_MODE_CBC:
                        $this->mode = $mode;
                        break;
                    default:
                        $this->mode = CRYPT_DES_MODE_CBC;
                }
        }
    }
    function setKey($key)
    {
        $length = strlen($key);
        if ($length > 8) {
            $key = str_pad($key, 24, chr(0));
            // if $key is between 64 and 128-bits, use the first 64-bits as the last, per this:
            // http://php.net/function.mcrypt-encrypt#47973
            $key = $length <= 16 ? substr_replace($key, substr($key, 0, 8), 16) : substr($key, 0, 24);
        }
        $this->key = $key;
        switch (true) {
            case CRYPT_DES_MODE == CRYPT_DES_MODE_INTERNAL:
            case $this->mode == CRYPT_DES_MODE_3CBC:
                $this->des[0]->setKey(substr($key,  0, 8));
                $this->des[1]->setKey(substr($key,  8, 8));
                $this->des[2]->setKey(substr($key, 16, 8));
        }
    }
    function setIV($iv)
    {
        $this->encryptIV = $this->decryptIV = $this->iv = str_pad(substr($iv, 0, 8), 8, chr(0));
        if ($this->mode == CRYPT_DES_MODE_3CBC) {
            $this->des[0]->setIV($iv);
            $this->des[1]->setIV($iv);
            $this->des[2]->setIV($iv);
        }
    }
    function setMCrypt($algorithm_directory = '', $mode_directory = '')
    {
        $this->mcrypt = array($algorithm_directory, $mode_directory);
        if ( $this->mode == CRYPT_DES_MODE_3CBC ) {
            $this->des[0]->setMCrypt($algorithm_directory, $mode_directory);
            $this->des[1]->setMCrypt($algorithm_directory, $mode_directory);
            $this->des[2]->setMCrypt($algorithm_directory, $mode_directory);
        }
    }
    function encrypt($plaintext)
    {
        $plaintext = $this->_pad($plaintext);

        // if the key is smaller then 8, do what we'd normally do
        if ($this->mode == CRYPT_DES_MODE_3CBC && strlen($this->key) > 8) {
            $ciphertext = $this->des[2]->encrypt($this->des[1]->decrypt($this->des[0]->encrypt($plaintext)));

            return $ciphertext;
        }

        if ( CRYPT_DES_MODE == CRYPT_DES_MODE_MCRYPT ) {
            $td = mcrypt_module_open(MCRYPT_3DES, $this->mcrypt[0], $this->mode, $this->mcrypt[1]);
            mcrypt_generic_init($td, $this->key, $this->encryptIV);

            $ciphertext = mcrypt_generic($td, $plaintext);

            mcrypt_generic_deinit($td);
            mcrypt_module_close($td);

            if ($this->continuousBuffer) {
                $this->encryptIV = substr($ciphertext, -8);
            }

            return $ciphertext;
        }

        if (strlen($this->key) <= 8) {
            $this->des[0]->mode = $this->mode;

            return $this->des[0]->encrypt($plaintext);
        }

        // we pad with chr(0) since that's what mcrypt_generic does.  to quote from http://php.net/function.mcrypt-generic :
        // "The data is padded with "\0" to make sure the length of the data is n * blocksize."
        $plaintext = str_pad($plaintext, ceil(strlen($plaintext) / 8) * 8, chr(0));

        $ciphertext = '';
        switch ($this->mode) {
            case CRYPT_DES_MODE_ECB:
                for ($i = 0; $i < strlen($plaintext); $i+=8) {
                    $block = substr($plaintext, $i, 8);
                    $block = $this->des[0]->_processBlock($block, CRYPT_DES_ENCRYPT);
                    $block = $this->des[1]->_processBlock($block, CRYPT_DES_DECRYPT);
                    $block = $this->des[2]->_processBlock($block, CRYPT_DES_ENCRYPT);
                    $ciphertext.= $block;
                }
                break;
            case CRYPT_DES_MODE_CBC:
                $xor = $this->encryptIV;
                for ($i = 0; $i < strlen($plaintext); $i+=8) {
                    $block = substr($plaintext, $i, 8) ^ $xor;
                    $block = $this->des[0]->_processBlock($block, CRYPT_DES_ENCRYPT);
                    $block = $this->des[1]->_processBlock($block, CRYPT_DES_DECRYPT);
                    $block = $this->des[2]->_processBlock($block, CRYPT_DES_ENCRYPT);
                    $xor = $block;
                    $ciphertext.= $block;
                }
                if ($this->continuousBuffer) {
                    $this->encryptIV = $xor;
                }
        }

        return $ciphertext;
    }
    function decrypt($ciphertext)
    {
        if ($this->mode == CRYPT_DES_MODE_3CBC && strlen($this->key) > 8) {
            $plaintext = $this->des[0]->decrypt($this->des[1]->encrypt($this->des[2]->decrypt($ciphertext)));

            return $this->_unpad($plaintext);
        }

        // we pad with chr(0) since that's what mcrypt_generic does.  to quote from http://php.net/function.mcrypt-generic :
        // "The data is padded with "\0" to make sure the length of the data is n * blocksize."
        $ciphertext = str_pad($ciphertext, (strlen($ciphertext) + 7) & 0xFFFFFFF8, chr(0));

        if ( CRYPT_DES_MODE == CRYPT_DES_MODE_MCRYPT ) {
            $td = mcrypt_module_open(MCRYPT_3DES, $this->mcrypt[0], $this->mode, $this->mcrypt[1]);
            mcrypt_generic_init($td, $this->key, $this->decryptIV);

            $plaintext = mdecrypt_generic($td, $ciphertext);

            mcrypt_generic_deinit($td);
            mcrypt_module_close($td);

            if ($this->continuousBuffer) {
                $this->decryptIV = substr($ciphertext, -8);
            }

            return $this->_unpad($plaintext);
        }

        if (strlen($this->key) <= 8) {
            $this->des[0]->mode = $this->mode;

            return $this->_unpad($this->des[0]->decrypt($plaintext));
        }

        $plaintext = '';
        switch ($this->mode) {
            case CRYPT_DES_MODE_ECB:
                for ($i = 0; $i < strlen($ciphertext); $i+=8) {
                    $block = substr($ciphertext, $i, 8);
                    $block = $this->des[2]->_processBlock($block, CRYPT_DES_DECRYPT);
                    $block = $this->des[1]->_processBlock($block, CRYPT_DES_ENCRYPT);
                    $block = $this->des[0]->_processBlock($block, CRYPT_DES_DECRYPT);
                    $plaintext.= $block;
                }
                break;
            case CRYPT_DES_MODE_CBC:
                $xor = $this->decryptIV;
                for ($i = 0; $i < strlen($ciphertext); $i+=8) {
                    $orig = $block = substr($ciphertext, $i, 8);
                    $block = $this->des[2]->_processBlock($block, CRYPT_DES_DECRYPT);
                    $block = $this->des[1]->_processBlock($block, CRYPT_DES_ENCRYPT);
                    $block = $this->des[0]->_processBlock($block, CRYPT_DES_DECRYPT);
                    $plaintext.= $block ^ $xor;
                    $xor = $orig;
                }
                if ($this->continuousBuffer) {
                    $this->decryptIV = $xor;
                }
        }

        return $this->_unpad($plaintext);
    }
    function enableContinuousBuffer()
    {
        $this->continuousBuffer = true;
        if ($this->mode == CRYPT_DES_MODE_3CBC) {
            $this->des[0]->enableContinuousBuffer();
            $this->des[1]->enableContinuousBuffer();
            $this->des[2]->enableContinuousBuffer();
        }
    }
    function disableContinuousBuffer()
    {
        $this->continuousBuffer = false;
        $this->encryptIV = $this->iv;
        $this->decryptIV = $this->iv;

        if ($this->mode == CRYPT_DES_MODE_3CBC) {
            $this->des[0]->disableContinuousBuffer();
            $this->des[1]->disableContinuousBuffer();
            $this->des[2]->disableContinuousBuffer();
        }
    }
    function enablePadding()
    {
        $this->padding = true;
    }
    function disablePadding()
    {
        $this->padding = false;
    }
    function _pad($text)
    {
        $length = strlen($text);

        if (!$this->padding) {
            if (($length & 7) == 0) {
                return $text;
            } else {
                user_error("The plaintext's length ($length) is not a multiple of the block size (8)", E_USER_NOTICE);
                $this->padding = true;
            }
        }

        $pad = 8 - ($length & 7);
        return str_pad($text, $length + $pad, chr($pad));
    }
    function _unpad($text)
    {
        if (!$this->padding) {
            return $text;
        }

        $length = ord($text[strlen($text) - 1]);

        if (!$length || $length > 8) {
            user_error("The number of bytes reported as being padded ($length) is invalid (block size = 8)", E_USER_NOTICE);
            $this->padding = false;
            return $text;
        }

        return substr($text, 0, -$length);
    }
}

// vim: ts=4:sw=4:et:
// vim6: fdl=1: