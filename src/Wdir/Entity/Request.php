<?php

namespace Wdir\Entity;

/**
 * The User request sanitised.
 */
class Request
{
  protected $cwd;
  protected $path;

  /**
   * You can pass the cwd and request into the constructor.
   * $request = new Request(getcwd(), 'Docs/Budgets')
   *
   * @param string $cwd
   * @param string $path
   */
  public function __construct($cwd = '', $path = '')
  {
    if (!empty($cwd))
      $this->setCwd($cwd);
    if (!empty($path))
      $this->setPath($path);
  }

  /**
   * The Current Working Directory. A / Will be appended to the end automatically.
   * This should be set by the system and as such will not be sanitised.
   * It's used in conjunction with the path to prevent the user traversing above the cwd.
   *
   * @param string $cwd
   * @return self
   */
  public function setCwd($cwd)
  {
    $cwd = rtrim($cwd, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    $this->cwd = $cwd;
    return $this;
  }

  /**
   * Returns the CWD with a trailing slash.
   *
   * @return string
   */
  public function getCwd()
  {
    return $this->cwd;
  }

  /**
   * Sets the Path provided to us by the user. 
   * By default this will be sanitised to prevent file-system attacks.
   *
   * @param string $path
   * @param bool $sanitise
   * @return self
   */
  public function setPath($path, $sanitise = true)
  {
    if ($sanitise)
      $path = self::sanitise($path);
    $this->path = $path;
    return $this;
  }

  /**
   * Returns the Path
   *
   * @return string
   */
  public function getPath()
  {
    return $this->path;
  }

  /**
   * Combine the cwd and path to a full string.
   *
   * @return string
   */
  public function getCwdAndPath()
  {
    return $this->getCwd() . $this->getPath();
  }

  /**
   * When called in the context of a string, we just return the path
   *
   * @return string
   */
  public function __toString()
  {
    return (string) $this->getPath();
  }

  /**
   * Sanitise the string, preventing attacks:
   * Directory Traversal: https://en.wikipedia.org/wiki/Directory_traversal_attack
   * Null-Byte Injection: http://resources.infosecinstitute.com/null-byte-injection-php/
   *
   * @param [type] $str
   * @return void
   */
  public static function sanitise($str)
  {
    $str = urldecode($str); // Decode all url escaped characters
    $str = str_replace(chr(0), '', $str); // remove null bytes, could also use filter_var($str, FILTER_SANITIZE_STRING)
    $str= str_replace('..', '', $str); // prevent traversal
    $str = ltrim($str, DIRECTORY_SEPARATOR); // Remove Initial slash if present. Not needed as CWD has a trailing slash.
    $str = rtrim($str, DIRECTORY_SEPARATOR); // We'll remove any trailing slash the user may have added.
    return $str;
  }

}