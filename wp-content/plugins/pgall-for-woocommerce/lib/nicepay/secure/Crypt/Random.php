<?php
function crypt_random($min = 0, $max = 0x7FFFFFFF, $randomness_path = '/dev/urandom')
{
    static $seeded = false;

    if (!$seeded) {
        $seeded = true;
        if (file_exists($randomness_path)) {
            $fp = fopen($randomness_path, 'r');
            $temp = unpack('Nint', fread($fp, 4));
            mt_srand($temp['int']);
            fclose($fp);
        } else {
            list($sec, $usec) = explode(' ', microtime());
            mt_srand((float) $sec + ((float) $usec * 100000));
        }
    }

    return mt_rand($min, $max);
}
?>