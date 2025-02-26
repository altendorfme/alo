<?php

namespace Pushbase\Utilities;

use Hackzilla\PasswordGenerator\Generator\ComputerPasswordGenerator;
use Exception;

class PasswordService
{
    public static function generateSecurePassword(
        int $length = 16,
        bool $includeUppercase = true,
        bool $includeLowercase = true,
        bool $includeNumbers = true,
        bool $includeSymbols = true
    ): string {
        $generator = new ComputerPasswordGenerator();
        $generator->setLength($length);
        $generator->setUppercase($includeUppercase);
        $generator->setLowercase($includeLowercase);
        $generator->setNumbers($includeNumbers);
        $generator->setSymbols($includeSymbols);

        try {
            return $generator->generatePassword();
        } catch (Exception $e) {
            return bin2hex(random_bytes($length / 2));
        }
    }
}
