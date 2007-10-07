<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en-US" lang="en-US">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<meta http-equiv="imagetoolbar" content="no" />
		<title><?php echo $this->get('title'); ?></title>
		<link href="screen.css" rel="stylesheet" type="text/css" media="screen" />
	</head>
	<body>
		<div id="body">
			<h1><?php echo $this->href('index.php', $this->get('title')); ?></h1>
			<ul id="navigation">
				<li id="about"><?php echo $this->href('index.php', $this->get('aboutTitle')); ?></li>
				<li id="demo"><?php echo $this->href('demo.php', $this->get('demoTitle')); ?></li>
				<li id="download"><?php echo $this->href('http://sourceforge.net/project/showfiles.php?group_id=204915', $this->get('downloadTitle')); ?></li>
				<li id="tracker"><?php echo $this->href('http://sourceforge.net/tracker/?group_id=204915', $this->get('trackerTitle')); ?></li>
				<li id="svn"><?php echo $this->href('http://sourceforge.net/svn/?group_id=204915', $this->get('svnTitle')); ?></li>
				<li id="sf"><?php echo $this->href('http://sourceforge.net/projects/markdownify', $this->get('sf.netTitle')); ?></li>
			</ul>
			<?php echo $this->renderPart('content'); ?>
			<div id="footer"><?php echo $this->get('footer'); ?></div>
		</div>
	</body>
</html>