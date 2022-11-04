<?php


namespace App\Constants;

/**
 * @method static Dimension MM()
 * @method static Dimension CM()
 * @method static Dimension INCH()
 * @method static Dimension FEET()
 * @method static Dimension METER()
 */
class Dimension extends Enum
{
    
    private const MM = 0;
    private const CM = 1;
    private const INCH = 2;
    private const FEET = 3;
    private const METER = 4;
    
}