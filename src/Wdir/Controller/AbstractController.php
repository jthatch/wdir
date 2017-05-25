<?php

namespace Wdir\Controller;

use Wdir\Entity\FileBundle;
use Wdir\Entity\File;
use Wdir\Entity\Request;

abstract class AbstractController implements ControllerInterface
{
	protected $request;
	protected $bundle;
	protected $error;

	public function setBundle(FileBundle $bundle)
	{
		$this->bundle = $bundle;
		return $this;
	}

	public function getBundle()
	{
		return $this->bundle;
	}

	public function isBundle()
	{
		return (bool) $this->getBundle();
	}

	public function setError(\UnexpectedValueException $error)
	{
		$this->error = $error;
		return $this;
	}

	public function getError()
	{
		return $this->error;
	}

	public function isError()
	{
		return (bool) $this->error;
	}

  public function setRequest(Request $request)
  {
    return $this->request = $request;
    return $this;
  }

  public function getRequest()
  {
    return $this->request;
  }

  public function isRequest()
  {
    return (bool) $this->getRequest();
  }

  public function isRequestAFile()
  {
    $file = new File($this->getRequest()->getCwdAndPath());
    return $file->isFile();
  }

}

