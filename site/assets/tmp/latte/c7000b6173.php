<?php

use Latte\Runtime as LR;

/** source: trees.latte */
final class Templatec7000b6173 extends Latte\Runtime\Template
{

	public function main(): array
	{
		extract($this->params);
		echo '<p>Ahoj</p>';
		return get_defined_vars();
	}

}
