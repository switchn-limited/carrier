<?php

namespace App\Services;

use App\Enums\NetworkCarriers;
use libphonenumber\NumberParseException;
use libphonenumber\PhoneNumber;
use libphonenumber\PhoneNumberFormat;
use libphonenumber\PhoneNumberToCarrierMapper;
use libphonenumber\PhoneNumberType;
use libphonenumber\PhoneNumberUtil;

class LibPhoneNumberService
{

    /** @var PhoneNumberUtil $phoneNumberUtil */
    private $phoneNumberUtil;

    /** @var PhoneNumberToCarrierMapper $phoneNumberUtil */
    private $carrierMapper;

    public function __construct()
    {
        $this->phoneNumberUtil = PhoneNumberUtil::getInstance();
        $this->carrierMapper = PhoneNumberToCarrierMapper::getInstance();
    }

    public function getPhoneNumberUtil(): PhoneNumberUtil {
        return $this->phoneNumberUtil;
    }

    public function getCarrierMapper(): PhoneNumberToCarrierMapper {
        return $this->carrierMapper;
    }

    public function getExampleMobileNumber(string $regionCode = 'CM'): string {
        $examplePhoneNumber = $this->getPhoneNumberUtil()
            ->getExampleNumberForType($regionCode, PhoneNumberType::MOBILE);

        return $examplePhoneNumber->getCountryCode() . ''. $examplePhoneNumber->getNationalNumber();
    }

    public function getUniqueExampleMobileNumber(string $regionCode = 'CM'): string {
        /**
         * Anonymous function variable assignment
         * @param $length
         * @return string
         *
         * @see https://www.php.net/manual/en/functions.anonymous.php
         */
        $randomNumber = function($length)
        {
            $result = '';

            for($i = 0; $i < $length; $i++) {
                $result .= random_int(0, 9);
            }

            return $result;
        };

        $mobileNumber = $this->getExampleMobileNumber($regionCode);
        /**
         * Try generating a unique number by replacing the last 4 digits of the example phone number with 4 randomly
         * generated digits.
         */
        return substr($mobileNumber, 0, -4) . $randomNumber(4);
    }

    /**
     * Gets the name of the carrier for the given phone number, in the language provided. As per
     * {@link #getNameForValidNumber(PhoneNumber, Locale)} but explicitly checks the validity of
     * the number passed in.
     *
     * @param PhoneNumber|string $number
     * @param string $languageCode Language code for which the description should be written
     * @return string a carrier name for the given phone number, or empty string if the number passed in is
     *     invalid
     * @throws \libphonenumber\NumberParseException
     */
    public function getCarrierForNumber($number, string $languageCode = 'en'): string
    {
        if (is_string($number)) {
            $number = $this->parse($number);
        }

        $carrier = $this->carrierMapper->getNameForNumber($number, $languageCode);

        // Adds detection for unhandled numbers (CAMTEL and Special Services)
        if (empty($carrier) && $this->isCamtelNumber($number)) {

            $carrier = NetworkCarriers::CAMTEL;
            /*// NB: Yoomee is under camtel
            if ($this->isYoomeeNumber($number)) {
                $carrier = NetworkCarriers::YOOMEE;
            } else {
                $carrier = NetworkCarriers::CAMTEL;
            }*/
        }

        return $carrier;
    }

    /**
     * Uses regex to determine if a number belongs to the CAMTEL network.
     * Here's what CAMTEL numbers look like:
     *      222 XX XX XX (fixed line camtel numbers)
     *      233 XX XX XX (fixed line camtel numbers)
     *      242 XX XX XX (CDMA camtel numbers)
     *      243 XX XX XX (CDMA camtel numbers)
     *      620 XX XX XX (mobile camtel numbers)
     *
     * @see https://www.cirt.cm/en/node/17?language=en
     * @see http://www.camtel.cm/assistance-espace-client/
     *
     * @param PhoneNumber $number
     * @return bool
     */
    public function isCamtelNumber(PhoneNumber $number)
    {
        // The regex reads: match all instances that start with 222 or 233 or 242 or 243 or 620, and followed by 6 digits
        if (preg_match("/^(222|233|242|243|620)\d{6}$/", $number->getNationalNumber())) {
            return true;
        }

        return false;
    }

    /**
     * Uses regex to determine if a number belongs to the CAMTEL network.
     * Here's what Yoomee numbers look like:
     *      242 9X XX XX (Yoomee numbers)
     *
     * @see https://www.cirt.cm/en/node/17?language=en
     * @see http://www.camtel.cm/assistance-espace-client/
     *
     * @param PhoneNumber $number
     * @return bool
     */
    public function isYoomeeNumber(PhoneNumber $number)
    {
        // The regex reads: match all instances that start with 2429 and followed by 5 digits
        if (preg_match("/^(2429)\d{5}$/", $number->getNationalNumber())) {
            return true;
        }

        return false;
    }

    /**
     * @param string $number
     * @param string $defaultRegion
     * @return PhoneNumber
     * @throws \libphonenumber\NumberParseException
     */
    public function parse(string $number, $defaultRegion = 'CM'): PhoneNumber {
        return $this->phoneNumberUtil->parse($number, $defaultRegion);
    }

    public function isFixedLineNumber(PhoneNumber $number): bool
    {
        // libphonenumber can detect Cameroon fixed line numbers
        return $this->phoneNumberUtil->getNumberType($number) === PhoneNumberType::FIXED_LINE;
    }

    public function isMobileNumber(PhoneNumber $number): bool
    {
        $result = $this->phoneNumberUtil->getNumberType($number) === PhoneNumberType::MOBILE;

        if ($result === false && $this->isCamtelNumber($number)) {

            $result = $this->isMobileCamtelNumber($number);

        }

        return $result;
    }

    public function isMobileCamtelNumber(PhoneNumber $number)
    {
        // 242, 243, and 620 represent the mobile range of CAMTEL numbers.
        // The regex reads: match all instances that start with 242 or 243 or 620, and followed by 6 digits.
        if (preg_match("/^(242|243|620)\d{6}$/", $number->getNationalNumber())) {
            return true;
        }

        return false;
    }

    /**
     * Formats a phone number in the specified format using default rules. Note that this does not
     * promise to produce a phone number that the user can dial from where they are - although we do
     * format in either 'national' or 'international' format depending on what the client asks for, we
     * do not currently support a more abbreviated format, such as for users in the same "area" who
     * could potentially dial the number without area code. Note that if the phone number has a
     * country calling code of 0 or an otherwise invalid country calling code, we cannot work out
     * which formatting rules to apply so we return the national significant number with no formatting
     * applied.
     *
     * @param PhoneNumber|string $number the phone number to be formatted
     * @param int $numberFormat the PhoneNumberFormat the phone number should be formatted into
     * @return string|null the formatted phone number
     */
    public function format($number, int $numberFormat = PhoneNumberFormat::NATIONAL): ?string
    {
        if (is_string($number)) {
            try {
                $number = $this->parse($number);
            } catch (NumberParseException $e) {
                // If we fail to parse the number return null
                return null;
            }
        }

        return $this->getPhoneNumberUtil()->format($number, $numberFormat);
    }

    /**
     * format() above returns a string but with spaces inside e.g. (6 76 76 92 51) which causes an error
     * at the level of MTN's servers. So am using str_replace to remove those spaces.
     * @param $number
     * @param int $numberFormat
     * @return string|null
     */
    public function formatAndRemoveSpaces($number, int $numberFormat = PhoneNumberFormat::NATIONAL): ?string
    {
        return str_replace(
            ' ',
            '',
            $this->format($number, $numberFormat)
        );
    }
}
