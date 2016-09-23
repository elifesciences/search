<?php

namespace tests\eLife\Search;

use BadMethodCallException;
use function GuzzleHttp\Promise\all;

/**
 * @method asyncAssertEquals($expected, $actual, $message = "")
 * @method asyncAssertArrayHasKey()
 * @method asyncAssertClassHasAttribute()
 * @method asyncAssertArraySubset()
 * @method asyncAssertClassHasStaticAttribute()
 * @method asyncAssertContains()
 * @method asyncAssertContainsOnly()
 * @method asyncAssertContainsOnlyInstancesOf()
 * @method asyncAssertCount()
 * @method asyncAssertEmpty()
 * @method asyncAssertEqualXMLStructure()
 * @method asyncAssertFalse()
 * @method asyncAssertFileEquals()
 * @method asyncAssertFileExists()
 * @method asyncAssertGreaterThan()
 * @method asyncAssertGreaterThanOrEqual()
 * @method asyncAssertInfinite()
 * @method asyncAssertInstanceOf()
 * @method asyncAssertInternalType()
 * @method asyncAssertJsonFileEqualsJsonFile()
 * @method asyncAssertJsonStringEqualsJsonFile()
 * @method asyncAssertJsonStringEqualsJsonString()
 * @method asyncAssertLessThan()
 * @method asyncAssertLessThanOrEqual()
 * @method asyncAssertNan()
 * @method asyncAssertNull()
 * @method asyncAssertObjectHasAttribute()
 * @method asyncAssertRegExp()
 * @method asyncAssertStringMatchesFormat()
 * @method asyncAssertStringMatchesFormatFile()
 * @method asyncAssertSame()
 * @method asyncAssertStringEndsWith()
 * @method asyncAssertStringEqualsFile()
 * @method asyncAssertStringStartsWith()
 * @method asyncAssertThat()
 * @method asyncAssertTrue()
 * @method asyncAssertXmlFileEqualsXmlFile()
 * @method asyncAssertXmlStringEqualsXmlFile()
 * @method asyncAssertXmlStringEqualsXmlString()
 */
trait AsyncAssert
{
    public function __call($name, $arguments)
    {
        if (strpos($name, 'async') === 0) {
            // Trim off the async.
            $fn = lcfirst(substr($name, 5));
            if (method_exists($this, $fn)) {
                // Wait for arguments.
                all($arguments)->then([$this, $fn]);
            } else {
                throw new BadMethodCallException('Async assert method not found', get_class($this), $fn);
            }
        }
    }
}
