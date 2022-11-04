<?php


namespace App\Constants;

/**
 * @method static CategoryAttributeType TEXT()
 * @method static CategoryAttributeType RICH_TEXT()
 * @method static CategoryAttributeType OPTION()
 * @method static CategoryAttributeType SINGLE_SELECT()
 * @method static CategoryAttributeType MULTI_SELECT()
 * @method static CategoryAttributeType NUMERIC()
 * @method static CategoryAttributeType DATE()
 * @method static CategoryAttributeType IMAGE()
 * @method static CategoryAttributeType AUTOCOMPLETE()
 * @method static CategoryAttributeType MULTI_ENUM()
 * @method static CategoryAttributeType RADIO()
 * @method static CategoryAttributeType MULTI_TEXT()
 * @method static CategoryAttributeType CHECKBOX_WITH_INPUT()
 * @method static CategoryAttributeType CHECKBOX()
 * @method static CategoryAttributeType DATETIME()
 * @method static CategoryAttributeType SWITCH()
 * @method static CategoryAttributeType SINGLE_SELECT_OR_INPUT()
 */
class CategoryAttributeType extends Enum
{
    private const TEXT = 0;
    private const RICH_TEXT = 1;
    private const OPTION = 2;
    private const SINGLE_SELECT = 3;
    private const MULTI_SELECT = 4;
    private const NUMERIC = 5;
    private const DATE = 6;
    private const IMAGE = 7;
    private const AUTOCOMPLETE = 8;
    private const MULTI_ENUM = 9;
    private const RADIO = 10;
    private const MULTI_TEXT = 11;
    private const CHECKBOX_WITH_INPUT = 12;
    private const CHECKBOX = 13;
    private const DATETIME = 14;
    private const SWITCH = 15;
    private const SINGLE_SELECT_OR_INPUT = 16;
}
