bovigo/callmap
==============

Allows to stub and mock method calls by applying a callmap.

Package status
--------------

Please note that a stable release is not available yet. Releases with version numbers below 1.0.0 should be considered as in development.

[![Build Status](https://secure.travis-ci.org/mikey179/bovigo-callmap.png)](http://travis-ci.org/mikey179/bovigo-callmap) [![Coverage Status](https://coveralls.io/repos/mikey179/bovigo-callmap/badge.png?branch=master)](https://coveralls.io/r/mikey179/bovigo-callmap?branch=master)

[![Latest Stable Version](https://poser.pugx.org/bovigo/callmap/version.png)](https://packagist.org/packages/bovigo/callmap) [![Latest Unstable Version](https://poser.pugx.org/bovigo/callmap/v/unstable.png)](//packagist.org/packages/bovigo/callmap)


Installation
------------

_bovigo/callmap_ is distributed as [Composer](https://getcomposer.org/) package.
To install it as a development dependency of your package add the following line
to the `require-dev` package links:

    "bovigo/callmap": "~0.4"

To install it as a runtime dependency for your package use the following command:

    composer require "bovigo/callmap=~0.4"

Requirements
------------

_bovigo/callmap_ requires at least PHP 5.4. For argument verification PHPUnit
is required.


Usage
-----

Explore the [tests](https://github.com/mikey179/bovigo-callmap/tree/master/src/test/php)
to see how _bovigo/callmap_ can be used. For the very eager, here's a code
example which features almost all of the possibilities:

```php
$yourClass = NewInstance::of('name\of\YourClass', ['some', 'arguments'])
        ->mapCalls(
                ['aMethod'     => 313,
                 'otherMethod' => function() { return 'yeah'; },
                 'play'        => onConsecutiveCalls(303, 808, 909, throws(new \Exception('error')),
                 'ups'         => throws(new \Exception('error')),
                 'hey'         => 'strtoupper'
                ]
        );
```

However, if you prefer text instead of code, read on.

Note: for the sake of brevity below it is assumed the used classes and functions
are imported into the current namespace via
```php
use bovigo\callmap\NewInstance;
use function bovigo\callmap\throws;
use function bovigo\callmap\onConsecutiveCalls;
```

_(For PHP versions older than 5.6.0, you can do `use bovigo\callmap` and call them
with `callmap\throws()` and `callmap\onConsecutiveCalls()`.)_


### Specify return values for method invocations ###

As the first step, you need to get an instance of the class, interface or trait
you want to specify return values for. To do this, _bovigo/callmap_ provides two
possibilities. The first one is to create a new instance where this instance is
a proxy to the actual class:

```php
$yourClass = NewInstance::of('name\of\YourClass', ['some', 'arguments']);
```

This creates an instance where each method call is passed to the original class
in case no return value was specified via the callmap. Also, it calls the
constructor of the class to instantiate of. If the class doesn't have a
constructor, or you create an instance of an interface or trait, the list of
constructor arguments can be left away.

The other option is to create a complete stub:

```php
$yourClass = NewInstance::stub('name\of\YourClass');
```

Instances created that way don't forward method calls. Note: in case you use a
PHP version older than 5.6.0, this won't work with PHP's internal classes, and
you will get an `ReflectionException` instead. See [PHP manual](http://php.net/manual/en/reflectionclass.newinstancewithoutconstructor.php)
for details.

Ok, so we created an instance of the thing that we want to specify return values
for, how to do that?

```php
$yourClass->mapCalls(
        ['aMethod'     => 303,
         'otherMethod' => function() { return 'yeah'; }
        ]
);
```

We simply pass a callmap to the `mapCalls()` method. Now, if something calls
`$yourClass->aMethod()`, the return value will always be `303`. In the case of
`$yourClass->otherMethod()`, the callable will be evaluated and its return value
will be returned.

Please be aware that the array provided with the `mapCalls()` method should
contain all methods that should be stubbed. If you call this method a second
time the complete callmap will be replaced:

```php
$yourClass->mapCalls(['aMethod' => 303]);
$yourClass->mapCalls(['otherMethod' => function() { return 'yeah'; }]);
```

As a result of this, `$yourClass->aMethod()` is not set any more to return `303`.

### Default return values ###

Depending on what is instantiated and how, there will be default return values
for the case that no call mapping has been passed for a method which actually is
called.

1.  Interfaces<br/>
    Default return value is always `null`, except the `@return` type hint in the
    doc comment specifies the short class name or the fully qualified class name
    of the interface itself or any other interface it extends. In that case the
    default return value will be the instance itself.

2.  Traits<br/>
    When instantiated with `NewInstance::of()` the default return value will be
    the value a call to the according method returns.<br/>
    When instantiated with `NewInstance::stub()` and for abstract methods the
    default return value is `null`, except the `@return` type hint in the doc
    comment specifies `$this` or `self`.

3.  Classes<br/>
    When instantiated with `NewInstance::of()` the default return value will be
    the value which is returned by the according method of the original class.<br/>
    When instantiated with `NewInstance::stub()` and for abstract methods the
    default return value is `null`, except the `@return` type hint in the doc
    comment specifies `$this` or `self`, the short class name or the fully
    qualified class name of the class or of a parent class or any interface the
    class implements. Exception to this: if the return type is `\Traversable`
    and the class implements this interface return value will be `null`.

### Specify a series of return values ###

Sometimes a method gets called more than once and you need to specify different
return values for each call.

```php
$yourClass->mapCalls(['aMethod' => onConsecutiveCalls(303, 808, 909)]);
```

This will return a different value on each invocation of `$yourClass->aMethod()`
in the order of the specified return values. If the method is called more often
than return values are specified, each subsequent call will return `null`.


### Let's throw an exception ###

Sometimes you don't need to specify a return value, but want the method to throw
an exception on invocation. Of course you could do that by providing a callable
in the callmap which throws the exception, but there's a more handy way available:

```php
$yourClass->mapCalls(['aMethod' => throws(new \Exception('error'))]);
```

Now each call to this method will throw this exception.

Of course this can be combined with a series of return values:

```php
$yourClass->mapCalls(['aMethod' => onConsecutiveCalls(303, throws(new \Exception('error')))]);
```

Here, the first invocation of `$yourClass->aMethod()` will return `303`, whereas
the second call will lead to the exception being thrown.


### Is there a way to access the passed arguments? ###

It might be useful to use the arguments passed to a method before returning a
value. If you specify a callable this callable will receive all arguments passed
to the method:

```php
$yourClass->mapCalls(['aMethod' => function($arg1, $arg2) { return $arg2;}]);

echo $yourClass->aMethod(303, 'foo'); // prints foo
```

However, if a method has optional parameters the default value will *not* be
passed as argument if it wasn't given in the actual method call. Only explicitly
passed arguments will be forwarded to the callable.


### Do I have to specify a closure or can I use an arbitrary callable? ###

You can:

```php
$yourClass->mapCalls(['aMethod' => 'strtoupper']);

echo $yourClass->aMethod('foo'); // prints FOO
```


### How do I specify that an object returns itself? ###

Actually, you don't. _bovigo/callmap_ is smart enough to detect when it should
return the object instance instead of null when no call mapping for a method was
provided. To achieve that, _bovigo/callmap_ tries to detect the return type of a
method from its doc comment. If the return type specified there is one of `$this`,
`self`, the short class name or the fully qualified class name of the class or
of a parent class or any interface the class implements, it will return the
instance instead of null.

Exception to this: if the return type is `\Traversable` this doesn't apply, even
if the class implements this interface.

Please note that `@inheritDoc` is not supported at the moment.

*Btw: looking forward to return type hints with PHP 7. ;-)*

In case this leads to a false interpretation and the instance is returned when
in fact it should not, you can always overrule that by explicitly stating a
return value in the callmap.


### Which methods can be used in the callmap? ###

Only non-static, non-final public and protected methods can be used.

In case you want to map a private, or a final, or a static method you are out of
luck. Probably you should rethink your class design.

Oh, and of course you can't use all of this with a class which is declared as
final.


### What happens if a method specified in the callmap doesn't exist? ###

In case the callmap contains a method which doesn't exist or is not applicable
for mapping (see above) `mapCalls()` will throw an `\InvalidArgumentException`.
This also prevents typos and wondering why something doesn't work as expected.


### Verify method invocations ###

_Available since 0.5.0_

Sometimes it is required to ensure that a method was invoked a certain amount of
times. In order to do that, _bovigo/callmap_ provides the `verify()` function:

```php
verify($yourClass, 'aMethod')->wasCalledOnce();
```

In case it was not called exactly once, this will throw a `CallAmountViolation`.
Otherwise, it will simply return true. This allows you to use it in an assertion
in case the test method doesn't have any other assertion and you want to have one:

```php
$this->assertTrue(verify($yourClass, 'aMethod')->wasCalledOnce());
```

Of course you can verify the call amount even if you didn't specify the method
in the callmap.

Here is a list of methods that the instance returned by `verify()` offers for
verifying the amount of method invocations:

*   `wasCalledAtMost($times)`<br/>
    Asserts that the method was invoked at maximum the given amount of times.
*   `wasCalledAtLeastOnce()`<br/>
    Asserts that the method was invoked at least once.
*   `wasCalledAtLeast($times)`<br/>
    Asserts that the method was invoked at minimum the given amount of times.
*   `wasCalledOnce()`<br/>
    Asserts that the method was invoked exactly once.
*   `wasCalled($times)`<br/>
    Asserts that the method was invoked exactly the given amount of times.
*   `wasNeverCalled()`<br/>
    Asserts that the method was never invoked.

In case the method to check doesn't exist or is not applicable for mapping (see
above) all of those methods throw an `\InvalidArgumentException`. This also
prevents typos and wondering why something doesn't work as expected.

By the way, if PHPUnit is available, `CallAmountViolation` will extend
`PHPUnit_Framework_ExpectationFailedException`. In case it isn't available it
will simply extend `\Exception`.


### Verify passed arguments ###

_Available since 0.5.0_

_Please note that for this feature at the current time PHPUnit must be present,
as argument verification makes use of the `PHPUnit_Framework_Constraint` classes._

In some cases it is useful to verify that an instance received the correct
arguments. You can do this with `verify()` as well:

```php
verify($yourClass, 'aMethod')->received(303, 'foo');
```

This will verify that each of the expected arguments matches the actually
received arguments of the first invocation of that method. In case you want to
verify another invocation, we got you covered:

```php
verify($yourClass, 'aMethod')->receivedOn(3, 303, 'foo');
```

This applies verification to the arguments the method received on the third
invocation.

There is also a shortcut to verify that the method didn't receive any arguments:

```php
verify($yourClass, 'aMethod')->receivedNothing(); // received nothing on first invocation
verify($yourClass, 'aMethod')->receivedNothing(3); // received nothing on third invocation
```

In case the method wasn't invoked (that much), a `MissingInvocation` exception
will be thrown.<br/>
In case the method received less arguments than expected, a `ArgumentMismatch`
exception will be thrown. It will also be thrown when `receivedNothing()` detects
that at least one argument was received.

Please not that each method has its own invocation count (whereas in PHPUnit the
invocation count is for the whole mock object). Also, invocation count starts at
1 for the first invocation, not at 0.

Both `reveived()` and `receivedOn()` also accept any instance of
`PHPUnit_Framework_Constraint`:

```php
verify($yourClass, 'aMethod')->received($this->isInstanceOf('another\ExampleClass'));
```

In case a bare value is passed it is assumed that `PHPUnit_Framework_Constraint_IsEqual`
is meant.