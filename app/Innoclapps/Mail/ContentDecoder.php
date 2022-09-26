<?php
/**
 * Concord CRM - https://www.concordcrm.com
 *
 * @version   1.0.7
 *
 * @link      Releases - https://www.concordcrm.com/releases
 * @link      Terms Of Service - https://www.concordcrm.com/terms
 *
 * @copyright Copyright (c) 2022-2022 KONKORD DIGITAL
 */

namespace App\Innoclapps\Mail;

class ContentDecoder
{
    /**
     * Decode the given string
     *
     * @param string $str The string being encoded.
     * @param string|null $contentTransferEncoding The type of Content-Transfer-Encoding that $str is encoded.
     * @param string|null $fromEncoding The type of encoding that $str is encoded.
     * @param string|null $toEncoding The type of encoding that $str is being converted to.
     * @return string
     */
    public static function decode($str, $contentTransferEncoding = null, $fromEncoding = null, $toEncoding = null)
    {
        if (! empty($contentTransferEncoding)) {
            switch (strtolower($contentTransferEncoding)) {
                case 'base64':
                    $str = base64_decode($str);

                    break;
                case 'quoted-printable':
                    $str = quoted_printable_decode($str);

                    break;
            }
        }

        if (! empty($fromEncoding) && ! empty($toEncoding) && strtolower($fromEncoding) !== strtolower($toEncoding)) {
            // Added additional option to avoid `illegal character` iconv decoding error
            $toEncoding .= '//TRANSLIT//IGNORE';

            $str = iconv($fromEncoding, $toEncoding, $str);
        }

        return $str;
    }
}
