<?php
error_reporting(E_ALL | E_STRICT);

require_once 'fry/core/Fry.php';
require_once 'lib/markdown-extra.php';
require_once 'lib/smartypants.php';
try {
	$language = 'en';
	$langs = array('en', 'de');
	if (isset($_GET['lang']) && in_array($_GET['lang'], $langs)) {
		$language = $_GET['lang'];
	} elseif(isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
		# user agent
		$languages = explode(',', str_replace('-', '_',
						str_replace('..','',
							str_replace('/','',$_SERVER['HTTP_ACCEPT_LANGUAGE']))));
		$cur_qual = 0;
		$cur_lang = '';
		for ($i = 0, $len = count($languages); $i < $len; $i++) {
			$lang = trim($languages[$i]);
			if (strstr($lang,';q=')) {
				# user defined qualtiy
				$lang = explode(';q=',$lang,2);
				$lang = trim($lang[0]);
				$qual = floatval($lang[1]);
			} else {
				# default quality
				$qual = 1.0;
			}
			if (!empty($cur_lang) && $cur_qual >= $qual) {
				# currently selected language has a higher quality
				continue;
			}
			# remove trailing semicolon(s)
			$file = rtrim($lang, ';');
			# search for matches to first two chars, e.g. "en" or "de"
			$lang = substr($lang, 0, 2);
			if (in_array($lang, $langs)) {
				$cur_lang = $lang;
				$cur_qual = $qual;
			}
		}
		$language = $cur_lang;
	}
	$fry = new Fry(new FryConfig('config.xml'));
	$fry->setDictionary(new FryDictionary('l18n/'.$language.'.xml'));
	define('LANG', $language);
	$fry->setGlobal('title', 'Markdownify: The HTML to Markdown converter for PHP');

	$body = new FryTemplate('templates/body.tpl.php');
	
	if (isset($_GET['show']) && $_GET['show'] == 'demo') {
		if (!empty($_POST['input'])) {
			require_once 'lib/markdownify_extra.php';
			require_once 'lib/diff.php';
			$input = $_POST['input'];
			if (!isset($_POST['leap'])) {
				$leap = MDFY_LINKS_EACH_PARAGRAPH;
			} else {
				$leap = $_POST['leap'];
			}
			
			if (!isset($_POST['keepHTML'])) {
				$keephtml = MDFY_KEEPHTML;
			} else {
				$keephtml = $_POST['keepHTML'];
			}
			if (!empty($_POST['extra'])) {
				$md = new Markdownify_Extra($leap, MDFY_BODYWIDTH, $keephtml);
			} else {
				$md = new Markdownify($leap, MDFY_BODYWIDTH, $keephtml);
			}
			$parsed = $md->parseString($input);
			$output = Markdown($parsed);
			$diff = new HTMLColorDiff;
			$input = htmlspecialchars($input, ENT_NOQUOTES, 'UTF-8');
			$inputOrig = $input;
			$output = htmlspecialchars($output, ENT_NOQUOTES, 'UTF-8');
			$parsed = htmlspecialchars($parsed, ENT_NOQUOTES, 'UTF-8');
			$diff->diff(&$input, &$output)->markChanges();
		} else {
			$parsed = '';
			$input = '';
			$output = '';
			$inputOrig = '';
		}
		$content = new FryTemplate('demo.tpl.php');
		$content->set('inputForm', $inputOrig);
		$content->set('input', $input);
		$content->set('output', $output);
		$content->set('parsed', $parsed);
	} else {
		$content = new FryTemplate('about.tpl.php');
	}

	$fry->setTemplate($body);
	$fry->setTemplatePart('content', $content);

	echo $fry->render();
} catch (Exception $e) {
	ob_flush();
	echo "<b>Error:</b><br/>\n" . $e->getMessage() 
			. "<br/>\n<b>Trace:</b><br/>\n" 
			. preg_replace("/(\n)|(\r\n)/", "\\1<br/>", $e->getTraceAsString());
}
error_reporting(E_ALL);
include_once('../../slimstat/inc.stats.php');