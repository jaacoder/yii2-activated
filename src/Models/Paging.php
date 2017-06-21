<?php

namespace Jaacoder\Yii2Activated\Models;

/**
 * Class for dealign with paging and sorting.
 */
class Paging
{
    /**
     * @var int
     */
    public $currentPage = 1;
    
    /**
     * @var int
     */
    public $perPage = 10;
    
    /**
     * @var int
     */
    public $lastPage;
    
    /**
     * @var int
     */
    public $total;
    
    /**
     * @var int
     */
    public $from;
    
    /**
     * @var int
     */
    public $to;
    
    /**
     * @var int
     */
    public $currentPageSize;
}
