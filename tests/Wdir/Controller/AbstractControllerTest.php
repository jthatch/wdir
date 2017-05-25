<?php

namespace Tests\Wdir\Controlle;

use Wdir\Controller\AbstractController;
use Wdir\Entity\FileBundle;
use Wdir\Entity\Request;

class AbstractControllerTest extends \PHPUnit_Framework_TestCase
{
  public function testControllerBundle()
  {
    $request = new Request(getcwd());
    $bundle = new FileBundle($request);
    $stub = $this->getMockForAbstractClass('Wdir\Controller\AbstractController');
    $this->assertEquals($bundle, $stub->setBundle($bundle)->getBundle());
  }

  public function testControllerError()
  {
    $this->setExpectedException(\UnexpectedValueException::class);
    $stub = $this->getMockForAbstractClass('Wdir\Controller\AbstractController');
    $stub->setError(new \UnexpectedValueException('Invalid Path'));
    throw $stub->getError();
  }

  public function testControllerIsError()
  {
    $stub = $this->getMockForAbstractClass('Wdir\Controller\AbstractController');
    $stub->setError(new \UnexpectedValueException('Invalid Path'));
    $this->assertTrue($stub->isError());
  }

  public function testControllerRequest()
  {
    $stub = $this->getMockForAbstractClass('Wdir\Controller\AbstractController');
    $request = new Request(getcwd(), 'foo/bar');
    $this->assertEquals($stub->setRequest($request)->getPath(), 'foo/bar');
  }

  public function testControllerisRequestAFile()
  {
    $stub = $this->getMockForAbstractClass('Wdir\Controller\AbstractController');
    $request = new Request(getcwd(), 'tests/Wdir/Controller/'.basename(__FILE__));
    $stub->setRequest($request);
    $this->assertTrue($stub->isRequestAFile());
  }

}