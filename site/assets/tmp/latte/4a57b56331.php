<?php

use Latte\Runtime as LR;

/** source: template.latte */
final class Template4a57b56331 extends Latte\Runtime\Template
{

	public function main(): array
	{
		extract($this->params);
		echo '<div>

';
		if ($items) /* line 4 */ {
			echo '    <ul>                
    ';
			$iterations = 0;
			foreach ($items as $item) /* line 5 */ {
				echo '        <li>';
				echo LR\Filters::escapeHtmlText(($this->filters->capitalize)($item)) /* line 6 */;
				echo '</li>   
';
				$iterations++;
			}
			echo '    </ul>';
		}
		echo '</div>';
		return get_defined_vars();
	}


	public function prepare(): void
	{
		extract($this->params);
		if (!$this->getReferringTemplate() || $this->getReferenceType() === "extends") {
			foreach (array_intersect_key(['item' => '5'], $this->params) as $ʟ_v => $ʟ_l) {
				trigger_error("Variable \$$ʟ_v overwritten in foreach on line $ʟ_l");
			}
		}
		
	}

}
