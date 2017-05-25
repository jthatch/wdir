<?php

namespace Wdir\Controller;

class CliController extends AbstractController implements ControllerInterface
{
  protected $cwd;
  protected $request;
  protected $bundle;
  protected $error;

  public function render()
  {
    if ($this->isError()) {
      echo $this->getError()->getMessage() . "\n";
    }
    else {
      printf("Index of %s\n", $this->getRequest()->getCwdAndPath());
      printf("%s %s %s\n",
        str_pad('NAME', 36, ' ', STR_PAD_RIGHT),
        str_pad('SIZE', 6, ' ', STR_PAD_RIGHT),
        str_pad('AGE', 15, ' ', STR_PAD_RIGHT)
      );
      echo str_repeat("-", 60) . "\n";
      foreach($this->getBundle() as $file) {
        printf("%s %s %s\n",
          str_pad($file->getName(), 36, ' ', STR_PAD_RIGHT),
          str_pad($file->getNiceSize(), 6, ' ', STR_PAD_RIGHT),
          str_pad($file->getAge(), 15, ' ', STR_PAD_RIGHT)
        );
      }
    }
  }
}