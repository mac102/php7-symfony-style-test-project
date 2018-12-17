<?php
declare(strict_types=1);

namespace App\Service;

class Echoprinter 
{
    public function __construct() {}
        
    public function doSomething(): void
    {
        echo 'Wyświetlam tekst';
    }
}
