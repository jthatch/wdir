<?php

namespace Wdir\Controller;

use Wdir\Entity\FileBundle;
use Wdir\Entity\File;

abstract class AbstractController implements ControllerInterface
{
	protected $cwd;
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

	public function getCwd()
	{
		return $this->cwd;
	}

	public function setCwd($cwd)
  {
    $this->cwd = rtrim($cwd, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    return $this;
  }

	public function getRequest()
	{
		return $this->request;
	}

	public function setRequest($request)
	{
		$this->request = $request;
		return $this;
	}

  public function isRequestAFile()
  {
    $file = new File($this->getCwd() . $this->getRequest());
    return $file->isFile();
  }

}

