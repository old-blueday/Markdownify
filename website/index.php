<?php
error_reporting(E_ALL);

require_once 'fry/core/Fry.php';

try {
	$lang = 'en';
	
	if (isset($_GET['lang']) && in_array($_GET['lang'], array('lt', 'en'))) {
		$lang = $_GET['lang'];
	}
	$fry = new Fry(new FryConfig('config/config.xml'));
	$fry->setGlobal('something', 'Global text, not form menu template');
	
	// main template, holder for subtemplates
	// + we set a local variable for this template
	$main = new FryTemplate("main.tpl.php");
	$main->set('title', 'Fry example 1');
	
	// menu template
	$menu = new FryTemplate("menu.tpl.php");
	
	// content template
	// + we set a local variable without using a setter
	$content = new FryTemplate("content.tpl.php");
	$content->advertisement = array(
		"Object oriented",
		"Fast",
		"Secure",
		"Developed using Test Driven Development (TDD)",
		"Easy to learn",
		"Light weight"
	);
	
	$footer = new FryTemplate("footer.tpl.php");
	
	// here we add all these templates to Fry, one main and other as parts
	$fry->setTemplate($main);
	$fry->setTemplatePart('menu', $menu);
	$fry->setTemplatePart('content', $content);
	$fry->setTemplatePart('footer', $footer);
	
	// render and output page
	echo $fry->render();
	
	// initialise Fry system, if you want, pass a config
	$fry = new Fry(new FryConfig('config/config.xml'));
	$fry->setGlobal('title', 'Markdownify: The HTML to Markdown converter for PHP');
	$body = new FryTemplate('templates/body.inc.php');
} catch (Exception $e) {
	ob_clean(); // not to see partly rendered templates output
	echo "<b>Error:</b><br/>\n" . $e->getMessage() 
			. "<br/>\n<b>Trace:</b><br/>\n" 
			. preg_replace("/(\n)|(\r\n)/", "\\1<br/>", $e->getTraceAsString());
}