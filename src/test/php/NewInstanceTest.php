<?php
/**
 * This file is part of bovigo\callmap.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @package  bovigo_callmap
 */
namespace bovigo\callmap;
/**
 * Helper class for the test.
 */
class AnotherTestHelperClass
{

}
/**
 * Another helper class.
 */
final class ThisIsNotPossible
{

}
/**
 * All remaining tests for bovigo\callmap\NewInstance.
 */
class NewInstanceTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     * @expectedException  InvalidArgumentException
     */
    public function callWithNonObjectOrClassNameThrowsInvalidArgumentException()
    {
        NewInstance::of(313);
    }

    /**
     * @test
     * @expectedException  InvalidArgumentException
     */
    public function callWithNonExistingClassNameThrowsInvalidArgumentException()
    {
        NewInstance::of('DoesNotExist');
    }

    /**
     * @test
     * @expectedException  InvalidArgumentException
     * @expectedExceptionMessage  Can not create mapping proxy for final class bovigo\callmap\ThisIsNotPossible
     * @since  0.4.0
     */
    public function canNotCreateInstanceOfFinalClass()
    {
        NewInstance::of('bovigo\callmap\ThisIsNotPossible');
    }

    /**
     * @test
     * @expectedException  InvalidArgumentException
     * @expectedExceptionMessage  Can not create mapping proxy for final class bovigo\callmap\ThisIsNotPossible
     * @since  0.4.0
     */
    public function canNotCreateStubInstanceOfFinalClass()
    {
        NewInstance::stub('bovigo\callmap\ThisIsNotPossible');
    }

    /**
     * @test
     * @expectedException  InvalidArgumentException
     * @expectedExceptionMessage  Can not create mapping proxy for final class bovigo\callmap\ThisIsNotPossible
     * @since  0.4.0
     */
    public function canNotRetrieveMappedClassnameForFinalClass()
    {
        NewInstance::classname('bovigo\callmap\ThisIsNotPossible');
    }

    /**
     * @test
     * @since  0.2.0
     */
    public function doesNotGenerateClassTwice()
    {
        assertEquals(
                NewInstance::classname('\ReflectionObject'),
                NewInstance::classname('\ReflectionObject')
        );
    }

    /**
     * @test
     * @since  0.2.0
     */
    public function doesCreateIndependentInstances()
    {
        assertNotSame(
                NewInstance::of('\ReflectionObject', [$this]),
                NewInstance::of('\ReflectionObject', [$this])
        );
    }

    /**
     * @test
     * @since  0.2.0
     */
    public function doesCreateIndependentStubs()
    {
        assertNotSame(
                NewInstance::stub('bovigo\callmap\AnotherTestHelperClass'),
                NewInstance::stub('bovigo\callmap\AnotherTestHelperClass')
        );
    }
}
