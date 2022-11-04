<?php


namespace App\Constants;

/**
 * @method static AuthenticationType FIELDS()
 * @method static AuthenticationType OAUTH()
 * @method static AuthenticationType HYBRID()
 */
class AuthenticationType extends Enum
{
    
    private const FIELDS = 0;
    private const OAUTH = 1;
    private const HYBRID = 2;
    
}