<?php namespace Morningtrain\WpNetsEasy\Services;

class IntegerService {

    public static function convertToOneHundredthInt(float $number) : int
    {
        return (int) round($number * 100, 0);
    }

    public static function convertFromOneHundredthInt(int $number) : float
    {
        return (float) $number / 100;
    }

}