<?php
namespace redisent\tests;

require_once __DIR__.'/../simpletest/autorun.php';
require_once __DIR__.'/strings_test.php';
require_once __DIR__.'/pipeline_test.php';

class AllTests extends \TestSuite {
    function __construct() {
        parent::__construct();
        $this->add(new StringsTest());
        $this->add(new PipelineTest());
    }
}
