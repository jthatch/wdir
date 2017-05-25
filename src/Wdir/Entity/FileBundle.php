<?php

namespace Wdir\Entity;

use Wdir\Entity\File;
use Wdir\Entity\Request;

class FileBundle extends \FilesystemIterator
{
  protected $request;
  protected $files;
  protected $sortBy = 'getFilename';
  protected $sortDirection = 'asc';

  public function __construct(Request $request, $flags = \FilesystemIterator::CURRENT_AS_FILEINFO)
  {
    $this->setRequest($request);
    parent::__construct($request->getCwdAndPath(), $flags);
  }

  public function setRequest(Request $request)
  {
    $this->request = $request;
    return $this;
  }

  public function getRequest()
  {
    return $this->request;
  }

  public function current()
  {
    $file = new File(parent::current());
    $file->setRequest($this->getRequest());
    return $file;
  }

  public function offsetSet($offset, $value)
  {
    if (!$value instanceof File) {
      throw new \InvalidArgumentException("value must be instance of File");
    }
  }

  public function setSortBy($sortBy)
  {
    $file = new File(__FILE__);
    if (!method_exists($file, $sortBy)) {
      throw new \InvalidArgumentException("Invalid method to sort by");
    }
    else {
      $this->sortBy = $sortBy;
      return $this;
    }
  }

  public function getSortBy()
  {
    return $this->sortBy;
  }

  public function setSortDirection($sortDirection)
  {
    $this->sortDirection = $sortDirection;
    return $this;
  }

  public function getSortDirection()
  {
    return $this->sortDirection;
  }

  public function getFiles()
  {
    $files = [];
    foreach($this as $file) {
      if (defined(APP_PHP) && APP_PHP === $file->getFilename())
        continue;
      $files[] = $file;
    }

    $sortBy = $this->getSortBy();
    $sortDirection = $this->getSortDirection();

    uasort($files, function($a, $b) use ($sortBy, $sortDirection) {
      return $sortDirection == 'asc' 
        ? strnatcmp($a->{$sortBy}(), $b->{$sortBy}())
        : strnatcmp($b->{$sortBy}(), $a->{$sortBy}());
    });

    // do we need to add parent directory?
    if (!$this->areWeAtCwd())
      array_unshift($files, $this->getParentDirectory());
    
    return $files;

    return $files;
  }

  protected function areWeAtCwd()
  {
    return
      realpath($this->getRequest()->getCwd()) ===
      realpath($this->getRequest()->getCwdAndPath())
    ;
  }

  protected function getParentDirectory()
  {
    $file = new File($this->getRequest()->getCwdAndPath() . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR);
    if (realpath($this->getRequest()->getCwd()) == $file->getPathInfo()->getPath()) {
      $file->setRequest(new Request($this->getRequest()->getCwd(), ''));
    }
    else {
      $file->setRequest(
        new Request(
          $this->getRequest()->getCwd(), 
          str_replace($this->getRequest()->getCwd(), '', $file->getPathInfo()->getPath())
        )
      );
    }
    return $file;
  }

}