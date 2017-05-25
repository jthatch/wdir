<?php

namespace Wdir\Controller;

use Wdir\Entity\Request;
use Wdir\Entity\FileBundle;
use Wdir\Entity\File;

class WebController extends AbstractController implements ControllerInterface
{
  protected $view;
  protected $sortBy;
  protected $sortDirection;

  public function setView($view)
  {
    $path = ROOT . DIRECTORY_SEPARATOR 
    . 'src' . DIRECTORY_SEPARATOR 
    . 'Wdir' . DIRECTORY_SEPARATOR 
    . "View/$view.html.php";
    if (!file_exists($path)) {
      throw new \UnexpectedValueException("View not found: $view");
    }
    $this->view = $path;
    return $this;
  }

  public function getView()
  {
    return $this->view;
  }

  public function loadView($data)
  {
    $view['this'] = $data;
    extract($view);
    require $this->getView();
  }

  public function getBaseUrl()
  {
    return APP_PHP . '?r=' . $this->getRequest();
  }

  public function setSortBy($sortBy)
  {
    $this->sortBy = $sortBy;
    return $this;
  }

  public function getSortBy()
  {
    return $this->sortBy;
  }

  public function getSortUrlByName($name)
  {
    switch($name) {
      case 'name':
        $code = 'N';
        break;
      case 'lmod':
        $code = 'M';
        break;
      case 'size':
        $code = 'S';
        break;
    }
     return "&C={$code}&O=" . ($this->getSortDirection() == 'desc' ? 'A' : 'D');
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

  public function setSortParams()
  {
    parse_str(filter_input(INPUT_SERVER, 'QUERY_STRING'), $get);

    // sort
    $sortBy = 'getFilename';
    if (isset($get['C'])) {
      switch($get['C']) {
        case 'M': // modified
          $sortBy = 'getMTime';
          break;
        case 'S': // size
          $sortBy = 'getSize';
          break;
        case 'N': // filename
        default:
          $sortBy = 'getFilename';
          break;
      }
    }
    $this->setSortBy($sortBy);
    $this->setSortDirection(isset($get) && isset($get['O']) && $get['O'] !== 'A' ? 'desc' : 'asc');

		return $this;
  }

  /*public function setRequest($queryString)
	{
    parse_str($queryString, $get);

    // parse request
    if (isset($get['r'])) {
      $this->request = rtrim(ltrim(str_replace('..', '', urldecode($get['r'])), DIRECTORY_SEPARATOR), DIRECTORY_SEPARATOR);
    }
    // sort
    $sortBy = 'getFilename';
    if (isset($get['C'])) {
      switch($get['C']) {
        case 'M': // modified
          $sortBy = 'getMTime';
          break;
        case 'S': // size
          $sortBy = 'getSize';
          break;
        case 'N': // filename
        default:
          $sortBy = 'getFilename';
          break;
      }
    }
    $this->setSortBy($sortBy);
    $this->setSortDirection(isset($get) && isset($get['O']) && $get['O'] !== 'A' ? 'desc' : 'asc');

		return $this;
	}*/

	public function render()
	{
    $this->setSortParams();
    if ($this->isBundle()) {
      $this->getBundle()->setSortBy($this->getSortBy());
      $this->getBundle()->setSortDirection($this->getSortDirection());

    }
    $this->loadView($this);
	}

  public function redirectToFile() {
    $url = DIRECTORY_SEPARATOR . ltrim(dirname(filter_input(INPUT_SERVER, 'SCRIPT_NAME')) . DIRECTORY_SEPARATOR . $this->getRequest(), DIRECTORY_SEPARATOR);
    header("Cache-Control: no-cache, must-revalidate");
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT");
    header("Location: {$url}");
  }
}

