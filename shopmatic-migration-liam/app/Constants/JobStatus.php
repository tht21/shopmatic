<?php


namespace App\Constants;

/**
 * @method static JobStatus PENDING()
 * @method static JobStatus PROCESSING()
 * @method static JobStatus FINISHED()
 * @method static JobStatus FAILED()
 */
class JobStatus extends Enum
{
    
    private const PENDING = 0;
    private const PROCESSING = 1;
    private const FINISHED = 2;
    private const FAILED = 3;
    
}