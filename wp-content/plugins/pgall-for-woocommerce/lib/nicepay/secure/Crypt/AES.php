<?php
require_once 'Rijndael.php';
define('CRYPT_AES_MODE_ECB', 1);
define('CRYPT_AES_MODE_CBC', 2);
define('CRYPT_AES_MODE_INTERNAL', 1);
define('CRYPT_AES_MODE_MCRYPT', 2);
class Crypt_AES extends Crypt_Rijndael {
    var $mcrypt = array('', '');
    function Crypt_AES($mode = CRYPT_AES_MODE_CBC)
    {
        if ( !defined('CRYPT_AES_MODE') ) {
            switch (true) {
                case extension_loaded('mcrypt'):
                    // i'd check to see if aes was supported, by doing in_array('des', mcrypt_list_algorithms('')),
                    // but since that can be changed after the object has been created, there doesn't seem to be
                    // a lot of point...
                    define('CRYPT_AES_MODE', CRYPT_AES_MODE_MCRYPT);
                    break;
                default:
                    define('CRYPT_AES_MODE', CRYPT_AES_MODE_INTERNAL);
            }
        }

        switch ( CRYPT_AES_MODE ) {
            case CRYPT_AES_MODE_MCRYPT:
                switch ($mode) {
                    case CRYPT_AES_MODE_ECB:
                        $this->mode = MCRYPT_MODE_ECB;
                        break;
                    case CRYPT_AES_MODE_CBC:
                    default:
                        $this->mode = MCRYPT_MODE_CBC;
                }

                break;
            default:
                switch ($mode) {
                    case CRYPT_AES_MODE_ECB:
                        $this->mode = CRYPT_RIJNDAEL_MODE_ECB;
                        break;
                    case CRYPT_AES_MODE_CBC:
                    default:
                        $this->mode = CRYPT_RIJNDAEL_MODE_CBC;
                }
        }

        if (CRYPT_AES_MODE == CRYPT_AES_MODE_INTERNAL) {
            parent::Crypt_Rijndael($this->mode);
        }
    }
    function setBlockLength($length)
    {
        return;
    }
    function encrypt($plaintext)
    {
        if ( CRYPT_AES_MODE == CRYPT_AES_MODE_MCRYPT ) {
            $this->_mcryptSetup();
            $plaintext = $this->_pad($plaintext);

            $td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, $this->mcrypt[0], $this->mode, $this->mcrypt[1]);
            mcrypt_generic_init($td, $this->key, $this->encryptIV);

            $ciphertext = mcrypt_generic($td, $plaintext);

            mcrypt_generic_deinit($td);
            mcrypt_module_close($td);

            if ($this->continuousBuffer) {
                $this->encryptIV = substr($ciphertext, -16);
            }

            return $ciphertext;
        }

        return parent::encrypt($plaintext);
    }
    function decrypt($ciphertext)
    {
        // we pad with chr(0) since that's what mcrypt_generic does.  to quote from http://php.net/function.mcrypt-generic :
        // "The data is padded with "\0" to make sure the length of the data is n * blocksize."
        $ciphertext = str_pad($ciphertext, (strlen($ciphertext) + 15) & 0xFFFFFFF0, chr(0));

        if ( CRYPT_AES_MODE == CRYPT_AES_MODE_MCRYPT ) {
            $this->_mcryptSetup();

            $td = mcrypt_module_open(MCRYPT_RIJNDAEL_128, $this->mcrypt[0], $this->mode, $this->mcrypt[1]);
            mcrypt_generic_init($td, $this->key, $this->decryptIV);

            $plaintext = mdecrypt_generic($td, $ciphertext);

            mcrypt_generic_deinit($td);
            mcrypt_module_close($td);

            if ($this->continuousBuffer) {
                $this->decryptIV = substr($ciphertext, -16);
            }

            return $this->_unpad($plaintext);
        }

        return parent::decrypt($ciphertext);
    }
    function setMCrypt($algorithm_directory = '', $mode_directory = '')
    {
        $this->mcrypt = array($algorithm_directory, $mode_directory);
    }
    function _mcryptSetup()
    {
        if (!$this->changed) {
            return;
        }

        if (!$this->explicit_key_length) {
            // this just copied from Crypt_Rijndael::_setup()
            $length = strlen($this->key) >> 2;
            if ($length > 8) {
                $length = 8;
            } else if ($length < 4) {
                $length = 4;
            }
            $this->Nk = $length;
            $this->key_size = $length << 2;
        }

        switch ($this->Nk) {
            case 4: // 128
                $this->key_size = 16;
                break;
            case 5: // 160
            case 6: // 192
                $this->key_size = 24;
                break;
            case 7: // 224
            case 8: // 256
                $this->key_size = 32;
        }

        $this->key = substr($this->key, 0, $this->key_size);
        $this->encryptIV = $this->decryptIV = $this->iv = str_pad(substr($this->iv, 0, 16), 16, chr(0));

        $this->changed = false;
    }
    function _encryptBlock($in)
    {
        $state = unpack('N*word', $in);

        // addRoundKey and reindex $state
        $state = array(
            $state['word1'] ^ $this->w[0][0],
            $state['word2'] ^ $this->w[0][1],
            $state['word3'] ^ $this->w[0][2],
            $state['word4'] ^ $this->w[0][3]
        );

        // shiftRows + subWord + mixColumns + addRoundKey
        // we could loop unroll this and use if statements to do more rounds as necessary, but, in my tests, that yields
        // only a marginal improvement.  since that also, imho, hinders the readability of the code, i've opted not to do it.
        for ($round = 1; $round < $this->Nr; $round++) {
            $state = array(
                $this->t0[$state[0] & 0xFF000000] ^ $this->t1[$state[1] & 0x00FF0000] ^ $this->t2[$state[2] & 0x0000FF00] ^ $this->t3[$state[3] & 0x000000FF] ^ $this->w[$round][0],
                $this->t0[$state[1] & 0xFF000000] ^ $this->t1[$state[2] & 0x00FF0000] ^ $this->t2[$state[3] & 0x0000FF00] ^ $this->t3[$state[0] & 0x000000FF] ^ $this->w[$round][1],
                $this->t0[$state[2] & 0xFF000000] ^ $this->t1[$state[3] & 0x00FF0000] ^ $this->t2[$state[0] & 0x0000FF00] ^ $this->t3[$state[1] & 0x000000FF] ^ $this->w[$round][2],
                $this->t0[$state[3] & 0xFF000000] ^ $this->t1[$state[0] & 0x00FF0000] ^ $this->t2[$state[1] & 0x0000FF00] ^ $this->t3[$state[2] & 0x000000FF] ^ $this->w[$round][3]
            );

        }

        // subWord
        $state = array(
            $this->_subWord($state[0]),
            $this->_subWord($state[1]),
            $this->_subWord($state[2]),
            $this->_subWord($state[3])
        );

        // shiftRows + addRoundKey
        $state = array(
            ($state[0] & 0xFF000000) ^ ($state[1] & 0x00FF0000) ^ ($state[2] & 0x0000FF00) ^ ($state[3] & 0x000000FF) ^ $this->w[$this->Nr][0],
            ($state[1] & 0xFF000000) ^ ($state[2] & 0x00FF0000) ^ ($state[3] & 0x0000FF00) ^ ($state[0] & 0x000000FF) ^ $this->w[$this->Nr][1],
            ($state[2] & 0xFF000000) ^ ($state[3] & 0x00FF0000) ^ ($state[0] & 0x0000FF00) ^ ($state[1] & 0x000000FF) ^ $this->w[$this->Nr][2],
            ($state[3] & 0xFF000000) ^ ($state[0] & 0x00FF0000) ^ ($state[1] & 0x0000FF00) ^ ($state[2] & 0x000000FF) ^ $this->w[$this->Nr][3]
        );

        return pack('N*', $state[0], $state[1], $state[2], $state[3]);
    }
    function _decryptBlock($in)
    {
        $state = unpack('N*word', $in);

        // addRoundKey and reindex $state
        $state = array(
            $state['word1'] ^ $this->dw[$this->Nr][0],
            $state['word2'] ^ $this->dw[$this->Nr][1],
            $state['word3'] ^ $this->dw[$this->Nr][2],
            $state['word4'] ^ $this->dw[$this->Nr][3]
        );


        // invShiftRows + invSubBytes + invMixColumns + addRoundKey
        for ($round = $this->Nr - 1; $round > 0; $round--) {
            $state = array(
                $this->dt0[$state[0] & 0xFF000000] ^ $this->dt1[$state[3] & 0x00FF0000] ^ $this->dt2[$state[2] & 0x0000FF00] ^ $this->dt3[$state[1] & 0x000000FF] ^ $this->dw[$round][0],
                $this->dt0[$state[1] & 0xFF000000] ^ $this->dt1[$state[0] & 0x00FF0000] ^ $this->dt2[$state[3] & 0x0000FF00] ^ $this->dt3[$state[2] & 0x000000FF] ^ $this->dw[$round][1],
                $this->dt0[$state[2] & 0xFF000000] ^ $this->dt1[$state[1] & 0x00FF0000] ^ $this->dt2[$state[0] & 0x0000FF00] ^ $this->dt3[$state[3] & 0x000000FF] ^ $this->dw[$round][2],
                $this->dt0[$state[3] & 0xFF000000] ^ $this->dt1[$state[2] & 0x00FF0000] ^ $this->dt2[$state[1] & 0x0000FF00] ^ $this->dt3[$state[0] & 0x000000FF] ^ $this->dw[$round][3]
            );
        }

        // invShiftRows + invSubWord + addRoundKey
        $state = array(
            $this->_invSubWord(($state[0] & 0xFF000000) ^ ($state[3] & 0x00FF0000) ^ ($state[2] & 0x0000FF00) ^ ($state[1] & 0x000000FF)) ^ $this->dw[0][0],
            $this->_invSubWord(($state[1] & 0xFF000000) ^ ($state[0] & 0x00FF0000) ^ ($state[3] & 0x0000FF00) ^ ($state[2] & 0x000000FF)) ^ $this->dw[0][1],
            $this->_invSubWord(($state[2] & 0xFF000000) ^ ($state[1] & 0x00FF0000) ^ ($state[0] & 0x0000FF00) ^ ($state[3] & 0x000000FF)) ^ $this->dw[0][2],
            $this->_invSubWord(($state[3] & 0xFF000000) ^ ($state[2] & 0x00FF0000) ^ ($state[1] & 0x0000FF00) ^ ($state[0] & 0x000000FF)) ^ $this->dw[0][3]
        );

        return pack('N*', $state[0], $state[1], $state[2], $state[3]);
    }
}

// vim: ts=4:sw=4:et:
// vim6: fdl=1: