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
				<li id="donate">
					<?php if (LANG == 'de'): ?>
					<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
						<p>
							<input type="hidden" name="cmd" value="_xclick" />
							<input type="hidden" name="business" value="milianwolff@gmx.net" />
							<input type="hidden" name="item_name" value="Markdownify" />
							<input type="hidden" name="buyer_credit_promo_code" value="" />
							<input type="hidden" name="buyer_credit_product_category" value="" />
							<input type="hidden" name="buyer_credit_shipping_method" value="" />
							<input type="hidden" name="buyer_credit_user_address_change" value="" />
							<input type="hidden" name="no_shipping" value="0" />
							<input type="hidden" name="no_note" value="1" />
							<input type="hidden" name="currency_code" value="EUR" />
							<input type="hidden" name="tax" value="0" />
							<input type="hidden" name="lc" value="DE" />
							<input type="hidden" name="bn" value="PP-DonationsBF" />
							<input type="image" src="https://www.paypal.com/de_DE/i/btn/x-click-but04.gif" name="submit" title="<?php echo $this->get('donate'); ?>" alt="<?php echo $this->get('donate'); ?>" />
						</p>
					</form>
					<?php else: ?>
					<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
						<p>
							<input type="hidden" name="cmd" value="_xclick" />
							<input type="hidden" name="business" value="milianwolff@gmx.net" />
							<input type="hidden" name="item_name" value="Markdownify" />
							<input type="hidden" name="buyer_credit_promo_code" value="" />
							<input type="hidden" name="buyer_credit_product_category" value="" />
							<input type="hidden" name="buyer_credit_shipping_method" value="" />
							<input type="hidden" name="buyer_credit_user_address_change" value="" />
							<input type="hidden" name="no_shipping" value="0" />
							<input type="hidden" name="no_note" value="1" />
							<input type="hidden" name="currency_code" value="USD" />
							<input type="hidden" name="tax" value="0" />
							<input type="hidden" name="lc" value="US" />
							<input type="hidden" name="bn" value="PP-DonationsBF" />
							<input type="image" src="https://www.paypal.com/en_US/i/btn/x-click-but04.gif" name="submit" title="<?php echo $this->get('donate'); ?>" alt="<?php echo $this->get('donate'); ?>" />
						</p>
					</form>
					<?php endif; ?>
				</li>
			</ul>
			<?php echo $this->renderPart('content'); ?>
			<div id="footer"><?php echo $this->get('footer'); ?></div>
		</div>
	</body>
</html>