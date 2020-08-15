<?php

namespace WordPress_ACL;

class Persian_ACL
{

    /**
     * Check Persian input
     *
     * @param $input
     * @return bool
     */
    public static function check_persian_input($input)
    {
        if (preg_match("/^[آ ا ب پ ت ث ج چ ح خ د ذ ر ز ژ س ش ص ض ط ظ ع غ ف ق ک گ ل م ن و ه ی]/", $input)) {
            return true;
        } else {
            return false;
        }
    }

    /*
	 * Check Mobile Number
	 */
    public static function validate_mobile($mobile)
    {
        $result = array(
            'success' => true,
            'text' => ''
        );

        //mobile nubmer character
        if (strlen($mobile) !== 11) {
            $result['text'] = 'شماره همراه 11 کاراکتر می باشد';
            $result['success'] = false;
        }

        //mobile start 09
        if (substr($mobile, 0, 2) !== "09") {
            $result['text'] = 'شماره همراه با 09 شروع می شود';
            $result['success'] = false;
        }

        //mobile Numeric
        if (!is_numeric($mobile)) {
            $result['text'] = 'شماره همراه تنها شامل کاراکتر عدد می باشد';
            $result['success'] = false;
        }

        return $result;
    }

    /**
     * Check National Code
     *
     * @param $national_code
     * @return bool
     */
    public static function check_national_code($national_code)
    {
        if (strlen($national_code) == 10) {
            if (
                $national_code == '1111111111' ||
                $national_code == '0000000000' ||
                $national_code == '2222222222' ||
                $national_code == '3333333333' ||
                $national_code == '4444444444' ||
                $national_code == '5555555555' ||
                $national_code == '6666666666' ||
                $national_code == '7777777777' ||
                $national_code == '8888888888' ||
                $national_code == '9999999999' ||
                $national_code == '0123456789'
            ) {
                //echo 'كد ملي صحيح نمي باشد';
                return false;
            }

            $c = substr($national_code, 9, 1);

            $n = substr($national_code, 0, 1) * 10 +
                substr($national_code, 1, 1) * 9 +
                substr($national_code, 2, 1) * 8 +
                substr($national_code, 3, 1) * 7 +
                substr($national_code, 4, 1) * 6 +
                substr($national_code, 5, 1) * 5 +
                substr($national_code, 6, 1) * 4 +
                substr($national_code, 7, 1) * 3 +
                substr($national_code, 8, 1) * 2;
            $r = $n - (int)($n / 11) * 11;
            if (($r == 0 && $r == $c) || ($r == 1 && $c == 1) || ($r > 1 && $c == 11 - $r)) {
                //echo ' کد ملی صحیح است';
                return true;
            } else {
                //echo 'كد ملي صحيح نمي باشد';
                return false;
            }
        } else {
            //echo 'طول کد ملی وارد شده باید 10 کاراکتر باشد';
            return false;
        }
    }


}
