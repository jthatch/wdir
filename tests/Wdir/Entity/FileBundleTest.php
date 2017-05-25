<?php

namespace Tests\Wdir\Entity;

use Wdir\Entity\FileBundle;
use Wdir\Entity\Request;

class FileBundleTest extends \PHPUnit_Framework_TestCase
{

  public function testFileBundleConstructor()
  {
    $request = new Request(getcwd());
    $this->assertInstanceOf(FileBundle::class, new FileBundle($request));
  }

  public function testFileBundleConstructorFailure()
  {
    $this->setExpectedException(\UnexpectedValueException::class);
    $request = new Request(getcwd(), 'FAKE_DIR_HERE');
    new FileBundle($request);
  }

  public function testFileBundleOffsetSetFailure()
  {
    $this->setExpectedException(\InvalidArgumentException::class);
    $request = new Request(getcwd());
    $bundle = new FileBundle($request);
    $bundle->offsetSet(1, new \stdClass);
  }

  public function testFileBundleReturnsRequest()
  {
    $request = new Request(getcwd());
    $bundle = new FileBundle($request);
    $this->assertInstanceOf(Request::class, $bundle->getRequest());
  }

  public function testFileBundleSortBy()
  {
    $request = new Request(getcwd());
    $bundle = new FileBundle($request);
    $bundle->setSortBy('getFilename');
    $this->assertEquals($bundle->getSortBy(), 'getFilename');
  }

  public function testFileBundleSortByFailure()
  {
    $this->setExpectedException(\InvalidArgumentException::class);
    $request = new Request(getcwd());
    $bundle = new FileBundle($request);
    $bundle->setSortBy('thisShouldFail');
  }

  public function testFileBundleSortDirection()
  {
    $request = new Request(getcwd());
    $bundle = new FileBundle($request);
    $bundle->setSortDirection('desc');
    $this->assertEquals($bundle->getSortDirection(), 'desc');
  }

  public function testFileBundleGetFiles()
  {
    $request = new Request(getcwd());
    $bundle = new FileBundle($request);
    $this->assertInternalType("array", $bundle->getFiles());
  }

}
