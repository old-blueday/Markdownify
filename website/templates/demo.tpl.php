		<h2><?php echo $this->get('demoTitle'); ?></h2>
		<form action="demo.php" method="post">
			<fieldset id="input">
				<legend><?php echo $this->get('Input'); ?></legend>
				<textarea cols="80" rows="30" name="input"><?php echo $this->get('inputForm'); ?></textarea>
			</fieldset>
			<fieldset id="options">
				<legend><?php echo $this->get('demo_options'); ?></legend>
				<ul>
					<li><?php echo $this->checkbox($this->get('extra'), 'extra', false); ?></li>
					<li><?php echo $this->checkbox($this->get('leap'), 'leap', false); ?></li>
					<li><?php echo $this->checkbox($this->get('keepHTML'), 'keepHTML', true); ?></li>
				</ul>
			</fieldset>
			<fieldset id="buttons">
				<p>
					<input name="submit" value="<?php echo $this->get('convertLabel'); ?>" type="submit" />
				</p>
			</fieldset>
		</form>
		<?php if ($this->get('input')): ?>
		<table>
			<tr>
				<th class="third"><?php echo $this->get('htmlInput'); ?></th>
				<th class="third"><?php echo $this->get('mdParsed'); ?></th>
				<th class="third"><?php echo $this->get('htmlOutput'); ?></th>
			</tr>
			<tr>
				<td class="third"><pre><code><?php echo $this->get('input'); ?></code></pre></td>
				<td class="third"><pre><code><?php echo $this->get('parsed'); ?></code></pre></td>
				<td class="third"><pre><code><?php echo $this->get('output'); ?></code></pre></td>
			</tr>
		</table>
		<?php endif; ?>