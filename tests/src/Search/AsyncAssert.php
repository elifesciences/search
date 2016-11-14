<?php

namespace tests\eLife\Search;

use BadMethodCallException;
use Closure;
use Exception;
use function GuzzleHttp\Promise\all;

/**
 * @method asyncAssertArrayHasKey($key, $array, $message = '')
 * @method asyncAssertArraySubset($subset, $array, $strict = false, $message = '')
 * @method asyncAssertArrayNotHasKey($key, $array, $message = '')
 * @method asyncAssertContains($needle, $haystack, $message = '', $ignoreCase = false, $checkForObjectIdentity = true, $checkForNonObjectIdentity = false)
 * @method asyncAssertAttributeContains($needle, $haystackAttributeName, $haystackClassOrObject, $message = '', $ignoreCase = false, $checkForObjectIdentity = true, $checkForNonObjectIdentity = false)
 * @method asyncAssertNotContains($needle, $haystack, $message = '', $ignoreCase = false, $checkForObjectIdentity = true, $checkForNonObjectIdentity = false)
 * @method asyncAssertAttributeNotContains($needle, $haystackAttributeName, $haystackClassOrObject, $message = '', $ignoreCase = false, $checkForObjectIdentity = true, $checkForNonObjectIdentity = false)
 * @method asyncAssertContainsOnly($type, $haystack, $isNativeType = null, $message = '')
 * @method asyncAssertContainsOnlyInstancesOf($classname, $haystack, $message = '')
 * @method asyncAssertAttributeContainsOnly($type, $haystackAttributeName, $haystackClassOrObject, $isNativeType = null, $message = '')
 * @method asyncAssertNotContainsOnly($type, $haystack, $isNativeType = null, $message = '')
 * @method asyncAssertAttributeNotContainsOnly($type, $haystackAttributeName, $haystackClassOrObject, $isNativeType = null, $message = '')
 * @method asyncAssertCount($expectedCount, $haystack, $message = '')
 * @method asyncAssertAttributeCount($expectedCount, $haystackAttributeName, $haystackClassOrObject, $message = '')
 * @method asyncAssertNotCount($expectedCount, $haystack, $message = '')
 * @method asyncAssertAttributeNotCount($expectedCount, $haystackAttributeName, $haystackClassOrObject, $message = '')
 * @method asyncAssertEquals($expected, $actual, $message = '', $delta = 0.0, $maxDepth = 10, $canonicalize = false, $ignoreCase = false)
 * @method asyncAssertAttributeEquals($expected, $actualAttributeName, $actualClassOrObject, $message = '', $delta = 0.0, $maxDepth = 10, $canonicalize = false, $ignoreCase = false)
 * @method asyncAssertNotEquals($expected, $actual, $message = '', $delta = 0.0, $maxDepth = 10, $canonicalize = false, $ignoreCase = false)
 * @method asyncAssertAttributeNotEquals($expected, $actualAttributeName, $actualClassOrObject, $message = '', $delta = 0.0, $maxDepth = 10, $canonicalize = false, $ignoreCase = false)
 * @method asyncAssertEmpty($actual, $message = '')
 * @method asyncAssertAttributeEmpty($haystackAttributeName, $haystackClassOrObject, $message = '')
 * @method asyncAssertNotEmpty($actual, $message = '')
 * @method asyncAssertAttributeNotEmpty($haystackAttributeName, $haystackClassOrObject, $message = '')
 * @method asyncAssertGreaterThan($expected, $actual, $message = '')
 * @method asyncAssertAttributeGreaterThan($expected, $actualAttributeName, $actualClassOrObject, $message = '')
 * @method asyncAssertGreaterThanOrEqual($expected, $actual, $message = '')
 * @method asyncAssertAttributeGreaterThanOrEqual($expected, $actualAttributeName, $actualClassOrObject, $message = '')
 * @method asyncAssertLessThan($expected, $actual, $message = '')
 * @method asyncAssertAttributeLessThan($expected, $actualAttributeName, $actualClassOrObject, $message = '')
 * @method asyncAssertLessThanOrEqual($expected, $actual, $message = '')
 * @method asyncAssertAttributeLessThanOrEqual($expected, $actualAttributeName, $actualClassOrObject, $message = '')
 * @method asyncAssertFileEquals($expected, $actual, $message = '', $canonicalize = false, $ignoreCase = false)
 * @method asyncAssertFileNotEquals($expected, $actual, $message = '', $canonicalize = false, $ignoreCase = false)
 * @method asyncAssertStringEqualsFile($expectedFile, $actualString, $message = '', $canonicalize = false, $ignoreCase = false)
 * @method asyncAssertStringNotEqualsFile($expectedFile, $actualString, $message = '', $canonicalize = false, $ignoreCase = false)
 * @method asyncAssertFileExists($filename, $message = '')
 * @method asyncAssertFileNotExists($filename, $message = '')
 * @method asyncAssertTrue($condition, $message = '')
 * @method asyncAssertNotTrue($condition, $message = '')
 * @method asyncAssertFalse($condition, $message = '')
 * @method asyncAssertNotFalse($condition, $message = '')
 * @method asyncAssertNull($actual, $message = '')
 * @method asyncAssertNotNull($actual, $message = '')
 * @method asyncAssertFinite($actual, $message = '')
 * @method asyncAssertInfinite($actual, $message = '')
 * @method asyncAssertNan($actual, $message = '')
 * @method asyncAssertClassHasAttribute($attributeName, $className, $message = '')
 * @method asyncAssertClassNotHasAttribute($attributeName, $className, $message = '')
 * @method asyncAssertClassHasStaticAttribute($attributeName, $className, $message = '')
 * @method asyncAssertClassNotHasStaticAttribute($attributeName, $className, $message = '')
 * @method asyncAssertObjectHasAttribute($attributeName, $object, $message = '')
 * @method asyncAssertObjectNotHasAttribute($attributeName, $object, $message = '')
 * @method asyncAssertSame($expected, $actual, $message = '')
 * @method asyncAssertAttributeSame($expected, $actualAttributeName, $actualClassOrObject, $message = '')
 * @method asyncAssertNotSame($expected, $actual, $message = '')
 * @method asyncAssertAttributeNotSame($expected, $actualAttributeName, $actualClassOrObject, $message = '')
 * @method asyncAssertInstanceOf($expected, $actual, $message = '')
 * @method asyncAssertAttributeInstanceOf($expected, $attributeName, $classOrObject, $message = '')
 * @method asyncAssertNotInstanceOf($expected, $actual, $message = '')
 * @method asyncAssertAttributeNotInstanceOf($expected, $attributeName, $classOrObject, $message = '')
 * @method asyncAssertInternalType($expected, $actual, $message = '')
 * @method asyncAssertAttributeInternalType($expected, $attributeName, $classOrObject, $message = '')
 * @method asyncAssertNotInternalType($expected, $actual, $message = '')
 * @method asyncAssertAttributeNotInternalType($expected, $attributeName, $classOrObject, $message = '')
 * @method asyncAssertRegExp($pattern, $string, $message = '')
 * @method asyncAssertNotRegExp($pattern, $string, $message = '')
 * @method asyncAssertSameSize($expected, $actual, $message = '')
 * @method asyncAssertNotSameSize($expected, $actual, $message = '')
 * @method asyncAssertStringMatchesFormat($format, $string, $message = '')
 * @method asyncAssertStringNotMatchesFormat($format, $string, $message = '')
 * @method asyncAssertStringMatchesFormatFile($formatFile, $string, $message = '')
 * @method asyncAssertStringNotMatchesFormatFile($formatFile, $string, $message = '')
 * @method asyncAssertStringStartsWith($prefix, $string, $message = '')
 * @method asyncAssertStringStartsNotWith($prefix, $string, $message = '')
 * @method asyncAssertStringEndsWith($suffix, $string, $message = '')
 * @method asyncAssertStringEndsNotWith($suffix, $string, $message = '')
 * @method asyncAssertXmlFileEqualsXmlFile($expectedFile, $actualFile, $message = '')
 * @method asyncAssertXmlFileNotEqualsXmlFile($expectedFile, $actualFile, $message = '')
 * @method asyncAssertXmlStringEqualsXmlFile($expectedFile, $actualXml, $message = '')
 * @method asyncAssertXmlStringNotEqualsXmlFile($expectedFile, $actualXml, $message = '')
 * @method asyncAssertXmlStringEqualsXmlString($expectedXml, $actualXml, $message = '')
 * @method asyncAssertXmlStringNotEqualsXmlString($expectedXml, $actualXml, $message = '')
 * @method asyncAssertEqualXMLStructure(DOMElement $expectedElement, DOMElement $actualElement, $checkAttributes = false, $message = '')
 * @method asyncAssertThat($value, PHPUnit_Framework_Constraint $constraint, $message = '')
 * @method asyncAssertJson($actualJson, $message = '')
 * @method asyncAssertJsonStringEqualsJsonString($expectedJson, $actualJson, $message = '')
 * @method asyncAssertJsonStringNotEqualsJsonString($expectedJson, $actualJson, $message = '')
 * @method asyncAssertJsonStringEqualsJsonFile($expectedFile, $actualJson, $message = '')
 * @method asyncAssertJsonStringNotEqualsJsonFile($expectedFile, $actualJson, $message = '')
 * @method asyncAssertJsonFileEqualsJsonFile($expectedFile, $actualFile, $message = '')
 * @method asyncAssertJsonFileNotEqualsJsonFile($expectedFile, $actualFile, $message = '')
 */
trait AsyncAssert
{
    protected $messages = [];

    public function __call($name, $arguments)
    {
        if (strpos($name, 'async') === 0) {
            // Trim off the async.
            $fn = lcfirst(substr($name, 5));
            if (method_exists($this, $fn)) {
                // Wait for arguments.
                all($arguments)->then(Closure::bind(function ($args) use ($fn) {
                    try {
                        $this->{$fn}(...$args);
                    } catch (Exception $e) {
                        $this->messages[] = $e;
                    }
                }, $this));
            } else {
                throw new BadMethodCallException('Async assert method not found', get_class($this), $fn);
            }
        }
    }

    final public function tearDown()
    {
        if (method_exists($this, 'fail')) {
            foreach ($this->messages as $message) {
                echo 'There were '.count($this->messages).' total failures';
                throw $message;
            }
        }
        if (method_exists($this, 'asyncTearDown')) {
            $this->asyncTearDown();
        }
    }
}
