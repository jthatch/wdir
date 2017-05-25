<?php

namespace Wdir\Entity;

use Wdir\Entity\Request;

class File extends \SplFileInfo
{
  protected $request;

  public function setRequest(Request $request)
  {
    $this->request = $request;
    return $this;
  }

  public function getRequest()
  {
    return $this->request;
  }

  /**
   * Returns the filename url encoded
   *
   * @return string
   */
  public function getUrl()
  {
    if ('..' === $this->getFilename()) {
      return urlencode($this->getRequest());
    }
    else {
      return urlencode($this->getRequest() . DIRECTORY_SEPARATOR . $this->getFilename());
    }
  }

  public function getName()
  {
    if ($this->isDir())
      return $this->getFilename() . DIRECTORY_SEPARATOR;
    
    return $this->getFilename();
  }

  /**
   * Returns the last modified time relative to the current time, e.g. 15 mins ago
   *
   * @return string
   */
  public function getAge()
  {
    $ts = $this->getMTime();
    $diff = time() - $ts;
    if($diff == 0) {
      return 'now';
    } 
    else if ($diff > 0) {
      $day_diff = floor($diff / 86400);
      if ($day_diff == 0) {
          if ($diff < 60) return 'just now';
          if ($diff < 120) return '1 minute ago';
          if ($diff < 3600) return floor($diff / 60) . ' minutes ago';
          if ($diff < 7200) return 'an hour ago';
          if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
      }
      if ($day_diff == 1) { return 'Yesterday'; }
      if ($day_diff < 7) { return $day_diff . ' days ago'; }
      if ($day_diff < 31) { return ceil($day_diff / 7) . ' weeks ago'; }
      if ($day_diff < 60) { return 'last month'; }
      return date('F Y', $ts);
    }
    else {
      $diff = abs($diff);
      $day_diff = floor($diff / 86400);
      if($day_diff == 0) {
          if($diff < 120) { return 'in a minute'; }
          if($diff < 3600) { return 'in ' . floor($diff / 60) . ' minutes'; }
          if($diff < 7200) { return 'in an hour'; }
          if($diff < 86400) { return 'in ' . floor($diff / 3600) . ' hours'; }
      }
      if($day_diff == 1) { return 'Tomorrow'; }
      if($day_diff < 4) { return date('l', $ts); }
      if($day_diff < 7 + (7 - date('w'))) { return 'next week'; }
      if(ceil($day_diff / 7) < 4) { return 'in ' . ceil($day_diff / 7) . ' weeks'; }
      if(date('n', $ts) == date('n') + 1) { return 'next month'; }
      return date('F Y', $ts);
    }
  }

  /**
   * Converts bytes to the most appropriate format
   *
   * @return string
   */
  public function getNiceSize()
  {
    if ($this->isDir())
      return '-';
    $bytes = $this->getSize();
    $sz = 'BKMGTP';
    //$sz = 'bkmgtp';
    $factor = floor((strlen($bytes) - 1) / 3);
    $decimals = $factor < 2 ? 0 : 2;
    return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
  }
}

