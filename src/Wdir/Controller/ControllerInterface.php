<?php

namespace Wdir\Controller;

use Wdir\Entity\FileBundle;

interface ControllerInterface
{

  /* public function __construct($cwd, $request); */
  /* public function __construct(FileBundle $bundle); */

  public function setBundle(FileBundle $bundle);

  public function getBundle();

  public function setError(\UnexpectedValueException $error);

  public function getError();

  public function render();
}
