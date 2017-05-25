<?php

namespace Wdir\Controller;

use Wdir\Entity\FileBundle;
use Wdir\Entity\Request;

interface ControllerInterface
{
  public function setBundle(FileBundle $bundle);

  public function getBundle();

  public function setError(\UnexpectedValueException $error);

  public function getError();

  public function setRequest(Request $request);

  public function getRequest();

  public function render();
}
