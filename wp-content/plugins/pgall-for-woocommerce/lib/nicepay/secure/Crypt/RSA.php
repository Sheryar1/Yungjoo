<?php
require_once('Math/BigInteger.php');
require_once('Crypt/Random.php');
require_once('Crypt/Hash.php');
define('CRYPT_RSA_ENCRYPTION_OAEP',  1);
define('CRYPT_RSA_ENCRYPTION_PKCS1', 2);
define('CRYPT_RSA_SIGNATURE_PSS',  1);
define('CRYPT_RSA_SIGNATURE_PKCS1', 2);
define('CRYPT_RSA_ASN1_INTEGER',   2);
define('CRYPT_RSA_ASN1_SEQUENCE', 48);
define('CRYPT_RSA_MODE_INTERNAL', 1);
define('CRYPT_RSA_MODE_OPENSSL', 2);
define('CRYPT_RSA_PRIVATE_FORMAT_PKCS1', 0);
define('CRYPT_RSA_PUBLIC_FORMAT_RAW', 1);
define('CRYPT_RSA_PUBLIC_FORMAT_PKCS1', 2);
define('CRYPT_RSA_PUBLIC_FORMAT_OPENSSH', 3);
class Crypt_RSA {
    var $zero;
    var $one;
    var $privateKeyFormat = CRYPT_RSA_PRIVATE_FORMAT_PKCS1;
    var $publicKeyFormat = CRYPT_RSA_PUBLIC_FORMAT_PKCS1;
    var $modulus;
    var $k;
    var $exponent;
    var $primes;
    var $exponents;
    var $coefficients;
    var $hashName;
    var $hash;
    var $hLen;
    var $sLen;
    var $mgfHash;
    var $encryptionMode = CRYPT_RSA_ENCRYPTION_OAEP;
    var $signatureMode = CRYPT_RSA_SIGNATURE_PSS;
    var $publicExponent = false;
    var $password = '';
    function Crypt_RSA()
    {
        if ( !defined('CRYPT_RSA_MODE') ) {
            switch (true) {
                //case extension_loaded('openssl') && version_compare(PHP_VERSION, '4.2.0', '>='):
                //    define('CRYPT_RSA_MODE', CRYPT_RSA_MODE_OPENSSL);
                //    break;
                default:
                    define('CRYPT_RSA_MODE', CRYPT_RSA_MODE_INTERNAL);
            }
        }

        $this->zero = new Math_BigInteger();
        $this->one = new Math_BigInteger(1);

        $this->hash = new Crypt_Hash('sha1');
        $this->hLen = $this->hash->getLength();
        $this->hashName = 'sha1';
        $this->mgfHash = new Crypt_Hash('sha1');
    }
    function createKey($bits = 1024, $timeout = false, $primes = array())
    {
        if ( CRYPT_RSA_MODE == CRYPT_RSA_MODE_OPENSSL ) {
            $rsa = openssl_pkey_new(array('private_key_bits' => $bits));
            openssl_pkey_export($rsa, $privatekey);
            $publickey = openssl_pkey_get_details($rsa);
            $publickey = $publickey['key'];

            if ($this->privateKeyFormat != CRYPT_RSA_PRIVATE_FORMAT_PKCS1) {
                $privatekey = call_user_func_array(array($this, '_convertPrivateKey'), array_values($this->_parseKey($privatekey, CRYPT_RSA_PRIVATE_FORMAT_PKCS1)));
                $publickey = call_user_func_array(array($this, '_convertPublicKey'), array_values($this->_parseKey($publickey, CRYPT_RSA_PUBLIC_FORMAT_PKCS1)));
            }

            return array(
                'privatekey' => $privatekey,
                'publickey' => $publickey,
                'partialkey' => false
            );
        }

        static $e;
        if (!isset($e)) {
            if (!defined('CRYPT_RSA_EXPONENT')) {
                // http://en.wikipedia.org/wiki/65537_%28number%29
                define('CRYPT_RSA_EXPONENT', '65537');
            }
            if (!defined('CRYPT_RSA_COMMENT')) {
                define('CRYPT_RSA_COMMENT', 'phpseclib-generated-key');
            }
            // per <http://cseweb.ucsd.edu/~hovav/dist/survey.pdf#page=5>, this number ought not result in primes smaller
            // than 256 bits.
            if (!defined('CRYPT_RSA_SMALLEST_PRIME')) {
                define('CRYPT_RSA_SMALLEST_PRIME', 4096);
            }

            $e = new Math_BigInteger(CRYPT_RSA_EXPONENT);
        }

        extract($this->_generateMinMax($bits));
        $absoluteMin = $min;
        $temp = $bits >> 1;
        if ($temp > CRYPT_RSA_SMALLEST_PRIME) {
            $num_primes = floor($bits / CRYPT_RSA_SMALLEST_PRIME);
            $temp = CRYPT_RSA_SMALLEST_PRIME;
        } else {
            $num_primes = 2;
        }
        extract($this->_generateMinMax($temp + $bits % $temp));
        $finalMax = $max;
        extract($this->_generateMinMax($temp));

        $exponents = $coefficients = array();
        $generator = new Math_BigInteger();
        $generator->setRandomGenerator('crypt_random');

        $n = $this->one->copy();
        $lcm = array(
            'top' => $this->one->copy(),
            'bottom' => false
        );

        $start = time();
        $i0 = count($primes) + 1;

        do {
            for ($i = $i0; $i <= $num_primes; $i++) {
                if ($timeout !== false) {
                    $timeout-= time() - $start;
                    $start = time();
                    if ($timeout <= 0) {
                        return array(
                            'privatekey' => '',
                            'publickey'  => '',
                            'partialkey' => $primes
                        );
                    }
                }
                if ($i == $num_primes) {
                    list($min, $temp) = $absoluteMin->divide($n);
                    if (!$temp->equals($this->zero)) {
                        $min = $min->add($this->one); // ie. ceil()
                    }
                    $primes[$i] = $generator->randomPrime($min, $finalMax, $timeout);
                } else {
                    $primes[$i] = $generator->randomPrime($min, $max, $timeout);
                }

                if ($primes[$i] === false) { // if we've reached the timeout
                    return array(
                        'privatekey' => '',
                        'publickey'  => '',
                        'partialkey' => array_slice($primes, 0, $i - 1)
                    );
                }

                // the first coefficient is calculated differently from the rest
                // ie. instead of being $primes[1]->modInverse($primes[2]), it's $primes[2]->modInverse($primes[1])
                if ($i > 2) {
                    $coefficients[$i] = $n->modInverse($primes[$i]);
                }

                $n = $n->multiply($primes[$i]);

                $temp = $primes[$i]->subtract($this->one);

                // textbook RSA implementations use Euler's totient function instead of the least common multiple.
                // see http://en.wikipedia.org/wiki/Euler%27s_totient_function
                $lcm['top'] = $lcm['top']->multiply($temp);
                $lcm['bottom'] = $lcm['bottom'] === false ? $temp : $lcm['bottom']->gcd($temp);

                $exponents[$i] = $e->modInverse($temp);
            }

            list($lcm) = $lcm['top']->divide($lcm['bottom']);
            $gcd = $lcm->gcd($e);
            $i0 = 1;
        } while (!$gcd->equals($this->one));

        $d = $e->modInverse($lcm);

        $coefficients[2] = $primes[2]->modInverse($primes[1]);

        // from <http://tools.ietf.org/html/rfc3447#appendix-A.1.2>:
        // RSAPrivateKey ::= SEQUENCE {
        //     version           Version,
        //     modulus           INTEGER,  -- n
        //     publicExponent    INTEGER,  -- e
        //     privateExponent   INTEGER,  -- d
        //     prime1            INTEGER,  -- p
        //     prime2            INTEGER,  -- q
        //     exponent1         INTEGER,  -- d mod (p-1)
        //     exponent2         INTEGER,  -- d mod (q-1)
        //     coefficient       INTEGER,  -- (inverse of q) mod p
        //     otherPrimeInfos   OtherPrimeInfos OPTIONAL
        // }

        return array(
            'privatekey' => $this->_convertPrivateKey($n, $e, $d, $primes, $exponents, $coefficients),
            'publickey'  => $this->_convertPublicKey($n, $e),
            'partialkey' => false
        );
    }
    function _convertPrivateKey($n, $e, $d, $primes, $exponents, $coefficients)
    {
        $num_primes = count($primes);

        $raw = array(
            'version' => $num_primes == 2 ? chr(0) : chr(1), // two-prime vs. multi
            'modulus' => $n->toBytes(true),
            'publicExponent' => $e->toBytes(true),
            'privateExponent' => $d->toBytes(true),
            'prime1' => $primes[1]->toBytes(true),
            'prime2' => $primes[2]->toBytes(true),
            'exponent1' => $exponents[1]->toBytes(true),
            'exponent2' => $exponents[2]->toBytes(true),
            'coefficient' => $coefficients[2]->toBytes(true)
        );

        // if the format in question does not support multi-prime rsa and multi-prime rsa was used,
        // call _convertPublicKey() instead.
        switch ($this->privateKeyFormat) {
            default: // eg. CRYPT_RSA_PRIVATE_FORMAT_PKCS1
                $components = array();
                foreach ($raw as $name => $value) {
                    $components[$name] = pack('Ca*a*', CRYPT_RSA_ASN1_INTEGER, $this->_encodeLength(strlen($value)), $value);
                }

                $RSAPrivateKey = implode('', $components);

                if ($num_primes > 2) {
                    $OtherPrimeInfos = '';
                    for ($i = 3; $i <= $num_primes; $i++) {
                        // OtherPrimeInfos ::= SEQUENCE SIZE(1..MAX) OF OtherPrimeInfo
                        //
                        // OtherPrimeInfo ::= SEQUENCE {
                        //     prime             INTEGER,  -- ri
                        //     exponent          INTEGER,  -- di
                        //     coefficient       INTEGER   -- ti
                        // }
                        $OtherPrimeInfo = pack('Ca*a*', CRYPT_RSA_ASN1_INTEGER, $this->_encodeLength(strlen($primes[$i]->toBytes(true))), $primes[$i]->toBytes(true));
                        $OtherPrimeInfo.= pack('Ca*a*', CRYPT_RSA_ASN1_INTEGER, $this->_encodeLength(strlen($exponents[$i]->toBytes(true))), $exponents[$i]->toBytes(true));
                        $OtherPrimeInfo.= pack('Ca*a*', CRYPT_RSA_ASN1_INTEGER, $this->_encodeLength(strlen($coefficients[$i]->toBytes(true))), $coefficients[$i]->toBytes(true));
                        $OtherPrimeInfos.= pack('Ca*a*', CRYPT_RSA_ASN1_SEQUENCE, $this->_encodeLength(strlen($OtherPrimeInfo)), $OtherPrimeInfo);
                    }
                    $RSAPrivateKey.= pack('Ca*a*', CRYPT_RSA_ASN1_SEQUENCE, $this->_encodeLength(strlen($OtherPrimeInfos)), $OtherPrimeInfos);
                }

                $RSAPrivateKey = pack('Ca*a*', CRYPT_RSA_ASN1_SEQUENCE, $this->_encodeLength(strlen($RSAPrivateKey)), $RSAPrivateKey);

                if (!empty($this->password)) {
                    $iv = $this->_random(8);
                    $symkey = pack('H*', md5($this->password . $iv)); // symkey is short for symmetric key
                    $symkey.= substr(pack('H*', md5($symkey . $this->password . $iv)), 0, 8);
                    if (!class_exists('Crypt_TripleDES')) {
                        require_once('Crypt/TripleDES.php');
                    }
                    $des = new Crypt_TripleDES();
                    $des->setKey($symkey);
                    $des->setIV($iv);
                    $iv = strtoupper(bin2hex($iv));
                    $RSAPrivateKey = "-----BEGIN RSA PRIVATE KEY-----\r\n" .
                                     "Proc-Type: 4,ENCRYPTED\r\n" .
                                     "DEK-Info: DES-EDE3-CBC,$iv\r\n" .
                                     "\r\n" .
                                     chunk_split(base64_encode($des->encrypt($RSAPrivateKey))) .
                                     '-----END RSA PRIVATE KEY-----';
                } else {
                    $RSAPrivateKey = "-----BEGIN RSA PRIVATE KEY-----\r\n" .
                                     chunk_split(base64_encode($RSAPrivateKey)) .
                                     '-----END RSA PRIVATE KEY-----';
                }

                return $RSAPrivateKey;
        }
    }
    function _convertPublicKey($n, $e)
    {
        $modulus = $n->toBytes(true);
        $publicExponent = $e->toBytes(true);

        switch ($this->publicKeyFormat) {
            case CRYPT_RSA_PUBLIC_FORMAT_RAW:
                return array('e' => $e->copy(), 'n' => $n->copy());
            case CRYPT_RSA_PUBLIC_FORMAT_OPENSSH:
                // from <http://tools.ietf.org/html/rfc4253#page-15>:
                // string    "ssh-rsa"
                // mpint     e
                // mpint     n
                $RSAPublicKey = pack('Na*Na*Na*', strlen('ssh-rsa'), 'ssh-rsa', strlen($publicExponent), $publicExponent, strlen($modulus), $modulus);
                $RSAPublicKey = 'ssh-rsa ' . base64_encode($RSAPublicKey) . ' ' . CRYPT_RSA_COMMENT;

                return $RSAPublicKey;
            default: // eg. CRYPT_RSA_PUBLIC_FORMAT_PKCS1
                // from <http://tools.ietf.org/html/rfc3447#appendix-A.1.1>:
                // RSAPublicKey ::= SEQUENCE {
                //     modulus           INTEGER,  -- n
                //     publicExponent    INTEGER   -- e
                // }
                $components = array(
                    'modulus' => pack('Ca*a*', CRYPT_RSA_ASN1_INTEGER, $this->_encodeLength(strlen($modulus)), $modulus),
                    'publicExponent' => pack('Ca*a*', CRYPT_RSA_ASN1_INTEGER, $this->_encodeLength(strlen($publicExponent)), $publicExponent)
                );

                $RSAPublicKey = pack('Ca*a*a*',
                    CRYPT_RSA_ASN1_SEQUENCE, $this->_encodeLength(strlen($components['modulus']) + strlen($components['publicExponent'])),
                    $components['modulus'], $components['publicExponent']
                );

                $RSAPublicKey = "-----BEGIN PUBLIC KEY-----\r\n" .
                                 chunk_split(base64_encode($RSAPublicKey)) .
                                 '-----END PUBLIC KEY-----';

                return $RSAPublicKey;
        }
    }
    function _parseKey($key, $type)
    {
        switch ($type) {
            case CRYPT_RSA_PUBLIC_FORMAT_RAW:
                if (!is_array($key)) {
                    return false;
                }
                $components = array();
                switch (true) {
                    case isset($key['e']):
                        $components['publicExponent'] = $key['e']->copy();
                        break;
                    case isset($key['exponent']):
                        $components['publicExponent'] = $key['exponent']->copy();
                        break;
                    case isset($key['publicExponent']):
                        $components['publicExponent'] = $key['publicExponent']->copy();
                        break;
                    case isset($key[0]):
                        $components['publicExponent'] = $key[0]->copy();
                }
                switch (true) {
                    case isset($key['n']):
                        $components['modulus'] = $key['n']->copy();
                        break;
                    case isset($key['modulo']):
                        $components['modulus'] = $key['modulo']->copy();
                        break;
                    case isset($key['modulus']):
                        $components['modulus'] = $key['modulus']->copy();
                        break;
                    case isset($key[1]):
                        $components['modulus'] = $key[1]->copy();
                }
                return $components;
            case CRYPT_RSA_PRIVATE_FORMAT_PKCS1:
            case CRYPT_RSA_PUBLIC_FORMAT_PKCS1:
                if (preg_match('#DEK-Info: DES-EDE3-CBC,(.+)#', $key, $matches)) {
                    $iv = pack('H*', trim($matches[1]));
                    $symkey = pack('H*', md5($this->password . $iv)); // symkey is short for symmetric key
                    $symkey.= substr(pack('H*', md5($symkey . $this->password . $iv)), 0, 8);
                    $ciphertext = base64_decode(preg_replace('#.+(\r|\n|\r\n)\1|[\r\n]|-.+-#s', '', $key));
                    if ($ciphertext === false) {
                        return false;
                    }
                    if (!class_exists('Crypt_TripleDES')) {
                        require_once('Crypt/TripleDES.php');
                    }
                    $des = new Crypt_TripleDES();
                    $des->setKey($symkey);
                    $des->setIV($iv);
                    $key = $des->decrypt($ciphertext);
                } else {
                    $key = base64_decode(preg_replace('#-.+-|[\r\n]#', '', $key));
                    if ($key === false) {
                        return false;
                    }
                }

                $private = false;
                $components = array();

                $this->_string_shift($key); // skip over CRYPT_RSA_ASN1_SEQUENCE
                $this->_decodeLength($key); // skip over the length of the above sequence
                $this->_string_shift($key); // skip over CRYPT_RSA_ASN1_INTEGER
                $length = $this->_decodeLength($key);
                $temp = $this->_string_shift($key, $length);
                if (strlen($temp) != 1 || ord($temp) > 2) {
                    $components['modulus'] = new Math_BigInteger($temp, -256);
                    $this->_string_shift($key); // skip over CRYPT_RSA_ASN1_INTEGER
                    $length = $this->_decodeLength($key);
                    $components[$type == CRYPT_RSA_PUBLIC_FORMAT_PKCS1 ? 'publicExponent' : 'privateExponent'] = new Math_BigInteger($this->_string_shift($key, $length), -256);

                    return $components;
                }
                $this->_string_shift($key); // skip over CRYPT_RSA_ASN1_INTEGER
                $length = $this->_decodeLength($key);
                $components['modulus'] = new Math_BigInteger($this->_string_shift($key, $length), -256);
                $this->_string_shift($key);
                $length = $this->_decodeLength($key);
                $components['publicExponent'] = new Math_BigInteger($this->_string_shift($key, $length), -256);
                $this->_string_shift($key);
                $length = $this->_decodeLength($key);
                $components['privateExponent'] = new Math_BigInteger($this->_string_shift($key, $length), -256);
                $this->_string_shift($key);
                $length = $this->_decodeLength($key);
                $components['primes'] = array(1 => new Math_BigInteger($this->_string_shift($key, $length), -256));
                $this->_string_shift($key);
                $length = $this->_decodeLength($key);
                $components['primes'][] = new Math_BigInteger($this->_string_shift($key, $length), -256);
                $this->_string_shift($key);
                $length = $this->_decodeLength($key);
                $components['exponents'] = array(1 => new Math_BigInteger($this->_string_shift($key, $length), -256));
                $this->_string_shift($key);
                $length = $this->_decodeLength($key);
                $components['exponents'][] = new Math_BigInteger($this->_string_shift($key, $length), -256);
                $this->_string_shift($key);
                $length = $this->_decodeLength($key);
                $components['coefficients'] = array(2 => new Math_BigInteger($this->_string_shift($key, $length), -256));
                if (!empty($key)) {
                    $key = substr($key, 1); // skip over CRYPT_RSA_ASN1_SEQUENCE
                    $this->_decodeLength($key);
                    while (!empty($key)) {
                        $key = substr($key, 1); // skip over CRYPT_RSA_ASN1_SEQUENCE
                        $this->_decodeLength($key);
                        $key = substr($key, 1);
                        $length = $this->_decodeLength($key);
                        $components['primes'][] = new Math_BigInteger($this->_string_shift($key, $length), -256);
                        $this->_string_shift($key);
                        $length = $this->_decodeLength($key);
                        $components['exponents'][] = new Math_BigInteger($this->_string_shift($key, $length), -256);
                        $this->_string_shift($key);
                        $length = $this->_decodeLength($key);
                        $components['coefficients'][] = new Math_BigInteger($this->_string_shift($key, $length), -256);
                    }
                }

                return $components;
            case CRYPT_RSA_PUBLIC_FORMAT_OPENSSH:
                $key = base64_decode(preg_replace('#^ssh-rsa | .+$#', '', $key));
                if ($key === false) {
                    return false;
                }

                $components = array();
                extract(unpack('Nlength', $this->_string_shift($key, 4)));
                $components['modulus'] = new Math_BigInteger($this->_string_shift($key, $length), -256);
                extract(unpack('Nlength', $this->_string_shift($key, 4)));
                $components['publicExponent'] = new Math_BigInteger($this->_string_shift($key, $length), -256);

                return $components;
        }
    }
    function loadKey($key, $type = CRYPT_RSA_PRIVATE_FORMAT_PKCS1)
    {
        $components = $this->_parseKey($key, $type);
        $this->modulus = $components['modulus'];
        $this->k = strlen($this->modulus->toBytes());
        $this->exponent = isset($components['privateExponent']) ? $components['privateExponent'] : $components['publicExponent'];
        if (isset($components['primes'])) {
            $this->primes = $components['primes'];
            $this->exponents = $components['exponents'];
            $this->coefficients = $components['coefficients'];
            $this->publicExponent = $components['publicExponent'];
        } else {
            $this->primes = array();
            $this->exponents = array();
            $this->coefficients = array();
            $this->publicExponent = false;
        }
    }
    function setPassword($password)
    {
        $this->password = $password;
    }
    function setPublicKey($key, $type = CRYPT_RSA_PUBLIC_FORMAT_PKCS1)
    {
        $components = $this->_parseKey($key, $type);
        if (!$this->modulus->equals($components['modulus'])) {
            return false;
        }
        $this->publicExponent = $components['publicExponent'];
    }
    function getPublicKey($type = CRYPT_RSA_PUBLIC_FORMAT_PKCS1)
    {
        $oldFormat = $this->publicKeyFormat;
        $this->publicKeyFormat = $type;
        $temp = $this->_convertPublicKey($this->modulus, $this->publicExponent);
        $this->publicKeyFormat = $oldFormat;
        return $temp;
    }
    function _generateMinMax($bits)
    {
        $bytes = $bits >> 3;
        $min = str_repeat(chr(0), $bytes);
        $max = str_repeat(chr(0xFF), $bytes);
        $msb = $num_bits & 7;
        if ($msb) {
            $min = chr(1 << ($msb - 1)) . $min;
            $max = chr((1 << $msb) - 1) . $max;
        } else {
            $min[0] = chr(0x80);
        }

        return array(
            'min' => new Math_BigInteger($min, 256),
            'max' => new Math_BigInteger($max, 256)
        );
    }
    function _decodeLength(&$string)
    {
        $length = ord($this->_string_shift($string));
        if ( $length & 0x80 ) { // definite length, long form
            $length&= 0x7F;
            $temp = $this->_string_shift($string, $length);
            $start+= $length;
            list(, $length) = unpack('N', substr(str_pad($temp, 4, chr(0), STR_PAD_LEFT), -4));
        }
        return $length;
    }
    function _encodeLength($length)
    {
        if ($length <= 0x7F) {
            return chr($length);
        }

        $temp = ltrim(pack('N', $length), chr(0));
        return pack('Ca*', 0x80 | strlen($temp), $temp);
    }
    function _string_shift(&$string, $index = 1)
    {
        $substr = substr($string, 0, $index);
        $string = substr($string, $index);
        return $substr;
    }
    function setPrivateKeyFormat($format)
    {
        $this->privateKeyFormat = $format;
    }
    function setPublicKeyFormat($format)
    {
        $this->publicKeyFormat = $format;
    }
    function setHash($hash)
    {
        // Crypt_Hash supports algorithms that PKCS#1 doesn't support.  md5-96 and sha1-96, for example.
        switch ($hash) {
            case 'md2':
            case 'md5':
            case 'sha1':
            case 'sha256':
            case 'sha384':
            case 'sha512':
                $this->hash = new Crypt_Hash($hash);
                $this->hLen = $this->hash->getLength();
                $this->hashName = $hash;
                break;
            default:
                $this->hash = new Crypt_Hash('sha1');
                $this->hLen = $this->hash->getLength();
                $this->hashName = 'sha1';
        }
    }
    function setMGFHash($hash)
    {
        // Crypt_Hash supports algorithms that PKCS#1 doesn't support.  md5-96 and sha1-96, for example.
        switch ($hash) {
            case 'md2':
            case 'md5':
            case 'sha1':
            case 'sha256':
            case 'sha384':
            case 'sha512':
                $this->mgfHash = new Crypt_Hash($hash);
                break;
            default:
                $this->mgfHash = new Crypt_Hash('sha1');
        }
    }
    function setSaltLength($sLen)
    {
        $this->sLen = $sLen;
    }
    function _random($bytes, $nonzero = false)
    {
        $temp = '';
        if ($nonzero) {
            for ($i = 0; $i < $bytes; $i++) {
                $temp.= chr(crypt_random(1, 255));
            }
        } else {
            $ints = ($bytes + 1) >> 2;
            for ($i = 0; $i < $ints; $i++) {
                $temp.= pack('N', crypt_random());
            }
            $temp = substr($temp, 0, $bytes);
        }
        return $temp;
    }
    function _i2osp($x, $xLen)
    {
        $x = $x->toBytes();
        if (strlen($x) > $xLen) {
            user_error('Integer too large', E_USER_NOTICE);
            return false;
        }
        return str_pad($x, $xLen, chr(0), STR_PAD_LEFT);
    }
    function _os2ip($x)
    {
        return new Math_BigInteger($x, 256);
    }
    function _exponentiate($x)
    {
        if (empty($this->primes) || empty($this->coefficients) || empty($this->exponents)) {
            return $x->modPow($this->exponent, $this->modulus);
        }

        $num_primes = count($this->primes);
        $m_i = array(
            1 => $x->modPow($this->exponents[1], $this->primes[1]),
            2 => $x->modPow($this->exponents[2], $this->primes[2])
        );
        $h = $m_i[1]->subtract($m_i[2]);
        $h = $h->multiply($this->coefficients[2]);
        list(, $h) = $h->divide($this->primes[1]);
        $m = $m_i[2]->add($h->multiply($this->primes[2]));

        $r = $this->primes[1];
        for ($i = 3; $i <= $num_primes; $i++) {
            $m_i = $x->modPow($this->exponents[$i], $this->primes[$i]);

            $r = $r->multiply($this->primes[$i - 1]);

            $h = $m_i->subtract($m);
            $h = $h->multiply($this->coefficients[$i]);
            list(, $h) = $h->divide($this->primes[$i]);

            $m = $m->add($r->multiply($h));
        }

        return $m;
    }
    function _rsaep($m)
    {
        if ($m->compare($this->zero) < 0 || $m->compare($this->modulus) > 0) {
            user_error('Message representative out of range', E_USER_NOTICE);
            return false;
        }
        return $this->_exponentiate($m);
    }
    function _rsadp($c)
    {
        if ($c->compare($this->zero) < 0 || $c->compare($this->modulus) > 0) {
            user_error('Ciphertext representative out of range', E_USER_NOTICE);
            return false;
        }
        return $this->_exponentiate($c);
    }
    function _rsasp1($m)
    {
        if ($m->compare($this->zero) < 0 || $m->compare($this->modulus) > 0) {
            user_error('Message representative out of range', E_USER_NOTICE);
            return false;
        }
        return $this->_exponentiate($m);
    }
    function _rsavp1($s)
    {
        if ($s->compare($this->zero) < 0 || $s->compare($this->modulus) > 0) {
            user_error('Signature representative out of range', E_USER_NOTICE);
            return false;
        }
        return $this->_exponentiate($s);
    }
    function _mgf1($mgfSeed, $maskLen)
    {
        // if $maskLen would yield strings larger than 4GB, PKCS#1 suggests a "Mask too long" error be output.

        $t = '';
        $count = ceil($maskLen / $this->hLen);
        for ($i = 0; $i < $count; $i++) {
            $c = pack('N', $i);
            $t.= $this->mgfHash->hash($mgfSeed . $c);
        }

        return substr($t, 0, $maskLen);
    }
    function _rsaes_oaep_encrypt($m, $l = '')
    {
        $mLen = strlen($m);

        // Length checking

        // if $l is larger than two million terrabytes and you're using sha1, PKCS#1 suggests a "Label too long" error
        // be output.

        if ($mLen > $this->k - 2 * $this->hLen - 2) {
            user_error('Message too long', E_USER_NOTICE);
            return false;
        }

        // EME-OAEP encoding

        $lHash = $this->hash->hash($l);
        $ps = str_repeat(chr(0), $this->k - $mLen - 2 * $this->hLen - 2);
        $db = $lHash . $ps . chr(1) . $m;
        $seed = $this->_random($this->hLen);
        $dbMask = $this->_mgf1($seed, $this->k - $this->hLen - 1);
        $maskedDB = $db ^ $dbMask;
        $seedMask = $this->_mgf1($maskedDB, $this->hLen);
        $maskedSeed = $seed ^ $seedMask;
        $em = chr(0) . $maskedSeed . $maskedDB;

        // RSA encryption

        $m = $this->_os2ip($em);
        $c = $this->_rsaep($m);
        $c = $this->_i2osp($c, $this->k);

        // Output the ciphertext C

        return $c;
    }
    function _rsaes_oaep_decrypt($c, $l = '')
    {
        // Length checking

        // if $l is larger than two million terrabytes and you're using sha1, PKCS#1 suggests a "Label too long" error
        // be output.

        if (strlen($c) != $this->k || $this->k < 2 * $this->hLen + 2) {
            user_error('Decryption error', E_USER_NOTICE);
            return false;
        }

        // RSA decryption

        $c = $this->_os2ip($c);
        $m = $this->_rsadp($c);
        if ($m === false) {
            user_error('Decryption error', E_USER_NOTICE);
            return false;
        }
        $em = $this->_i2osp($m, $this->k);

        // EME-OAEP decoding

        $lHash = $this->hash->hash($l);
        $y = ord($em[0]);
        $maskedSeed = substr($em, 1, $this->hLen);
        $maskedDB = substr($em, $this->hLen + 1);
        $seedMask = $this->_mgf1($maskedDB, $this->hLen);
        $seed = $maskedSeed ^ $seedMask;
        $dbMask = $this->_mgf1($seed, $this->k - $this->hLen - 1);
        $db = $maskedDB ^ $dbMask;
        $lHash2 = substr($db, 0, $this->hLen);
        $m = substr($db, $this->hLen);
        if ($lHash != $lHash2) {
            user_error('Decryption error', E_USER_NOTICE);
            return false;
        }
        $m = ltrim($m, chr(0));
        if (ord($m[0]) != 1) {
            user_error('Decryption error', E_USER_NOTICE);
            return false;
        }

        // Output the message M

        return substr($m, 1);
    }
    function _rsaes_pkcs1_v1_5_encrypt($m)
    {
        $mLen = strlen($m);

        // Length checking

        if ($mLen > $this->k - 11) {
            user_error('Message too long', E_USER_NOTICE);
            return false;
        }

        // EME-PKCS1-v1_5 encoding

        $ps = $this->_random($this->k - $mLen - 3, true);
        $em = chr(0) . chr(2) . $ps . chr(0) . $m;

        // RSA encryption
        $m = $this->_os2ip($em);
        $c = $this->_rsaep($m);
        $c = $this->_i2osp($c, $this->k);

        // Output the ciphertext C

        return $c;
    }
    function _rsaes_pkcs1_v1_5_decrypt($c)
    {
        // Length checking

        if (strlen($c) != $this->k) { // or if k < 11
            user_error('Decryption error', E_USER_NOTICE);
            return false;
        }

        // RSA decryption

        $c = $this->_os2ip($c);
        $m = $this->_rsadp($c);
        if ($m === false) {
            user_error('Decryption error', E_USER_NOTICE);
            return false;
        }
        $em = $this->_i2osp($m, $this->k);

        // EME-PKCS1-v1_5 decoding

        if (ord($em[0]) != 0 || ord($em[1]) != 2) {
            user_error('Decryption error', E_USER_NOTICE);
            return false;
        }

        $ps = substr($em, 2, strpos($em, chr(0), 2) - 2);
        $m = substr($em, strlen($ps) + 3);

        if (strlen($ps) < 8) {
            user_error('Decryption error', E_USER_NOTICE);
            return false;
        }

        // Output M

        return $m;
    }
    function _emsa_pss_encode($m, $emBits)
    {
        // if $m is larger than two million terrabytes and you're using sha1, PKCS#1 suggests a "Label too long" error
        // be output.

        $emLen = ($emBits + 1) >> 3; // ie. ceil($emBits / 8)
        $sLen = $this->sLen == false ? $this->hLen : $this->sLen;

        $mHash = $this->hash->hash($m);
        if ($emLen < $this->hLen + $sLen + 2) {
            user_error('Encoding error', E_USER_NOTICE);
            return false;
        }

        $salt = $this->_random($sLen);
        $m2 = "\0\0\0\0\0\0\0\0" . $mHash . $salt;
        $h = $this->hash->hash($m2);
        $ps = str_repeat(chr(0), $emLen - $sLen - $this->hLen - 2);
        $db = $ps . chr(1) . $salt;
        $dbMask = $this->_mgf1($h, $emLen - $this->hLen - 1);
        $maskedDB = $db ^ $dbMask;
        $maskedDB[0] = ~chr(0xFF << ($emBits & 7)) & $maskedDB[0];
        $em = $maskedDB . $h . chr(0xBC);

        return $em;
    }
    function _emsa_pss_verify($m, $em, $emBits)
    {
        // if $m is larger than two million terrabytes and you're using sha1, PKCS#1 suggests a "Label too long" error
        // be output.

        $emLen = ($emBits + 1) >> 3; // ie. ceil($emBits / 8);
        $sLen = $this->sLen == false ? $this->hLen : $this->sLen;

        $mHash = $this->hash->hash($m);
        if ($emLen < $this->hLen + $sLen + 2) {
            return false;
        }

        if ($em[strlen($em) - 1] != chr(0xBC)) {
            return false;
        }

        $maskedDB = substr($em, 0, $em - $this->hLen - 1);
        $h = substr($em, $em - $this->hLen - 1, $this->hLen);
        $temp = chr(0xFF << ($emBits & 7));
        if ((~$maskedDB[0] & $temp) != $temp) {
            return false;
        }
        $dbMask = $this->_mgf1($h, $emLen - $this->hLen - 1);
        $db = $maskedDB ^ $dbMask;
        $db[0] = ~chr(0xFF << ($emBits & 7)) & $db[0];
        $temp = $emLen - $this->hLen - $sLen - 2;
        if (substr($db, 0, $temp) != str_repeat(chr(0), $temp) || ord($db[$temp]) != 1) {
            return false;
        }
        $salt = substr($db, $temp + 1); // should be $sLen long
        $m2 = "\0\0\0\0\0\0\0\0" . $mHash . $salt;
        $h2 = $this->hash->hash($m2);
        return $h == $h2;
    }
    function _rsassa_pss_sign($m)
    {
        // EMSA-PSS encoding

        $em = $this->_emsa_pss_encode($m, 8 * $this->k - 1);

        // RSA signature

        $m = $this->_os2ip($em);
        $s = $this->_rsasp1($m);
        $s = $this->_i2osp($s, $this->k);

        // Output the signature S

        return $s;
    }
    function _rsassa_pss_verify($m, $s)
    {
        // Length checking

        if (strlen($s) != $this->k) {
            user_error('Invalid signature', E_USER_NOTICE);
            return false;
        }

        // RSA verification

        $modBits = 8 * $this->k;

        $s2 = $this->_os2ip($s);
        $m2 = $this->_rsavp1($s2);
        if ($m2 === false) {
            user_error('Invalid signature', E_USER_NOTICE);
            return false;
        }
        $em = $this->_i2osp($m2, $modBits >> 3);
        if ($em === false) {
            user_error('Invalid signature', E_USER_NOTICE);
            return false;
        }

        // EMSA-PSS verification

        return $this->_emsa_pss_verify($m, $em, $modBits - 1);
    }
    function _emsa_pkcs1_v1_5_encode($m, $emLen)
    {
        $h = $this->hash->hash($m);
        if ($h === false) {
            return false;
        }

        // see http://tools.ietf.org/html/rfc3447#page-43
        switch ($this->hashName) {
            case 'md2':
                $t = pack('H*', '3020300c06082a864886f70d020205000410');
                break;
            case 'md5':
                $t = pack('H*', '3020300c06082a864886f70d020505000410');
                break;
            case 'sha1':
                $t = pack('H*', '3021300906052b0e03021a05000414');
                break;
            case 'sha256':
                $t = pack('H*', '3031300d060960864801650304020105000420');
                break;
            case 'sha384':
                $t = pack('H*', '3041300d060960864801650304020205000430');
                break;
            case 'sha512':
                $t = pack('H*', '3051300d060960864801650304020305000440');
        }
        $t.= $h;
        $tLen = strlen($t);

        if ($emLen < $tLen + 11) {
            user_error('Intended encoded message length too short', E_USER_NOTICE);
            return false;
        }

        $ps = str_repeat(chr(0xFF), $emLen - $tLen - 3);

        $em = "\0\1$ps\0$t";

        return $em;
    }
    function _rsassa_pkcs1_v1_5_sign($m)
    {
        // EMSA-PKCS1-v1_5 encoding

        $em = $this->_emsa_pkcs1_v1_5_encode($m, $this->k);
        if ($em === false) {
            user_error('RSA modulus too short', E_USER_NOTICE);
            return false;
        }

        // RSA signature

        $m = $this->_os2ip($em);
        $s = $this->_rsasp1($m);
        $s = $this->_i2osp($s, $this->k);

        // Output the signature S

        return $s;
    }
    function _rsassa_pkcs1_v1_5_verify($m, $s)
    {
        // Length checking

        if (strlen($s) != $this->k) {
            user_error('Invalid signature', E_USER_NOTICE);
            return false;
        }

        // RSA verification

        $s = $this->_os2ip($s);
        $m2 = $this->_rsavp1($s);
        if ($m2 === false) {
            user_error('Invalid signature', E_USER_NOTICE);
            return false;
        }
        $em = $this->_i2osp($m2, $this->k);
        if ($em === false) {
            user_error('Invalid signature', E_USER_NOTICE);
            return false;
        }

        // EMSA-PKCS1-v1_5 encoding

        $em2 = $this->_emsa_pkcs1_v1_5_encode($m, $this->k);
        if ($em2 === false) {
            user_error('RSA modulus too short', E_USER_NOTICE);
            return false;
        }

        // Compare

        return $em == $em2;
    }
    function setEncryptionMode($mode)
    {
        $this->encryptionMode = $mode;
    }
    function setSignatureMode($mode)
    {
        $this->signatureMode = $mode;
    }
    function encrypt($plaintext)
    {
        switch ($this->encryptionMode) {
            case CRYPT_RSA_ENCRYPTION_PKCS1:
                $plaintext = str_split($plaintext, $this->k - 11);
                $ciphertext = '';
                foreach ($plaintext as $m) {
                    $ciphertext.= $this->_rsaes_pkcs1_v1_5_encrypt($m);
                }
                return $ciphertext;
            //case CRYPT_RSA_ENCRYPTION_OAEP:
            default:
                $plaintext = str_split($plaintext, $this->k - 2 * $this->hLen - 2);
                $ciphertext = '';
                foreach ($plaintext as $m) {
                    $ciphertext.= $this->_rsaes_oaep_encrypt($m);
                }
                return $ciphertext;
        }
    }
    function decrypt($ciphertext)
    {
        switch ($this->encryptionMode) {
            case CRYPT_RSA_ENCRYPTION_PKCS1:
                $ciphertext = str_split($ciphertext, $this->k);
                $plaintext = '';
                foreach ($ciphertext as $c) {
                    $temp = $this->_rsaes_pkcs1_v1_5_decrypt($c);
                    if ($temp === false) {
                        return false;
                    }
                    $plaintext.= $temp;
                }
                return $plaintext;
            //case CRYPT_RSA_ENCRYPTION_OAEP:
            default:
                $ciphertext = str_split($ciphertext, $this->k);
                $plaintext = '';
                foreach ($ciphertext as $c) {
                    $temp = $this->_rsaes_oaep_decrypt($c);
                    if ($temp === false) {
                        return false;
                    }
                    $plaintext.= $temp;
                }
                return $plaintext;
        }
    }
    function sign($message)
    {
        switch ($this->signatureMode) {
            case CRYPT_RSA_SIGNATURE_PKCS1:
                return $this->_rsassa_pkcs1_v1_5_sign($message);
            //case CRYPT_RSA_SIGNATURE_PSS:
            default:
                return $this->_rsassa_pss_sign($message);
        }
    }
    function verify($message, $signature)
    {
        switch ($this->signatureMode) {
            case CRYPT_RSA_SIGNATURE_PKCS1:
                return $this->_rsassa_pkcs1_v1_5_verify($message, $signature);
            //case CRYPT_RSA_SIGNATURE_PSS:
            default:
                return $this->_rsassa_pss_verify($message, $signature);
        }
    }
}