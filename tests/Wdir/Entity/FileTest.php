<?php

namespace Tests\Wdir\Entity;

use Wdir\Entity\File;
use Wdir\Entity\Request;

class FileTest extends \PHPUnit_Framework_TestCase
{

  public function testFileGetFileName()
  {
    $file = new File(__FILE__);
    $this->assertEquals($file->getFilename(), basename(__FILE__));
  }

  public function testFileGetName()
  {
    $file = new File(__FILE__);
    $this->assertEquals($file->getName(), 'FileTest.php');
  }

  public function testFileGetUrl()
  {
    $file = new File(__FILE__);
    $this->assertEquals($file->getUrl(), '%2FFileTest.php');

  }

  public function testDirGetName()
  {
    $file = new File(__DIR__);
    $this->assertEquals($file->getName(), 'Entity/');
  }

  public function testFileSetRequest()
  {
    $file = new File(__FILE__);
    $request = new Request(getcwd(), 'hello/world');
    $file->setRequest($request);
    $this->assertEquals($file->getRequest()->getPath(), 'hello/world');
  }

  public function testFileGetNiceSizeAsString()
  {
    $file = new File(__FILE__);
    $this->assertInternalType("string", $file->getNiceSize());
  }

  public function testFileGetAgeAsString()
  {
    $file = new File(__FILE__);
    $this->assertInternalType("string", $file->getAge());
  }

}