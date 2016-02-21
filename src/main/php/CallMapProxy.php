<?php
/**
 * This file is part of bovigo\callmap.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace bovigo\callmap;
/**
 * CallMapProxy to be mixed into proxies generated by NewInstance.
 *
 * @internal
 */
trait CallMapProxy
{
    /**
     * map of method with closures to call instead
     *
     * @type  \bovigo\callmap\CallMap
     */
    private $callMap;
    /**
     * @type  array
     */
    private $callHistory = [];
    /**
     * switch whether passing calls to parent class is allowed
     *
     * @type  bool
     */
    private $parentCallsAllowed = true;

    /**
     * disable passing calls to parent class
     *
     * @return  $this
     */
    public function preventParentCalls()
    {
        $this->parentCallsAllowed = false;
        return $this;
    }

    /**
     * sets the call map to use
     *
     * @param   array  $callMap
     * @return  $this
     * @throws  \InvalidArgumentException  in case any of the mapped methods does not exist or is not applicable
     */
    public function mapCalls(array $callMap)
    {
        foreach (array_keys($callMap) as $method) {
            if (!isset($this->_allowedMethods[$method])) {
                throw new \InvalidArgumentException(
                        $this->callmapInvalidMethod($method, 'map')
                );
            }
        }

        $this->callMap = new CallMap($callMap);
        return $this;
    }

    /**
     * handles actual method calls
     *
     * @param   string    $method            actually called method
     * @param   mixed[]   $arguments         list of given arguments for methods
     * @param   bool      $shouldReturnSelf  whether the return value should be the instance itself
     * @return  mixed
     * @throws  \Exception
     */
    protected function handleMethodCall($method, $arguments, $shouldReturnSelf)
    {
        $invocation = $this->recordCall($method, $arguments);
        if (null !== $this->callMap && $this->callMap->hasResultFor($method, $invocation)) {
            return $this->callMap->resultFor($method, $arguments, $invocation);
        }

        if ($this->parentCallsAllowed && is_callable(['parent', $method])) {
            // is_callable() returns true even for abstract methods
            $refMethod = new \ReflectionMethod(get_parent_class(), $method);
            if (!$refMethod->isAbstract()) {
                return call_user_func_array(['parent', $method], $arguments);
            }
        }

        if ($shouldReturnSelf) {
            return $this;
        }

        return null;
    }

    /**
     * records method call for given method
     *
     * @param   string    $method      name of called method
     * @param   mixed[]   $arguments   list of passed arguments
     * @return  int  amount of calls for given method
     */
    private function recordCall($method, $arguments)
    {
        if (!isset($this->callHistory[$method])) {
            $this->callHistory[$method] = [];
        }

        $this->callHistory[$method][] = $arguments;
        return count($this->callHistory[$method]);
    }

    /**
     * returns amount of calls received for given method
     *
     * @param   string  $method  name of method to check
     * @return  int
     * @throws  \InvalidArgumentException  in case the method does not exist or is not applicable
     */
    public function callsReceivedFor($method)
    {
        if (!isset($this->_allowedMethods[$method])) {
            throw new \InvalidArgumentException(
                    $this->callmapInvalidMethod($method, 'retrieve call amount for')
            );
        }

        if (isset($this->callHistory[$method])) {
            return count($this->callHistory[$method]);
        }

        return 0;
    }

    /**
     * returns the arguments received for a specific call
     *
     * @param   string  $method      name of method to check
     * @param   int     $invocation  nth invocation to check, defaults to 1 aka first invocation
     * @return  mixed[]
     * @throws  \InvalidArgumentException  in case the method does not exist or is not applicable
     * @throws  \bovigo\callmap\MissingInvocation  in case no such invocation was received
     */
    public function argumentsReceivedFor($method, $invocation = 1)
    {
        if (!isset($this->_allowedMethods[$method])) {
            throw new \InvalidArgumentException(
                    $this->callmapInvalidMethod($method, 'retrieve received arguments for')
            );
        }

        if (isset($this->callHistory[$method]) && isset($this->callHistory[$method][$invocation - 1])) {
            return ['arguments' => $this->callHistory[$method][$invocation - 1],
                    'names'     => $this->_methodParams[$method]
            ];
        }

        $invocations = $this->callsReceivedFor($method);
        throw new MissingInvocation(
                sprintf(
                    'Missing invocation #%d for %s, was %s.',
                    $invocation,
                    methodName($this, $method),
                    ($invocations === 0 ?
                            'never called' :
                            ('only called ' . ($invocations === 1 ?
                                'once' : $invocations . ' times')
                            )
                    )
                )
        );
    }

    /**
     * creates complete error message when called with invalid method
     *
     * @param  string  $invalidMethod
     * @param  string  $message
     */
    private function callmapInvalidMethod($invalidMethod, $message)
    {
        return sprintf(
                'Trying to %s method %s, but it %s',
                $message,
                methodName($this, $invalidMethod),
                (method_exists($this, $invalidMethod) ?
                    'is not applicable for mapping.' :
                    'does not exist. Probably a typo?'
                )
        );
    }
}
