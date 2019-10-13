<?php

namespace MichaelDrennen\Etrade\Tests;

use MichaelDrennen\Etrade\Etrade;
use PHPUnit\Framework\TestCase;

class AbstractEtradeBaseTestCase extends TestCase {


    protected function getEtradeSandboxedStableInstance(): Etrade {
        return new Etrade( $consumerKey );
    }


}