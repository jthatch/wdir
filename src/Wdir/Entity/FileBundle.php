<?php


namespace Wdir\Entity;

use Wdir\Entity\File;

class FileBundle extends \FilesystemIterator
{
  protected $cwd; // this is the directory jail the script lives in. It cannot traverse above this.
  protected $path;
  protected $files;
  protected $sortBy = 'getFilename';
  protected $sortDirection;

  public function __construct($cwd, $path, $flags = \FilesystemIterator::CURRENT_AS_FILEINFO)
  {
    $path = $this->sanitise($path);
    //$path = $this->removeInitialSlash($path);
    $this->setCwd($cwd);
    $this->setPath($path);
    parent::__construct($this->getCwdandPath(), $flags);
  }

  public function current()
  {
    $file = new File(parent::current());
    $file->setRequest($this->getPath());
    return $file;
  }

  public function offsetSet($offset, $value)
  {
    if (!$value instanceof File) {
      throw new \InvalidArgumentException("value must be instance of File");
    }
  }

  public function setPath($path)
  {
    $this->path = rtrim($path, DIRECTORY_SEPARATOR);
    $this->path = ltrim($path, DIRECTORY_SEPARATOR);
    return $this;
  }

  public function getPath()
  {
    return $this->path;
  }

  public function setCwd($cwd)
  {
    $this->cwd = rtrim($cwd, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    return $this;
  }

  public function getCwd()
  {
    return $this->cwd;
  }

  public function getCwdAndPath()
  {
    return $this->getCwd() . $this->getPath();
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
      if (APP_PHP === $file->getFilename())
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

  protected function removeInitialSlash($path)
  {
    if (DIRECTORY_SEPARATOR === substr($path, 0, 1) && strlen($path) > 1)
      return substr($path, 1);
    else if (DIRECTORY_SEPARATOR === substr($path, 0, 1))
      return '';
    return $path;
  }

  protected function sanitise($path)
  {
    $path = urldecode($path);
    return str_replace('..', '', $path);
  }

  protected function areWeAtCwd()
  {
    return
      realpath($this->getCwd()) ===
      realpath($this->getCwd() . DIRECTORY_SEPARATOR . $this->getPath())
    ;
  }

  protected function getParentDirectory()
  {
    $file = new File($this->getCwdAndPath() . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR);
    if (realpath($this->getCwd()) == $file->getPathInfo()->getPath()) {
      $file->setRequest('');
    }
    else {
      $file->setRequest(str_replace($this->getCwd(), '/', $file->getPathInfo()->getPath()));
    }
    return $file;
  }

}