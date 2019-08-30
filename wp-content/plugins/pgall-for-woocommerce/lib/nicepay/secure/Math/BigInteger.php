<?php
define('MATH_BIGINTEGER_MONTGOMERY', 0);
define('MATH_BIGINTEGER_BARRETT', 1);
define('MATH_BIGINTEGER_POWEROF2', 2);
define('MATH_BIGINTEGER_CLASSIC', 3);
define('MATH_BIGINTEGER_NONE', 4);
define('MATH_BIGINTEGER_VARIABLE', 0);
define('MATH_BIGINTEGER_DATA', 1);
define('MATH_BIGINTEGER_MODE_INTERNAL', 1);
define('MATH_BIGINTEGER_MODE_BCMATH', 2);
define('MATH_BIGINTEGER_MODE_GMP', 3);
define('MATH_BIGINTEGER_MAX_DIGIT52', pow(2, 52));
define('MATH_BIGINTEGER_KARATSUBA_CUTOFF', 15);
class Math_BigInteger {
    var $value;
    var $is_negative = false;
    var $generator = 'mt_rand';
    var $precision = -1;
    var $bitmask = false;
    function Math_BigInteger($x = 0, $base = 10)
    {
        if ( !defined('MATH_BIGINTEGER_MODE') ) {
            switch (true) {
                case extension_loaded('gmp'):
                    define('MATH_BIGINTEGER_MODE', MATH_BIGINTEGER_MODE_GMP);
                    break;
                case extension_loaded('bcmath'):
                    define('MATH_BIGINTEGER_MODE', MATH_BIGINTEGER_MODE_BCMATH);
                    break;
                default:
                    define('MATH_BIGINTEGER_MODE', MATH_BIGINTEGER_MODE_INTERNAL);
            }
        }

        switch ( MATH_BIGINTEGER_MODE ) {
            case MATH_BIGINTEGER_MODE_GMP:
                if (is_resource($x) && get_resource_type($x) == 'GMP integer') {
                    $this->value = $x;
                    return;
                }
                $this->value = gmp_init(0);
                break;
            case MATH_BIGINTEGER_MODE_BCMATH:
                $this->value = '0';
                break;
            default:
                $this->value = array();
        }

        if ($x === 0) {
            return;
        }

        switch ($base) {
            case -256:
                if (ord($x[0]) & 0x80) {
                    $x = ~$x;
                    $this->is_negative = true;
                }
            case  256:
                switch ( MATH_BIGINTEGER_MODE ) {
                    case MATH_BIGINTEGER_MODE_GMP:
                        $sign = $this->is_negative ? '-' : '';
                        $this->value = gmp_init($sign . '0x' . bin2hex($x));
                        break;
                    case MATH_BIGINTEGER_MODE_BCMATH:
                        // round $len to the nearest 4 (thanks, DavidMJ!)
                        $len = (strlen($x) + 3) & 0xFFFFFFFC;

                        $x = str_pad($x, $len, chr(0), STR_PAD_LEFT);

                        for ($i = 0; $i < $len; $i+= 4) {
                            $this->value = bcmul($this->value, '4294967296'); // 4294967296 == 2**32
                            $this->value = bcadd($this->value, 0x1000000 * ord($x[$i]) + ((ord($x[$i + 1]) << 16) | (ord($x[$i + 2]) << 8) | ord($x[$i + 3])));
                        }

                        if ($this->is_negative) {
                            $this->value = '-' . $this->value;
                        }

                        break;
                    // converts a base-2**8 (big endian / msb) number to base-2**26 (little endian / lsb)
                    default:
                        while (strlen($x)) {
                            $this->value[] = $this->_bytes2int($this->_base256_rshift($x, 26));
                        }
                }

                if ($this->is_negative) {
                    if (MATH_BIGINTEGER_MODE != MATH_BIGINTEGER_MODE_INTERNAL) {
                        $this->is_negative = false;
                    }
                    $temp = $this->add(new Math_BigInteger('-1'));
                    $this->value = $temp->value;
                }
                break;
            case  16:
            case -16:
                if ($base > 0 && $x[0] == '-') {
                    $this->is_negative = true;
                    $x = substr($x, 1);
                }

                $x = preg_replace('#^(?:0x)?([A-Fa-f0-9]*).*#', '$1', $x);

                $is_negative = false;
                if ($base < 0 && hexdec($x[0]) >= 8) {
                    $this->is_negative = $is_negative = true;
                    $x = bin2hex(~pack('H*', $x));
                }

                switch ( MATH_BIGINTEGER_MODE ) {
                    case MATH_BIGINTEGER_MODE_GMP:
                        $temp = $this->is_negative ? '-0x' . $x : '0x' . $x;
                        $this->value = gmp_init($temp);
                        $this->is_negative = false;
                        break;
                    case MATH_BIGINTEGER_MODE_BCMATH:
                        $x = ( strlen($x) & 1 ) ? '0' . $x : $x;
                        $temp = new Math_BigInteger(pack('H*', $x), 256);
                        $this->value = $this->is_negative ? '-' . $temp->value : $temp->value;
                        $this->is_negative = false;
                        break;
                    default:
                        $x = ( strlen($x) & 1 ) ? '0' . $x : $x;
                        $temp = new Math_BigInteger(pack('H*', $x), 256);
                        $this->value = $temp->value;
                }

                if ($is_negative) {
                    $temp = $this->add(new Math_BigInteger('-1'));
                    $this->value = $temp->value;
                }
                break;
            case  10:
            case -10:
                $x = preg_replace('#^(-?[0-9]*).*#', '$1', $x);

                switch ( MATH_BIGINTEGER_MODE ) {
                    case MATH_BIGINTEGER_MODE_GMP:
                        $this->value = gmp_init($x);
                        break;
                    case MATH_BIGINTEGER_MODE_BCMATH:
                        // explicitly casting $x to a string is necessary, here, since doing $x[0] on -1 yields different
                        // results then doing it on '-1' does (modInverse does $x[0])
                        $this->value = (string) $x;
                        break;
                    default:
                        $temp = new Math_BigInteger();

                        // array(10000000) is 10**7 in base-2**26.  10**7 is the closest to 2**26 we can get without passing it.
                        $multiplier = new Math_BigInteger();
                        $multiplier->value = array(10000000);

                        if ($x[0] == '-') {
                            $this->is_negative = true;
                            $x = substr($x, 1);
                        }

                        $x = str_pad($x, strlen($x) + (6 * strlen($x)) % 7, 0, STR_PAD_LEFT);

                        while (strlen($x)) {
                            $temp = $temp->multiply($multiplier);
                            $temp = $temp->add(new Math_BigInteger($this->_int2bytes(substr($x, 0, 7)), 256));
                            $x = substr($x, 7);
                        }

                        $this->value = $temp->value;
                }
                break;
            case  2: // base-2 support originally implemented by Lluis Pamies - thanks!
            case -2:
                if ($base > 0 && $x[0] == '-') {
                    $this->is_negative = true;
                    $x = substr($x, 1);
                }

                $x = preg_replace('#^([01]*).*#', '$1', $x);
                $x = str_pad($x, strlen($x) + (3 * strlen($x)) % 4, 0, STR_PAD_LEFT);

                $str = '0x';
                while (strlen($x)) {
                    $part = substr($x, 0, 4);
                    $str.= dechex(bindec($part));
                    $x = substr($x, 4);
                }

                if ($this->is_negative) {
                    $str = '-' . $str;
                }

                $temp = new Math_BigInteger($str, 8 * $base); // ie. either -16 or +16
                $this->value = $temp->value;
                $this->is_negative = $temp->is_negative;

                break;
            default:
                // base not supported, so we'll let $this == 0
        }
    }
    function toBytes($twos_compliment = false)
    {
        if ($twos_compliment) {
            $comparison = $this->compare(new Math_BigInteger());
            if ($comparison == 0) {
                return $this->precision > 0 ? str_repeat(chr(0), ($this->precision + 1) >> 3) : '';
            }

            $temp = $comparison < 0 ? $this->add(new Math_BigInteger(1)) : $this->copy();
            $bytes = $temp->toBytes();

            if (empty($bytes)) { // eg. if the number we're trying to convert is -1
                $bytes = chr(0);
            }

            if (ord($bytes[0]) & 0x80) {
                $bytes = chr(0) . $bytes;
            }

            return $comparison < 0 ? ~$bytes : $bytes;
        }

        switch ( MATH_BIGINTEGER_MODE ) {
            case MATH_BIGINTEGER_MODE_GMP:
                if (gmp_cmp($this->value, gmp_init(0)) == 0) {
                    return $this->precision > 0 ? str_repeat(chr(0), ($this->precision + 1) >> 3) : '';
                }

                $temp = gmp_strval(gmp_abs($this->value), 16);
                $temp = ( strlen($temp) & 1 ) ? '0' . $temp : $temp;
                $temp = pack('H*', $temp);

                return $this->precision > 0 ?
                    substr(str_pad($temp, $this->precision >> 3, chr(0), STR_PAD_LEFT), -($this->precision >> 3)) :
                    ltrim($temp, chr(0));
            case MATH_BIGINTEGER_MODE_BCMATH:
                if ($this->value === '0') {
                    return $this->precision > 0 ? str_repeat(chr(0), ($this->precision + 1) >> 3) : '';
                }

                $value = '';
                $current = $this->value;

                if ($current[0] == '-') {
                    $current = substr($current, 1);
                }

                // we don't do four bytes at a time because then numbers larger than 1<<31 would be negative
                // two's complimented numbers, which would break chr.
                while (bccomp($current, '0') > 0) {
                    $temp = bcmod($current, 0x1000000);
                    $value = chr($temp >> 16) . chr($temp >> 8) . chr($temp) . $value;
                    $current = bcdiv($current, 0x1000000);
                }

                return $this->precision > 0 ?
                    substr(str_pad($value, $this->precision >> 3, chr(0), STR_PAD_LEFT), -($this->precision >> 3)) :
                    ltrim($value, chr(0));
        }

        if (!count($this->value)) {
            return $this->precision > 0 ? str_repeat(chr(0), ($this->precision + 1) >> 3) : '';
        }
        $result = $this->_int2bytes($this->value[count($this->value) - 1]);

        $temp = $this->copy();

        for ($i = count($temp->value) - 2; $i >= 0; $i--) {
            $temp->_base256_lshift($result, 26);
            $result = $result | str_pad($temp->_int2bytes($temp->value[$i]), strlen($result), chr(0), STR_PAD_LEFT);
        }

        return $this->precision > 0 ?
            substr(str_pad($result, $this->precision >> 3, chr(0), STR_PAD_LEFT), -($this->precision >> 3)) :
            $result;
    }
    function toHex($twos_compliment = false)
    {
        return bin2hex($this->toBytes($twos_compliment));
    }
    function toBits($twos_compliment = false)
    {
        $hex = $this->toHex($twos_compliment);
        $bits = '';
        for ($i = 0; $i < strlen($hex); $i+=8) {
            $bits.= str_pad(decbin(hexdec(substr($hex, $i, 8))), 32, '0', STR_PAD_LEFT);
        }
        return $this->precision > 0 ? substr($bits, -$this->precision) : ltrim($bits, '0');
    }
    function toString()
    {
        switch ( MATH_BIGINTEGER_MODE ) {
            case MATH_BIGINTEGER_MODE_GMP:
                return gmp_strval($this->value);
            case MATH_BIGINTEGER_MODE_BCMATH:
                if ($this->value === '0') {
                    return '0';
                }

                return ltrim($this->value, '0');
        }

        if (!count($this->value)) {
            return '0';
        }

        $temp = $this->copy();
        $temp->is_negative = false;

        $divisor = new Math_BigInteger();
        $divisor->value = array(10000000); // eg. 10**7
        $result = '';
        while (count($temp->value)) {
            list($temp, $mod) = $temp->divide($divisor);
            $result = str_pad($mod->value[0], 7, '0', STR_PAD_LEFT) . $result;
        }
        $result = ltrim($result, '0');

        if ($this->is_negative) {
            $result = '-' . $result;
        }

        return $result;
    }
    function copy()
    {
        $temp = new Math_BigInteger();
        $temp->value = $this->value;
        $temp->is_negative = $this->is_negative;
        $temp->generator = $this->generator;
        $temp->precision = $this->precision;
        $temp->bitmask = $this->bitmask;
        return $temp;
    }
    function __toString()
    {
        return $this->toString();
    }
    function __clone()
    {
        return $this->copy();
    }
    function add($y)
    {
        switch ( MATH_BIGINTEGER_MODE ) {
            case MATH_BIGINTEGER_MODE_GMP:
                $temp = new Math_BigInteger();
                $temp->value = gmp_add($this->value, $y->value);

                return $this->_normalize($temp);
            case MATH_BIGINTEGER_MODE_BCMATH:
                $temp = new Math_BigInteger();
                $temp->value = bcadd($this->value, $y->value);

                return $this->_normalize($temp);
        }

        $this_size = count($this->value);
        $y_size = count($y->value);

        if ($this_size == 0) {
            return $y->copy();
        } else if ($y_size == 0) {
            return $this->copy();
        }

        // subtract, if appropriate
        if ( $this->is_negative != $y->is_negative ) {
            // is $y the negative number?
            $y_negative = $this->compare($y) > 0;

            $temp = $this->copy();
            $y = $y->copy();
            $temp->is_negative = $y->is_negative = false;

            $diff = $temp->compare($y);
            if ( !$diff ) {
                $temp = new Math_BigInteger();
                return $this->_normalize($temp);
            }

            $temp = $temp->subtract($y);

            $temp->is_negative = ($diff > 0) ? !$y_negative : $y_negative;

            return $this->_normalize($temp);
        }

        $result = new Math_BigInteger();
        $carry = 0;

        $size = max($this_size, $y_size);
        $size+= $size & 1; // rounds $size to the nearest 2.

        $x = array_pad($this->value, $size, 0);
        $y = array_pad($y->value, $size, 0);

        for ($i = 0; $i < $size - 1; $i+=2) {
            $sum = $x[$i + 1] * 0x4000000 + $x[$i] + $y[$i + 1] * 0x4000000 + $y[$i] + $carry;
            $carry = $sum >= MATH_BIGINTEGER_MAX_DIGIT52; // eg. floor($sum / 2**52); only possible values (in any base) are 0 and 1
            $sum = $carry ? $sum - MATH_BIGINTEGER_MAX_DIGIT52 : $sum;

            $temp = floor($sum / 0x4000000);

            $result->value[] = $sum - 0x4000000 * $temp; // eg. a faster alternative to fmod($sum, 0x4000000)
            $result->value[] = $temp;
        }

        if ($carry) {
            $result->value[] = (int) $carry;
        }

        $result->is_negative = $this->is_negative;

        return $this->_normalize($result);
    }
    function subtract($y)
    {
        switch ( MATH_BIGINTEGER_MODE ) {
            case MATH_BIGINTEGER_MODE_GMP:
                $temp = new Math_BigInteger();
                $temp->value = gmp_sub($this->value, $y->value);

                return $this->_normalize($temp);
            case MATH_BIGINTEGER_MODE_BCMATH:
                $temp = new Math_BigInteger();
                $temp->value = bcsub($this->value, $y->value);

                return $this->_normalize($temp);
        }

        $this_size = count($this->value);
        $y_size = count($y->value);

        if ($this_size == 0) {
            $temp = $y->copy();
            $temp->is_negative = !$temp->is_negative;
            return $temp;
        } else if ($y_size == 0) {
            return $this->copy();
        }

        // add, if appropriate (ie. -$x - +$y or +$x - -$y)
        if ( $this->is_negative != $y->is_negative ) {
            $is_negative = $y->compare($this) > 0;

            $temp = $this->copy();
            $y = $y->copy();
            $temp->is_negative = $y->is_negative = false;

            $temp = $temp->add($y);

            $temp->is_negative = $is_negative;

            return $this->_normalize($temp);
        }

        $diff = $this->compare($y);

        if ( !$diff ) {
            $temp = new Math_BigInteger();
            return $this->_normalize($temp);
        }

        // switch $this and $y around, if appropriate.
        if ( (!$this->is_negative && $diff < 0) || ($this->is_negative && $diff > 0) ) {
            $is_negative = $y->is_negative;

            $temp = $this->copy();
            $y = $y->copy();
            $temp->is_negative = $y->is_negative = false;

            $temp = $y->subtract($temp);
            $temp->is_negative = !$is_negative;

            return $this->_normalize($temp);
        }

        $result = new Math_BigInteger();
        $carry = 0;

        $size = max($this_size, $y_size);
        $size+= $size % 2;

        $x = array_pad($this->value, $size, 0);
        $y = array_pad($y->value, $size, 0);

        for ($i = 0; $i < $size - 1; $i+=2) {
            $sum = $x[$i + 1] * 0x4000000 + $x[$i] - $y[$i + 1] * 0x4000000 - $y[$i] + $carry;
            $carry = $sum < 0 ? -1 : 0; // eg. floor($sum / 2**52); only possible values (in any base) are 0 and 1
            $sum = $carry ? $sum + MATH_BIGINTEGER_MAX_DIGIT52 : $sum;

            $temp = floor($sum / 0x4000000);

            $result->value[] = $sum - 0x4000000 * $temp;
            $result->value[] = $temp;
        }

        // $carry shouldn't be anything other than zero, at this point, since we already made sure that $this
        // was bigger than $y.

        $result->is_negative = $this->is_negative;

        return $this->_normalize($result);
    }
    function multiply($x)
    {
        switch ( MATH_BIGINTEGER_MODE ) {
            case MATH_BIGINTEGER_MODE_GMP:
                $temp = new Math_BigInteger();
                $temp->value = gmp_mul($this->value, $x->value);

                return $this->_normalize($temp);
            case MATH_BIGINTEGER_MODE_BCMATH:
                $temp = new Math_BigInteger();
                $temp->value = bcmul($this->value, $x->value);

                return $this->_normalize($temp);
        }

        static $cutoff = false;
        if ($cutoff === false) {
            $cutoff = 2 * MATH_BIGINTEGER_KARATSUBA_CUTOFF;
        }

        if ( $this->equals($x) ) {
            return $this->_square();
        }

        $this_length = count($this->value);
        $x_length = count($x->value);

        if ( !$this_length || !$x_length ) { // a 0 is being multiplied
            $temp = new Math_BigInteger();
            return $this->_normalize($temp);
        }

        $product = min($this_length, $x_length) < $cutoff ? $this->_multiply($x) : $this->_karatsuba($x);

        $product->is_negative = $this->is_negative != $x->is_negative;

        return $this->_normalize($product);
    }
    function _multiplyLower($x, $stop)
    {
        $this_length = count($this->value);
        $x_length = count($x->value);

        if ( !$this_length || !$x_length ) { // a 0 is being multiplied
            return new Math_BigInteger();
        }

        if ( $this_length < $x_length ) {
            return $x->_multiplyLower($this, $stop);
        }

        $product = new Math_BigInteger();
        $product->value = $this->_array_repeat(0, $this_length + $x_length);

        // the following for loop could be removed if the for loop following it
        // (the one with nested for loops) initially set $i to 0, but
        // doing so would also make the result in one set of unnecessary adds,
        // since on the outermost loops first pass, $product->value[$k] is going
        // to always be 0

        $carry = 0;

        for ($j = 0; $j < $this_length; $j++) { // ie. $i = 0, $k = $i
            $temp = $this->value[$j] * $x->value[0] + $carry; // $product->value[$k] == 0
            $carry = floor($temp / 0x4000000);
            $product->value[$j] = $temp - 0x4000000 * $carry;
        }

        if ($j < $stop) {
            $product->value[$j] = $carry;
        }

        // the above for loop is what the previous comment was talking about.  the
        // following for loop is the "one with nested for loops"

        for ($i = 1; $i < $x_length; $i++) {
            $carry = 0;

            for ($j = 0, $k = $i; $j < $this_length && $k < $stop; $j++, $k++) {
                $temp = $product->value[$k] + $this->value[$j] * $x->value[$i] + $carry;
                $carry = floor($temp / 0x4000000);
                $product->value[$k] = $temp - 0x4000000 * $carry;
            }

            if ($k < $stop) {
                $product->value[$k] = $carry;
            }
        }

        $product->is_negative = $this->is_negative != $x->is_negative;

        return $product;
    }
    function _multiply($x)
    {
        $this_length = count($this->value);
        $x_length = count($x->value);

        if ( !$this_length || !$x_length ) { // a 0 is being multiplied
            return new Math_BigInteger();
        }

        if ( $this_length < $x_length ) {
            return $x->_multiply($this);
        }

        $product = new Math_BigInteger();
        $product->value = $this->_array_repeat(0, $this_length + $x_length);

        // the following for loop could be removed if the for loop following it
        // (the one with nested for loops) initially set $i to 0, but
        // doing so would also make the result in one set of unnecessary adds,
        // since on the outermost loops first pass, $product->value[$k] is going
        // to always be 0

        $carry = 0;

        for ($j = 0; $j < $this_length; $j++) { // ie. $i = 0
            $temp = $this->value[$j] * $x->value[0] + $carry; // $product->value[$k] == 0
            $carry = floor($temp / 0x4000000);
            $product->value[$j] = $temp - 0x4000000 * $carry;
        }

        $product->value[$j] = $carry;

        // the above for loop is what the previous comment was talking about.  the
        // following for loop is the "one with nested for loops"
        for ($i = 1; $i < $x_length; $i++) {
            $carry = 0;

            for ($j = 0, $k = $i; $j < $this_length; $j++, $k++) {
                $temp = $product->value[$k] + $this->value[$j] * $x->value[$i] + $carry;
                $carry = floor($temp / 0x4000000);
                $product->value[$k] = $temp - 0x4000000 * $carry;
            }

            $product->value[$k] = $carry;
        }

        $product->is_negative = $this->is_negative != $x->is_negative;

        return $this->_normalize($product);
    }
    function _karatsuba($y)
    {
        $x = $this->copy();

        $m = min(count($x->value) >> 1, count($y->value) >> 1);

        if ($m < MATH_BIGINTEGER_KARATSUBA_CUTOFF) {
            return $x->_multiply($y);
        }

        $x1 = new Math_BigInteger();
        $x0 = new Math_BigInteger();
        $y1 = new Math_BigInteger();
        $y0 = new Math_BigInteger();

        $x1->value = array_slice($x->value, $m);
        $x0->value = array_slice($x->value, 0, $m);
        $y1->value = array_slice($y->value, $m);
        $y0->value = array_slice($y->value, 0, $m);

        $z2 = $x1->_karatsuba($y1);
        $z0 = $x0->_karatsuba($y0);

        $z1 = $x1->add($x0);
        $z1 = $z1->_karatsuba($y1->add($y0));
        $z1 = $z1->subtract($z2->add($z0));

        $z2->value = array_merge(array_fill(0, 2 * $m, 0), $z2->value);
        $z1->value = array_merge(array_fill(0,     $m, 0), $z1->value);

        $xy = $z2->add($z1);
        $xy = $xy->add($z0);

        return $xy;
    }
    function _square()
    {
        static $cutoff = false;
        if ($cutoff === false) {
            $cutoff = 2 * MATH_BIGINTEGER_KARATSUBA_CUTOFF;
        }

        return count($this->value) < $cutoff ? $this->_baseSquare() : $this->_karatsubaSquare();
    }
    function _baseSquare()
    {
        if ( empty($this->value) ) {
            return new Math_BigInteger();
        }

        $square = new Math_BigInteger();
        $square->value = $this->_array_repeat(0, 2 * count($this->value));

        for ($i = 0, $max_index = count($this->value) - 1; $i <= $max_index; $i++) {
            $i2 = 2 * $i;

            $temp = $square->value[$i2] + $this->value[$i] * $this->value[$i];
            $carry = floor($temp / 0x4000000);
            $square->value[$i2] = $temp - 0x4000000 * $carry;

            // note how we start from $i+1 instead of 0 as we do in multiplication.
            for ($j = $i + 1, $k = $i2 + 1; $j <= $max_index; $j++, $k++) {
                $temp = $square->value[$k] + 2 * $this->value[$j] * $this->value[$i] + $carry;
                $carry = floor($temp / 0x4000000);
                $square->value[$k] = $temp - 0x4000000 * $carry;
            }

            // the following line can yield values larger 2**15.  at this point, PHP should switch
            // over to floats.
            $square->value[$i + $max_index + 1] = $carry;
        }

        return $square;
    }
    function _karatsubaSquare()
    {
        $m = count($this->value) >> 1;

        if ($m < MATH_BIGINTEGER_KARATSUBA_CUTOFF) {
            return $this->_square();
        }

        $x1 = new Math_BigInteger();
        $x0 = new Math_BigInteger();

        $x1->value = array_slice($this->value, $m);
        $x0->value = array_slice($this->value, 0, $m);

        $z2 = $x1->_karatsubaSquare();
        $z0 = $x0->_karatsubaSquare();

        $z1 = $x1->add($x0);
        $z1 = $z1->_karatsubaSquare();
        $z1 = $z1->subtract($z2->add($z0));

        $z2->value = array_merge(array_fill(0, 2 * $m, 0), $z2->value);
        $z1->value = array_merge(array_fill(0,     $m, 0), $z1->value);

        $xx = $z2->add($z1);
        $xx = $xx->add($z0);

        return $xx;
    }
    function divide($y)
    {
        switch ( MATH_BIGINTEGER_MODE ) {
            case MATH_BIGINTEGER_MODE_GMP:
                $quotient = new Math_BigInteger();
                $remainder = new Math_BigInteger();

                list($quotient->value, $remainder->value) = gmp_div_qr($this->value, $y->value);

                if (gmp_sign($remainder->value) < 0) {
                    $remainder->value = gmp_add($remainder->value, gmp_abs($y->value));
                }

                return array($this->_normalize($quotient), $this->_normalize($remainder));
            case MATH_BIGINTEGER_MODE_BCMATH:
                $quotient = new Math_BigInteger();
                $remainder = new Math_BigInteger();

                $quotient->value = bcdiv($this->value, $y->value);
                $remainder->value = bcmod($this->value, $y->value);

                if ($remainder->value[0] == '-') {
                    $remainder->value = bcadd($remainder->value, $y->value[0] == '-' ? substr($y->value, 1) : $y->value);
                }

                return array($this->_normalize($quotient), $this->_normalize($remainder));
        }

        if (count($y->value) == 1) {
            $temp = $this->_divide_digit($y->value[0]);
            $temp[0]->is_negative = $this->is_negative != $y->is_negative;
            return array($this->_normalize($temp[0]), $this->_normalize($temp[1]));
        }

        static $zero;
        if (!isset($zero)) {
            $zero = new Math_BigInteger();
        }

        $x = $this->copy();
        $y = $y->copy();

        $x_sign = $x->is_negative;
        $y_sign = $y->is_negative;

        $x->is_negative = $y->is_negative = false;

        $diff = $x->compare($y);

        if ( !$diff ) {
            $temp = new Math_BigInteger();
            $temp->value = array(1);
            $temp->is_negative = $x_sign != $y_sign;
            return array($this->_normalize($temp), $this->_normalize(new Math_BigInteger()));
        }

        if ( $diff < 0 ) {
            // if $x is negative, "add" $y.
            if ( $x_sign ) {
                $x = $y->subtract($x);
            }
            return array($this->_normalize(new Math_BigInteger()), $this->_normalize($x));
        }

        // normalize $x and $y as described in HAC 14.23 / 14.24
        $msb = $y->value[count($y->value) - 1];
        for ($shift = 0; !($msb & 0x2000000); $shift++) {
            $msb <<= 1;
        }
        $x->_lshift($shift);
        $y->_lshift($shift);

        $x_max = count($x->value) - 1;
        $y_max = count($y->value) - 1;

        $quotient = new Math_BigInteger();
        $quotient->value = $this->_array_repeat(0, $x_max - $y_max + 1);

        // $temp = $y << ($x_max - $y_max-1) in base 2**26
        $temp = new Math_BigInteger();
        $temp->value = array_merge($this->_array_repeat(0, $x_max - $y_max), $y->value);

        while ( $x->compare($temp) >= 0 ) {
            // calculate the "common residue"
            $quotient->value[$x_max - $y_max]++;
            $x = $x->subtract($temp);
            $x_max = count($x->value) - 1;
        }

        for ($i = $x_max; $i >= $y_max + 1; $i--) {
            $x_value = array(
                $x->value[$i],
                ( $i > 0 ) ? $x->value[$i - 1] : 0,
                ( $i > 1 ) ? $x->value[$i - 2] : 0
            );
            $y_value = array(
                $y->value[$y_max],
                ( $y_max > 0 ) ? $y->value[$y_max - 1] : 0
            );

            $q_index = $i - $y_max - 1;
            if ($x_value[0] == $y_value[0]) {
                $quotient->value[$q_index] = 0x3FFFFFF;
            } else {
                $quotient->value[$q_index] = floor(
                    ($x_value[0] * 0x4000000 + $x_value[1])
                    /
                    $y_value[0]
                );
            }

            $temp = new Math_BigInteger();
            $temp->value = array($y_value[1], $y_value[0]);

            $lhs = new Math_BigInteger();
            $lhs->value = array($quotient->value[$q_index]);
            $lhs = $lhs->multiply($temp);

            $rhs = new Math_BigInteger();
            $rhs->value = array($x_value[2], $x_value[1], $x_value[0]);

            while ( $lhs->compare($rhs) > 0 ) {
                $quotient->value[$q_index]--;

                $lhs = new Math_BigInteger();
                $lhs->value = array($quotient->value[$q_index]);
                $lhs = $lhs->multiply($temp);
            }

            $adjust = $this->_array_repeat(0, $q_index);
            $temp = new Math_BigInteger();
            $temp->value = array($quotient->value[$q_index]);
            $temp = $temp->multiply($y);
            $temp->value = array_merge($adjust, $temp->value);

            $x = $x->subtract($temp);

            if ($x->compare($zero) < 0) {
                $temp->value = array_merge($adjust, $y->value);
                $x = $x->add($temp);

                $quotient->value[$q_index]--;
            }

            $x_max = count($x->value) - 1;
        }

        // unnormalize the remainder
        $x->_rshift($shift);

        $quotient->is_negative = $x_sign != $y_sign;

        // calculate the "common residue", if appropriate
        if ( $x_sign ) {
            $y->_rshift($shift);
            $x = $y->subtract($x);
        }

        return array($this->_normalize($quotient), $this->_normalize($x));
    }
    function _divide_digit($divisor)
    {
        $carry = 0;
        $result = new Math_BigInteger();

        for ($i = count($this->value) - 1; $i >= 0; $i--) {
            $temp = 0x4000000 * $carry + $this->value[$i];
            $result->value[$i] = floor($temp / $divisor);
            $carry = fmod($temp, $divisor);
        }

        $remainder = new Math_BigInteger();
        $remainder->value = array($carry);

        return array($result, $remainder);
    }
    function modPow($e, $n)
    {
        $n = $this->bitmask !== false && $this->bitmask->compare($n) < 0 ? $this->bitmask : $n->abs();

        if ($e->compare(new Math_BigInteger()) < 0) {
            $e = $e->abs();

            $temp = $this->modInverse($n);
            if ($temp === false) {
                return false;
            }

            return $this->_normalize($temp->modPow($e, $n));
        }

        switch ( MATH_BIGINTEGER_MODE ) {
            case MATH_BIGINTEGER_MODE_GMP:
                $temp = new Math_BigInteger();
                $temp->value = gmp_powm($this->value, $e->value, $n->value);

                return $this->_normalize($temp);
            case MATH_BIGINTEGER_MODE_BCMATH:
                $temp = new Math_BigInteger();
                $temp->value = bcpowmod($this->value, $e->value, $n->value);

                return $this->_normalize($temp);
        }

        if ( empty($e->value) ) {
            $temp = new Math_BigInteger();
            $temp->value = array(1);
            return $this->_normalize($temp);
        }

        if ( $e->value == array(1) ) {
            list(, $temp) = $this->divide($n);
            return $this->_normalize($temp);
        }

        if ( $e->value == array(2) ) {
            $temp = $this->_square();
            list(, $temp) = $temp->divide($n);
            return $this->_normalize($temp);
        }

        return $this->_normalize($this->_slidingWindow($e, $n, MATH_BIGINTEGER_BARRETT));

        // is the modulo odd?
        if ( $n->value[0] & 1 ) {
            return $this->_normalize($this->_slidingWindow($e, $n, MATH_BIGINTEGER_MONTGOMERY));
        }
        // if it's not, it's even

        // find the lowest set bit (eg. the max pow of 2 that divides $n)
        for ($i = 0; $i < count($n->value); $i++) {
            if ( $n->value[$i] ) {
                $temp = decbin($n->value[$i]);
                $j = strlen($temp) - strrpos($temp, '1') - 1;
                $j+= 26 * $i;
                break;
            }
        }
        // at this point, 2^$j * $n/(2^$j) == $n

        $mod1 = $n->copy();
        $mod1->_rshift($j);
        $mod2 = new Math_BigInteger();
        $mod2->value = array(1);
        $mod2->_lshift($j);

        $part1 = ( $mod1->value != array(1) ) ? $this->_slidingWindow($e, $mod1, MATH_BIGINTEGER_MONTGOMERY) : new Math_BigInteger();
        $part2 = $this->_slidingWindow($e, $mod2, MATH_BIGINTEGER_POWEROF2);

        $y1 = $mod2->modInverse($mod1);
        $y2 = $mod1->modInverse($mod2);

        $result = $part1->multiply($mod2);
        $result = $result->multiply($y1);

        $temp = $part2->multiply($mod1);
        $temp = $temp->multiply($y2);

        $result = $result->add($temp);
        list(, $result) = $result->divide($n);

        return $this->_normalize($result);
    }
    function powMod($e, $n)
    {
        return $this->modPow($e, $n);
    }
    function _slidingWindow($e, $n, $mode)
    {
        static $window_ranges = array(7, 25, 81, 241, 673, 1793); // from BigInteger.java's oddModPow function
        //static $window_ranges = array(0, 7, 36, 140, 450, 1303, 3529); // from MPM 7.3.1

        $e_length = count($e->value) - 1;
        $e_bits = decbin($e->value[$e_length]);
        for ($i = $e_length - 1; $i >= 0; $i--) {
            $e_bits.= str_pad(decbin($e->value[$i]), 26, '0', STR_PAD_LEFT);
        }

        $e_length = strlen($e_bits);

        // calculate the appropriate window size.
        // $window_size == 3 if $window_ranges is between 25 and 81, for example.
        for ($i = 0, $window_size = 1; $e_length > $window_ranges[$i] && $i < count($window_ranges); $window_size++, $i++);
        switch ($mode) {
            case MATH_BIGINTEGER_MONTGOMERY:
                $reduce = '_montgomery';
                $prep = '_prepMontgomery';
                break;
            case MATH_BIGINTEGER_BARRETT:
                $reduce = '_barrett';
                $prep = '_barrett';
                break;
            case MATH_BIGINTEGER_POWEROF2:
                $reduce = '_mod2';
                $prep = '_mod2';
                break;
            case MATH_BIGINTEGER_CLASSIC:
                $reduce = '_remainder';
                $prep = '_remainder';
                break;
            case MATH_BIGINTEGER_NONE:
                // ie. do no modular reduction.  useful if you want to just do pow as opposed to modPow.
                $reduce = 'copy';
                $prep = 'copy';
                break;
            default:
                // an invalid $mode was provided
        }

        // precompute $this^0 through $this^$window_size
        $powers = array();
        $powers[1] = $this->$prep($n);
        $powers[2] = $powers[1]->_square();
        $powers[2] = $powers[2]->$reduce($n);

        // we do every other number since substr($e_bits, $i, $j+1) (see below) is supposed to end
        // in a 1.  ie. it's supposed to be odd.
        $temp = 1 << ($window_size - 1);
        for ($i = 1; $i < $temp; $i++) {
            $powers[2 * $i + 1] = $powers[2 * $i - 1]->multiply($powers[2]);
            $powers[2 * $i + 1] = $powers[2 * $i + 1]->$reduce($n);
        }

        $result = new Math_BigInteger();
        $result->value = array(1);
        $result = $result->$prep($n);

        for ($i = 0; $i < $e_length; ) {
            if ( !$e_bits[$i] ) {
                $result = $result->_square();
                $result = $result->$reduce($n);
                $i++;
            } else {
                for ($j = $window_size - 1; $j > 0; $j--) {
                    if ( !empty($e_bits[$i + $j]) ) {
                        break;
                    }
                }

                for ($k = 0; $k <= $j; $k++) {// eg. the length of substr($e_bits, $i, $j+1)
                    $result = $result->_square();
                    $result = $result->$reduce($n);
                }

                $result = $result->multiply($powers[bindec(substr($e_bits, $i, $j + 1))]);
                $result = $result->$reduce($n);

                $i+=$j + 1;
            }
        }

        $result = $result->$reduce($n);

        return $result;
    }
    function _remainder($n)
    {
        list(, $temp) = $this->divide($n);
        return $temp;
    }
    function _mod2($n)
    {
        $temp = new Math_BigInteger();
        $temp->value = array(1);
        return $this->bitwise_and($n->subtract($temp));
    }
    function _barrett($n)
    {
        static $cache = array(
            MATH_BIGINTEGER_VARIABLE => array(),
            MATH_BIGINTEGER_DATA => array()
        );

        $n_length = count($n->value);

        if (count($this->value) > 2 * $n_length) {
            list(, $temp) = $this->divide($n);
            return $temp;
        }

        if ( ($key = array_search($n->value, $cache[MATH_BIGINTEGER_VARIABLE])) === false ) {
            $key = count($cache[MATH_BIGINTEGER_VARIABLE]);
            $cache[MATH_BIGINTEGER_VARIABLE][] = $n->value;
            $temp = new Math_BigInteger();
            $temp->value = $this->_array_repeat(0, 2 * $n_length);
            $temp->value[] = 1;
            list($cache[MATH_BIGINTEGER_DATA][], ) = $temp->divide($n);
        }

        $temp = new Math_BigInteger();
        $temp->value = array_slice($this->value, $n_length - 1);
        $temp = $temp->multiply($cache[MATH_BIGINTEGER_DATA][$key]);
        $temp->value = array_slice($temp->value, $n_length + 1);

        $result = new Math_BigInteger();
        $result->value = array_slice($this->value, 0, $n_length + 1);
        $temp = $temp->_multiplyLower($n, $n_length + 1);
        // $temp->value == array_slice($temp->multiply($n)->value, 0, $n_length + 1)

        if ($result->compare($temp) < 0) {
            $corrector = new Math_BigInteger();
            $corrector->value = $this->_array_repeat(0, $n_length + 1);
            $corrector->value[] = 1;
            $result = $result->add($corrector);
        }

        $result = $result->subtract($temp);
        while ($result->compare($n) > 0) {
            $result = $result->subtract($n);
        }

        return $result;
    }
    function _montgomery($n)
    {
        static $cache = array(
            MATH_BIGINTEGER_VARIABLE => array(),
            MATH_BIGINTEGER_DATA => array()
        );

        if ( ($key = array_search($n->value, $cache[MATH_BIGINTEGER_VARIABLE])) === false ) {
            $key = count($cache[MATH_BIGINTEGER_VARIABLE]);
            $cache[MATH_BIGINTEGER_VARIABLE][] = $n->value;
            $cache[MATH_BIGINTEGER_DATA][] = $n->_modInverse67108864();
        }

        $k = count($n->value);

        $result = $this->copy();

        for ($i = 0; $i < $k; $i++) {
            $temp = new Math_BigInteger();
            $temp->value = array(
                ($result->value[$i] * $cache[MATH_BIGINTEGER_DATA][$key]) & 0x3FFFFFF
            );

            $temp = $temp->multiply($n);
            $temp->value = array_merge($this->_array_repeat(0, $i), $temp->value);
            $result = $result->add($temp);
        }

        $result->value = array_slice($result->value, $k);

        if ($result->compare($n) >= 0) {
            $result = $result->subtract($n);
        }

        return $result;
    }
    function _prepMontgomery($n)
    {
        $k = count($n->value);

        $temp = new Math_BigInteger();
        $temp->value = array_merge($this->_array_repeat(0, $k), $this->value);

        list(, $temp) = $temp->divide($n);
        return $temp;
    }
    function _modInverse67108864() // 2**26 == 67108864
    {
        $x = -$this->value[0];
        $result = $x & 0x3; // x**-1 mod 2**2
        $result = ($result * (2 - $x * $result)) & 0xF; // x**-1 mod 2**4
        $result = ($result * (2 - ($x & 0xFF) * $result))  & 0xFF; // x**-1 mod 2**8
        $result = ($result * ((2 - ($x & 0xFFFF) * $result) & 0xFFFF)) & 0xFFFF; // x**-1 mod 2**16
        $result = fmod($result * (2 - fmod($x * $result, 0x4000000)), 0x4000000); // x**-1 mod 2**26
        return $result & 0x3FFFFFF;
    }
    function modInverse($n)
    {
        switch ( MATH_BIGINTEGER_MODE ) {
            case MATH_BIGINTEGER_MODE_GMP:
                $temp = new Math_BigInteger();
                $temp->value = gmp_invert($this->value, $n->value);

                return ( $temp->value === false ) ? false : $this->_normalize($temp);
        }

        static $zero, $one;
        if (!isset($zero)) {
            $zero = new Math_BigInteger();
            $one = new Math_BigInteger(1);
        }

        // $x mod $n == $x mod -$n.
        $n = $n->abs();

        if ($this->compare($zero) < 0) {
            $temp = $this->abs();
            $temp = $temp->modInverse($n);
            return $negated === false ? false : $this->_normalize($n->subtract($temp));
        }

        extract($this->extendedGCD($n));

        if (!$gcd->equals($one)) {
            return false;
        }

        $x = $x->compare($zero) < 0 ? $x->add($n) : $x;

        return $this->compare($zero) < 0 ? $this->_normalize($n->subtract($x)) : $this->_normalize($x);
    }
    function extendedGCD($n) {
        switch ( MATH_BIGINTEGER_MODE ) {
            case MATH_BIGINTEGER_MODE_GMP:
                extract(gmp_gcdext($this->value, $n->value));

                return array(
                    'gcd' => $this->_normalize(new Math_BigInteger($g)),
                    'x'   => $this->_normalize(new Math_BigInteger($s)),
                    'y'   => $this->_normalize(new Math_BigInteger($t))
                );
            case MATH_BIGINTEGER_MODE_BCMATH:
                // it might be faster to use the binary xGCD algorithim here, as well, but (1) that algorithim works
                // best when the base is a power of 2 and (2) i don't think it'd make much difference, anyway.  as is,
                // the basic extended euclidean algorithim is what we're using.

                $u = $this->value;
                $v = $n->value;

                $a = '1';
                $b = '0';
                $c = '0';
                $d = '1';

                while (bccomp($v, '0') != 0) {
                    $q = bcdiv($u, $v);

                    $temp = $u;
                    $u = $v;
                    $v = bcsub($temp, bcmul($v, $q));

                    $temp = $a;
                    $a = $c;
                    $c = bcsub($temp, bcmul($a, $q));

                    $temp = $b;
                    $b = $d;
                    $d = bcsub($temp, bcmul($b, $q));
                }

                return array(
                    'gcd' => $this->_normalize(new Math_BigInteger($u)),
                    'x'   => $this->_normalize(new Math_BigInteger($a)),
                    'y'   => $this->_normalize(new Math_BigInteger($b))
                );
        }

        $y = $n->copy();
        $x = $this->copy();
        $g = new Math_BigInteger();
        $g->value = array(1);

        while ( !(($x->value[0] & 1)|| ($y->value[0] & 1)) ) {
            $x->_rshift(1);
            $y->_rshift(1);
            $g->_lshift(1);
        }

        $u = $x->copy();
        $v = $y->copy();

        $a = new Math_BigInteger();
        $b = new Math_BigInteger();
        $c = new Math_BigInteger();
        $d = new Math_BigInteger();

        $a->value = $d->value = $g->value = array(1);

        while ( !empty($u->value) ) {
            while ( !($u->value[0] & 1) ) {
                $u->_rshift(1);
                if ( ($a->value[0] & 1) || ($b->value[0] & 1) ) {
                    $a = $a->add($y);
                    $b = $b->subtract($x);
                }
                $a->_rshift(1);
                $b->_rshift(1);
            }

            while ( !($v->value[0] & 1) ) {
                $v->_rshift(1);
                if ( ($c->value[0] & 1) || ($d->value[0] & 1) ) {
                    $c = $c->add($y);
                    $d = $d->subtract($x);
                }
                $c->_rshift(1);
                $d->_rshift(1);
            }

            if ($u->compare($v) >= 0) {
                $u = $u->subtract($v);
                $a = $a->subtract($c);
                $b = $b->subtract($d);
            } else {
                $v = $v->subtract($u);
                $c = $c->subtract($a);
                $d = $d->subtract($b);
            }
        }

        return array(
            'gcd' => $this->_normalize($g->multiply($v)),
            'x'   => $this->_normalize($c),
            'y'   => $this->_normalize($d)
        );
    }
    function gcd($n)
    {
        extract($this->extendedGCD($n));
        return $gcd;
    }
    function abs()
    {
        $temp = new Math_BigInteger();

        switch ( MATH_BIGINTEGER_MODE ) {
            case MATH_BIGINTEGER_MODE_GMP:
                $temp->value = gmp_abs($this->value);
                break;
            case MATH_BIGINTEGER_MODE_BCMATH:
                $temp->value = (bccomp($this->value, '0') < 0) ? substr($this->value, 1) : $this->value;
                break;
            default:
                $temp->value = $this->value;
        }

        return $temp;
    }
    function compare($y)
    {
        switch ( MATH_BIGINTEGER_MODE ) {
            case MATH_BIGINTEGER_MODE_GMP:
                return gmp_cmp($this->value, $y->value);
            case MATH_BIGINTEGER_MODE_BCMATH:
                return bccomp($this->value, $y->value);
        }

        $x = $this->_normalize($this->copy());
        $y = $this->_normalize($y);

        if ( $x->is_negative != $y->is_negative ) {
            return ( !$x->is_negative && $y->is_negative ) ? 1 : -1;
        }

        $result = $x->is_negative ? -1 : 1;

        if ( count($x->value) != count($y->value) ) {
            return ( count($x->value) > count($y->value) ) ? $result : -$result;
        }

        for ($i = count($x->value) - 1; $i >= 0; $i--) {
            if ($x->value[$i] != $y->value[$i]) {
                return ( $x->value[$i] > $y->value[$i] ) ? $result : -$result;
            }
        }

        return 0;
    }
    function equals($x)
    {
        switch ( MATH_BIGINTEGER_MODE ) {
            case MATH_BIGINTEGER_MODE_GMP:
                return gmp_cmp($this->value, $x->value) == 0;
            default:
                return $this->value == $x->value && $this->is_negative == $x->is_negative;
        }
    }         
    function setPrecision($bits)
    {
        $this->precision = $bits;
        if ( MATH_BIGINTEGER_MODE != MATH_BIGINTEGER_MODE_BCMATH ) {
            $this->bitmask = new Math_BigInteger(chr((1 << ($bits & 0x7)) - 1) . str_repeat(chr(0xFF), $bits >> 3), 256);
        } else {
            $this->bitmask = new Math_BigInteger(bcpow('2', $bits));
        }
    }
    function bitwise_and($x)
    {
        switch ( MATH_BIGINTEGER_MODE ) {
            case MATH_BIGINTEGER_MODE_GMP:
                $temp = new Math_BigInteger();
                $temp->value = gmp_and($this->value, $x->value);

                return $this->_normalize($temp);
            case MATH_BIGINTEGER_MODE_BCMATH:
                $left = $this->toBytes();
                $right = $x->toBytes();

                $length = max(strlen($left), strlen($right));

                $left = str_pad($left, $length, chr(0), STR_PAD_LEFT);
                $right = str_pad($right, $length, chr(0), STR_PAD_LEFT);

                return $this->_normalize(new Math_BigInteger($left & $right, 256));
        }

        $result = $this->copy();

        $length = min(count($x->value), count($this->value));

        $result->value = array_slice($result->value, 0, $length);

        for ($i = 0; $i < $length; $i++) {
            $result->value[$i] = $result->value[$i] & $x->value[$i];
        }

        return $this->_normalize($result);
    }
    function bitwise_or($x)
    {
        switch ( MATH_BIGINTEGER_MODE ) {
            case MATH_BIGINTEGER_MODE_GMP:
                $temp = new Math_BigInteger();
                $temp->value = gmp_or($this->value, $x->value);

                return $this->_normalize($temp);
            case MATH_BIGINTEGER_MODE_BCMATH:
                $left = $this->toBytes();
                $right = $x->toBytes();

                $length = max(strlen($left), strlen($right));

                $left = str_pad($left, $length, chr(0), STR_PAD_LEFT);
                $right = str_pad($right, $length, chr(0), STR_PAD_LEFT);

                return $this->_normalize(new Math_BigInteger($left | $right, 256));
        }

        $length = max(count($this->value), count($x->value));
        $result = $this->copy();
        $result->value = array_pad($result->value, 0, $length);
        $x->value = array_pad($x->value, 0, $length);

        for ($i = 0; $i < $length; $i++) {
            $result->value[$i] = $this->value[$i] | $x->value[$i];
        }

        return $this->_normalize($result);
    }
    function bitwise_xor($x)
    {
        switch ( MATH_BIGINTEGER_MODE ) {
            case MATH_BIGINTEGER_MODE_GMP:
                $temp = new Math_BigInteger();
                $temp->value = gmp_xor($this->value, $x->value);

                return $this->_normalize($temp);
            case MATH_BIGINTEGER_MODE_BCMATH:
                $left = $this->toBytes();
                $right = $x->toBytes();

                $length = max(strlen($left), strlen($right));

                $left = str_pad($left, $length, chr(0), STR_PAD_LEFT);
                $right = str_pad($right, $length, chr(0), STR_PAD_LEFT);

                return $this->_normalize(new Math_BigInteger($left ^ $right, 256));
        }

        $length = max(count($this->value), count($x->value));
        $result = $this->copy();
        $result->value = array_pad($result->value, 0, $length);
        $x->value = array_pad($x->value, 0, $length);

        for ($i = 0; $i < $length; $i++) {
            $result->value[$i] = $this->value[$i] ^ $x->value[$i];
        }

        return $this->_normalize($result);
    }
    function bitwise_not()
    {
        // calculuate "not" without regard to $this->precision
        // (will always result in a smaller number.  ie. ~1 isn't 1111 1110 - it's 0)
        $temp = $this->toBytes();
        $pre_msb = decbin(ord($temp[0]));
        $temp = ~$temp;
        $msb = decbin(ord($temp[0]));
        if (strlen($msb) == 8) {
            $msb = substr($msb, strpos($msb, '0'));
        }
        $temp[0] = chr(bindec($msb));

        // see if we need to add extra leading 1's
        $current_bits = strlen($pre_msb) + 8 * strlen($temp) - 8;
        $new_bits = $this->precision - $current_bits;
        if ($new_bits <= 0) {
            return $this->_normalize(new Math_BigInteger($temp, 256));
        }

        // generate as many leading 1's as we need to.
        $leading_ones = chr((1 << ($new_bits & 0x7)) - 1) . str_repeat(chr(0xFF), $new_bits >> 3);
        $this->_base256_lshift($leading_ones, $current_bits);

        $temp = str_pad($temp, ceil($this->bits / 8), chr(0), STR_PAD_LEFT);

        return $this->_normalize(new Math_BigInteger($leading_ones | $temp, 256));
    }
    function bitwise_rightShift($shift)
    {
        $temp = new Math_BigInteger();

        switch ( MATH_BIGINTEGER_MODE ) {
            case MATH_BIGINTEGER_MODE_GMP:
                static $two;

                if (empty($two)) {
                    $two = gmp_init('2');
                }

                $temp->value = gmp_div_q($this->value, gmp_pow($two, $shift));

                break;
            case MATH_BIGINTEGER_MODE_BCMATH:
                $temp->value = bcdiv($this->value, bcpow('2', $shift));

                break;
            default: // could just replace _lshift with this, but then all _lshift() calls would need to be rewritten
                     // and I don't want to do that...
                $temp->value = $this->value;
                $temp->_rshift($shift);
        }

        return $this->_normalize($temp);
    }
    function bitwise_leftShift($shift)
    {
        $temp = new Math_BigInteger();

        switch ( MATH_BIGINTEGER_MODE ) {
            case MATH_BIGINTEGER_MODE_GMP:
                static $two;

                if (empty($two)) {
                    $two = gmp_init('2');
                }

                $temp->value = gmp_mul($this->value, gmp_pow($two, $shift));

                break;
            case MATH_BIGINTEGER_MODE_BCMATH:
                $temp->value = bcmul($this->value, bcpow('2', $shift));

                break;
            default: // could just replace _rshift with this, but then all _lshift() calls would need to be rewritten
                     // and I don't want to do that...
                $temp->value = $this->value;
                $temp->_lshift($shift);
        }

        return $this->_normalize($temp);
    }
    function bitwise_leftRotate($shift)
    {
        $bits = $this->toBytes();

        if ($this->precision > 0) {
            $precision = $this->precision;
            if ( MATH_BIGINTEGER_MODE == MATH_BIGINTEGER_MODE_BCMATH ) {
                $mask = $this->bitmask->subtract(new Math_BigInteger(1));
                $mask = $mask->toBytes();
            } else {
                $mask = $this->bitmask->toBytes();
            }
        } else {
            $temp = ord($bits[0]);
            for ($i = 0; $temp >> $i; $i++);
            $precision = 8 * strlen($bits) - 8 + $i;
            $mask = chr((1 << ($precision & 0x7)) - 1) . str_repeat(chr(0xFF), $precision >> 3);
        }

        if ($shift < 0) {
            $shift+= $precision;
        }
        $shift%= $precision;

        if (!$shift) {
            return $this->copy();
        }

        $left = $this->bitwise_leftShift($shift);
        $left = $left->bitwise_and(new Math_BigInteger($mask, 256));
        $right = $this->bitwise_rightShift($precision - $shift);
        $result = MATH_BIGINTEGER_MODE != MATH_BIGINTEGER_MODE_BCMATH ? $left->bitwise_or($right) : $left->add($right);
        return $this->_normalize($result);
    }
    function bitwise_rightRotate($shift)
    {
        return $this->bitwise_leftRotate(-$shift);
    }
    function setRandomGenerator($generator)
    {
        $this->generator = $generator;
    }
    function random($min = false, $max = false)
    {
        if ($min === false) {
            $min = new Math_BigInteger(0);
        }

        if ($max === false) {
            $max = new Math_BigInteger(0x7FFFFFFF);
        }

        $compare = $max->compare($min);

        if (!$compare) {
            return $this->_normalize($min);
        } else if ($compare < 0) {
            // if $min is bigger then $max, swap $min and $max
            $temp = $max;
            $max = $min;
            $min = $temp;
        }

        $generator = $this->generator;

        $max = $max->subtract($min);
        $max = ltrim($max->toBytes(), chr(0));
        $size = strlen($max) - 1;
        $random = '';

        $bytes = $size & 1;
        for ($i = 0; $i < $bytes; $i++) {
            $random.= chr($generator(0, 255));
        }

        $blocks = $size >> 1;
        for ($i = 0; $i < $blocks; $i++) {
            // mt_rand(-2147483648, 0x7FFFFFFF) always produces -2147483648 on some systems
            $random.= pack('n', $generator(0, 0xFFFF));
        }

        $temp = new Math_BigInteger($random, 256);
        if ($temp->compare(new Math_BigInteger(substr($max, 1), 256)) > 0) {
            $random = chr($generator(0, ord($max[0]) - 1)) . $random;
        } else {
            $random = chr($generator(0, ord($max[0])    )) . $random;
        }

        $random = new Math_BigInteger($random, 256);

        return $this->_normalize($random->add($min));
    }
    function randomPrime($min = false, $max = false, $timeout = false)
    {
        // gmp_nextprime() requires PHP 5 >= 5.2.0 per <http://php.net/gmp-nextprime>.
        if ( MATH_BIGINTEGER_MODE == MATH_BIGINTEGER_MODE_GMP && function_exists('gmp_nextprime') ) {
            // we don't rely on Math_BigInteger::random()'s min / max when gmp_nextprime() is being used since this function
            // does its own checks on $max / $min when gmp_nextprime() is used.  When gmp_nextprime() is not used, however,
            // the same $max / $min checks are not performed.
            if ($min === false) {
                $min = new Math_BigInteger(0);
            }

            if ($max === false) {
                $max = new Math_BigInteger(0x7FFFFFFF);
            }

            $compare = $max->compare($min);

            if (!$compare) {
                return $min;
            } else if ($compare < 0) {
                // if $min is bigger then $max, swap $min and $max
                $temp = $max;
                $max = $min;
                $min = $temp;
            }

            $x = $this->random($min, $max);

            $x->value = gmp_nextprime($x->value);

            if ($x->compare($max) <= 0) {
                return $x;
            }

            $x->value = gmp_nextprime($min->value);

            if ($x->compare($max) <= 0) {
                return $x;
            }

            return false;
        }

        $repeat1 = $repeat2 = array();

        $one = new Math_BigInteger(1);
        $two = new Math_BigInteger(2);

        $start = time();

        do {
            if ($timeout !== false && time() - $start > $timeout) {
                return false;
            }

            $x = $this->random($min, $max);
            if ($x->equals($two)) {
                return $x;
            }

            // make the number odd
            switch ( MATH_BIGINTEGER_MODE ) {
                case MATH_BIGINTEGER_MODE_GMP:
                    gmp_setbit($x->value, 0);
                    break;
                case MATH_BIGINTEGER_MODE_BCMATH:
                    if ($x->value[strlen($x->value) - 1] % 2 == 0) {
                        $x = $x->add($one);
                    }
                    break;
                default:
                    $x->value[0] |= 1;
            }

            // if we've seen this number twice before, assume there are no prime numbers within the given range
            if (in_array($x->value, $repeat1)) {
                if (in_array($x->value, $repeat2)) {
                    return false;
                } else {
                    $repeat2[] = $x->value;
                }
            } else {
                $repeat1[] = $x->value;
            }
        } while (!$x->isPrime());

        return $x;
    }
    function isPrime($t = false)
    {
        $length = strlen($this->toBytes());

        if (!$t) {
            // see HAC 4.49 "Note (controlling the error probability)"
                 if ($length >= 163) { $t =  2; } // floor(1300 / 8)
            else if ($length >= 106) { $t =  3; } // floor( 850 / 8)
            else if ($length >= 81 ) { $t =  4; } // floor( 650 / 8)
            else if ($length >= 68 ) { $t =  5; } // floor( 550 / 8)
            else if ($length >= 56 ) { $t =  6; } // floor( 450 / 8)
            else if ($length >= 50 ) { $t =  7; } // floor( 400 / 8)
            else if ($length >= 43 ) { $t =  8; } // floor( 350 / 8)
            else if ($length >= 37 ) { $t =  9; } // floor( 300 / 8)
            else if ($length >= 31 ) { $t = 12; } // floor( 250 / 8)
            else if ($length >= 25 ) { $t = 15; } // floor( 200 / 8)
            else if ($length >= 18 ) { $t = 18; } // floor( 150 / 8)
            else                     { $t = 27; }
        }

        // ie. gmp_testbit($this, 0)
        // ie. isEven() or !isOdd()
        switch ( MATH_BIGINTEGER_MODE ) {
            case MATH_BIGINTEGER_MODE_GMP:
                return gmp_prob_prime($this->value, $t) != 0;
            case MATH_BIGINTEGER_MODE_BCMATH:
                if ($this->value == '2') {
                    return true;
                }
                if ($this->value[strlen($this->value) - 1] % 2 == 0) {
                    return false;
                }
                break;
            default:
                if ($this->value == array(2)) {
                    return true;
                }
                if (~$this->value[0] & 1) {
                    return false;
                }
        }

        static $primes, $zero, $one, $two;

        if (!isset($primes)) {
            $primes = array(
                3,    5,    7,    11,   13,   17,   19,   23,   29,   31,   37,   41,   43,   47,   53,   59,   
                61,   67,   71,   73,   79,   83,   89,   97,   101,  103,  107,  109,  113,  127,  131,  137,  
                139,  149,  151,  157,  163,  167,  173,  179,  181,  191,  193,  197,  199,  211,  223,  227,  
                229,  233,  239,  241,  251,  257,  263,  269,  271,  277,  281,  283,  293,  307,  311,  313,  
                317,  331,  337,  347,  349,  353,  359,  367,  373,  379,  383,  389,  397,  401,  409,  419,  
                421,  431,  433,  439,  443,  449,  457,  461,  463,  467,  479,  487,  491,  499,  503,  509,  
                521,  523,  541,  547,  557,  563,  569,  571,  577,  587,  593,  599,  601,  607,  613,  617,  
                619,  631,  641,  643,  647,  653,  659,  661,  673,  677,  683,  691,  701,  709,  719,  727,  
                733,  739,  743,  751,  757,  761,  769,  773,  787,  797,  809,  811,  821,  823,  827,  829,  
                839,  853,  857,  859,  863,  877,  881,  883,  887,  907,  911,  919,  929,  937,  941,  947,  
                953,  967,  971,  977,  983,  991,  997
            );

            for ($i = 0; $i < count($primes); $i++) {
                $primes[$i] = new Math_BigInteger($primes[$i]);
            }

            $zero = new Math_BigInteger();
            $one = new Math_BigInteger(1);
            $two = new Math_BigInteger(2);
        }

        // see HAC 4.4.1 "Random search for probable primes"
        for ($i = 0; $i < count($primes); $i++) {
            list(, $r) = $this->divide($primes[$i]);
            if ($r->equals($zero)) {
                return false;
            }
        }

        $n   = $this->copy();
        $n_1 = $n->subtract($one);
        $n_2 = $n->subtract($two);

        $r = $n_1->copy();
        // ie. $s = gmp_scan1($n, 0) and $r = gmp_div_q($n, gmp_pow(gmp_init('2'), $s));
        if ( MATH_BIGINTEGER_MODE == MATH_BIGINTEGER_MODE_BCMATH ) {
            $s = 0;
            while ($r->value[strlen($r->value) - 1] % 2 == 0) {
                $r->value = bcdiv($r->value, 2);
                $s++;
            }
        } else {
            for ($i = 0; $i < count($r->value); $i++) {
                $temp = ~$r->value[$i] & 0xFFFFFF;
                for ($j = 1; ($temp >> $j) & 1; $j++);
                if ($j != 25) {
                    break;
                }
            }
            $s = 26 * $i + $j - 1;
            $r->_rshift($s);
        }

        for ($i = 0; $i < $t; $i++) {
            $a = new Math_BigInteger();
            $a = $a->random($two, $n_2);
            $y = $a->modPow($r, $n);

            if (!$y->equals($one) && !$y->equals($n_1)) {
                for ($j = 1; $j < $s && !$y->equals($n_1); $j++) {
                    $y = $y->modPow($two, $n);
                    if ($y->equals($one)) {
                        return false;
                    }
                }

                if (!$y->equals($n_1)) {
                    return false;
                }
            }
        }
        return true;
    }
    function _lshift($shift)
    {
        if ( $shift == 0 ) {
            return;
        }

        $num_digits = floor($shift / 26);
        $shift %= 26;
        $shift = 1 << $shift;

        $carry = 0;

        for ($i = 0; $i < count($this->value); $i++) {
            $temp = $this->value[$i] * $shift + $carry;
            $carry = floor($temp / 0x4000000);
            $this->value[$i] = $temp - $carry * 0x4000000;
        }

        if ( $carry ) {
            $this->value[] = $carry;
        }

        while ($num_digits--) {
            array_unshift($this->value, 0);
        }
    }
    function _rshift($shift)
    {
        if ($shift == 0) {
            return;
        }

        $num_digits = floor($shift / 26);
        $shift %= 26;
        $carry_shift = 26 - $shift;
        $carry_mask = (1 << $shift) - 1;

        if ( $num_digits ) {
            $this->value = array_slice($this->value, $num_digits);
        }

        $carry = 0;

        for ($i = count($this->value) - 1; $i >= 0; $i--) {
            $temp = $this->value[$i] >> $shift | $carry;
            $carry = ($this->value[$i] & $carry_mask) << $carry_shift;
            $this->value[$i] = $temp;
        }
    }
    function _normalize($result)
    {
        $result->precision = $this->precision;
        $result->bitmask = $this->bitmask;

        switch ( MATH_BIGINTEGER_MODE ) {
            case MATH_BIGINTEGER_MODE_GMP:
                if (!empty($result->bitmask->value)) {
                    $result->value = gmp_and($result->value, $result->bitmask->value);
                }

                return $result;
            case MATH_BIGINTEGER_MODE_BCMATH:
                if (!empty($result->bitmask->value)) {
                    $result->value = bcmod($result->value, $result->bitmask->value);
                }

                return $result;
        }

        if ( !count($result->value) ) {
            return $result;
        }

        for ($i = count($result->value) - 1; $i >= 0; $i--) {
            if ( $result->value[$i] ) {
                break;
            }
            unset($result->value[$i]);
        }

        if (!empty($result->bitmask->value)) {
            $length = min(count($result->value), count($this->bitmask->value));
            $result->value = array_slice($result->value, 0, $length);

            for ($i = 0; $i < $length; $i++) {
                $result->value[$i] = $result->value[$i] & $this->bitmask->value[$i];
            }
        }

        return $result;
    }
    function _array_repeat($input, $multiplier)
    {
        return ($multiplier) ? array_fill(0, $multiplier, $input) : array();
    }
    function _base256_lshift(&$x, $shift)
    {
        if ($shift == 0) {
            return;
        }

        $num_bytes = $shift >> 3; // eg. floor($shift/8)
        $shift &= 7; // eg. $shift % 8

        $carry = 0;
        for ($i = strlen($x) - 1; $i >= 0; $i--) {
            $temp = ord($x[$i]) << $shift | $carry;
            $x[$i] = chr($temp);
            $carry = $temp >> 8;
        }
        $carry = ($carry != 0) ? chr($carry) : '';
        $x = $carry . $x . str_repeat(chr(0), $num_bytes);
    }
    function _base256_rshift(&$x, $shift)
    {
        if ($shift == 0) {
            $x = ltrim($x, chr(0));
            return '';
        }

        $num_bytes = $shift >> 3; // eg. floor($shift/8)
        $shift &= 7; // eg. $shift % 8

        $remainder = '';
        if ($num_bytes) {
            $start = $num_bytes > strlen($x) ? -strlen($x) : -$num_bytes;
            $remainder = substr($x, $start);
            $x = substr($x, 0, -$num_bytes);
        }

        $carry = 0;
        $carry_shift = 8 - $shift;
        for ($i = 0; $i < strlen($x); $i++) {
            $temp = (ord($x[$i]) >> $shift) | $carry;
            $carry = (ord($x[$i]) << $carry_shift) & 0xFF;
            $x[$i] = chr($temp);
        }
        $x = ltrim($x, chr(0));

        $remainder = chr($carry >> $carry_shift) . $remainder;

        return ltrim($remainder, chr(0));
    }

    // one quirk about how the following functions are implemented is that PHP defines N to be an unsigned long
    // at 32-bits, while java's longs are 64-bits.
    function _int2bytes($x)
    {
        return ltrim(pack('N', $x), chr(0));
    }
    function _bytes2int($x)
    {
        $temp = unpack('Nint', str_pad($x, 4, chr(0), STR_PAD_LEFT));
        return $temp['int'];
    }
}