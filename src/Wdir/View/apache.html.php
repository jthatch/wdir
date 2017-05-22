<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 3.2 Final//EN">
<html>
 <head>
  <title>Index of /<?=$this->getRequest()?></title>
 </head>
 <body>

<?php if ($this->isError()) : ?>
	<h3>Error: <?=$this->getError()->getMessage()?></h3>
  <hr>

<?php else: ?>
<h2>Index of /<?=$this->getRequest()?></h2>
<pre>
<a href="<?=$this->getBaseUrl() . $this->getSortUrlByName('name')?>">Name</a>                                        <a href="<?=$this->getBaseUrl() . $this->getSortUrlByName('lmod')?>">Last modified</a>      <a href="<?=$this->getBaseUrl() . $this->getSortUrlByName('size')?>">Size</a>  Description<hr>
<?php foreach($this->getBundle()->getFiles() as $file) :
  $url = !empty($file->getUrl()) ? APP_PHP . '?r=' . $file->getUrl() : APP_PHP ?>
<a href="<?=$url?>" title="<?=$file->getName()?>"><?=substr($file->getName(), 0, 43)?></a><?=str_repeat(' ', (43 - strlen($file->getName()) > 0 ? 43 - strlen($file->getName()) : 0))?> <?= date('Y-m-d H:i', $file->getCTime())?>    <?=$file->getNiceSize()?>

<?php endforeach; ?>
<hr>
<?php endif; ?>
</pre>
<div style="text-align:right">Powered by <a href="http://github.com/jthatch" target="_blank">wdir</a></div>
</body>
</html>
