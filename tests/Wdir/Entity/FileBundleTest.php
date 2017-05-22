<?php

namespace Tests\Wdir\Entity;

use Wdir\Entity\FileBundle;

class FileBundleTest extends \PHPUnit_Framework_TestCase
{

  public function testFileBundleConstructor()
  {
    $this->assertInstanceOf(FileBundle::class, new FileBundle(getcwd(), DIRECTORY_SEPARATOR));
  }

  public function testFileBundleConstructorFailure()
  {
    $this->setExpectedException(\UnexpectedValueException::class);
    new FileBundle(getcwd().'FAKE_DIR_HERE', DIRECTORY_SEPARATOR);
  }

  public function testFileBundleOffsetSetFailure()
  {
    $this->setExpectedException(\InvalidArgumentException::class);
    $bundle = new FileBundle(getcwd(), DIRECTORY_SEPARATOR);
    $bundle->offsetSet(1, new \stdClass);
  }

  public function testFileBundlePath()
  {
    $bundle = new FileBundle(getcwd(), DIRECTORY_SEPARATOR);
    $this->assertEquals($bundle->getPath(), '');
  }

  public function testFileBundleCwd()
  {
    $bundle = new FileBundle(getcwd(), DIRECTORY_SEPARATOR);
    $this->assertEquals($bundle->getCwd(), getcwd() . DIRECTORY_SEPARATOR);
  }

  public function testFileBundleCwdAndPath()
  {
    $bundle = new FileBundle(getcwd(), DIRECTORY_SEPARATOR);
    $this->assertEquals($bundle->getCwdAndPath(), getcwd() . DIRECTORY_SEPARATOR);
  }

  public function testFileBundleSortBy()
  {
    $bundle = new FileBundle(getcwd(), DIRECTORY_SEPARATOR);
    $bundle->setSortBy('getFilename');
    $this->assertEquals($bundle->getSortBy(), 'getFilename');
  }

  public function testFileBundleSortByFailure()
  {
    $this->setExpectedException(\InvalidArgumentException::class);
    $bundle = new FileBundle(getcwd(), DIRECTORY_SEPARATOR);
    $bundle->setSortBy('thisShouldFail');
  }

  public function testFileBundleSortDirection()
  {
    $bundle = new FileBundle(getcwd(), DIRECTORY_SEPARATOR);
    $bundle->setSortDirection('desc');
    $this->assertEquals($bundle->getSortDirection(), 'desc');
  }

  public function testFileBundleGetFiles()
  {
    $bundle = new FileBundle(getcwd(), DIRECTORY_SEPARATOR);
    $this->assertInternalType("array", $bundle->getFiles());
  }
}
