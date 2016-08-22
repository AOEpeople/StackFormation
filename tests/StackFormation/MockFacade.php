<?php
namespace StackFormation\Tests;

abstract class MockFacade extends \PHPUnit_Framework_TestCase{

    /**
     * Returns a mock object for the specified class.
     *
     * @param string     $originalClassName       Name of the class to mock.
     * @param array|null $methods                 When provided, only methods whose names are in the array
     *                                            are replaced with a configurable test double. The behavior
     *                                            of the other methods is not changed.
     *                                            Providing null means that no methods will be replaced.
     * @param array      $arguments               Parameters to pass to the original class' constructor.
     * @param string     $mockClassName           Class name for the generated test double class.
     * @param bool       $callOriginalConstructor Can be used to disable the call to the original class' constructor.
     * @param bool       $callOriginalClone       Can be used to disable the call to the original class' clone constructor.
     * @param bool       $callAutoload            Can be used to disable __autoload() during the generation of the test double class.
     * @param bool       $cloneArguments
     * @param bool       $callOriginalMethods
     * @param object     $proxyTarget
     *
     * @return PHPUnit_Framework_MockObject_MockObject
     *
     * @throws PHPUnit_Framework_Exception
     *
     * @since  Method available since Release 3.0.0
     */
    public function getMock($originalClassName, $methods = array(), array $arguments = array(), $mockClassName = '', $callOriginalConstructor = true, $callOriginalClone = true, $callAutoload = true, $cloneArguments = false, $callOriginalMethods = false, $proxyTarget = null)
    {
        $mockBuilder = $this->getMockBuilder($originalClassName);
        $mockBuilder->setMethods($methods);
        $mockBuilder->setConstructorArgs($arguments);
        $mockBuilder->setMockClassName($mockClassName);
        if(true === $callOriginalConstructor){
            $mockBuilder->enableOriginalConstructor();
        }else{
            $mockBuilder->disableOriginalConstructor();
        }
        if(true === $callOriginalClone){
            $mockBuilder->enableOriginalClone();
        }else{
            $mockBuilder->disableOriginalClone();
        }
        if(true === $callAutoload){
            $mockBuilder->enableAutoload();
        }else{
            $mockBuilder->disableAutoload();
        }
        if(true === $cloneArguments){
            $mockBuilder->enableArgumentCloning();
        }else{
            $mockBuilder->disableArgumentCloning();
        }
        if(true === $callOriginalMethods){
            $mockBuilder->enableProxyingToOriginalMethods();
        }else{
            $mockBuilder->disableProxyingToOriginalMethods();
        }
        if($proxyTarget !== null && true === is_object($proxyTarget)){
            $mockBuilder->setProxyTarget($proxyTarget);
        }
        return $mockBuilder->getMock();
    }
}