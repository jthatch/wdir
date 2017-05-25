<?php

namespace Tests\Wdir\Entity;

use Wdir\Entity\Request;

class RequestTest extends \PHPUnit_Framework_TestCase
{

  public function testRequestCwd()
  {
    $request = new Request(getcwd());
    $this->assertEquals($request->getCwd(), getcwd() . DIRECTORY_SEPARATOR);
  }

  public function testRequestPath()
  {
    $request = new Request(getcwd(), 'foo/bar');
    $this->assertEquals($request->getPath(), 'foo/bar');
  }

  public function testRequestCwdAndPath()
  {
    $request = new Request(getcwd(), 'foo/bar');
    $this->assertEquals($request->getCwdAndPath(), getcwd() . '/foo/bar');
  }

  public function testRequestToString()
  {
    $request = new Request(getcwd(), 'foo/bar');
    $str = '' . $request;
    $this->assertInternalType("string", $str);
    $this->assertEquals($str, 'foo/bar');
  }

  public function testRequestSanitisePath()
  {
    $request = new Request(getcwd(), '../../');
    $this->assertEquals($request->getPath(), '');
  }

  public function testRequestHackNullByte()
  {
    $request = new Request(getcwd(), "\0/etc/passwd");
    $this->assertEquals($request->getPath(), 'etc/passwd');
  }

}