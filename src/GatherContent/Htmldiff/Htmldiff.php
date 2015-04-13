<?php

namespace GatherContent\Htmldiff;

use GatherContent\Htmldiff\ProcessorInterface;
use GatherContent\Htmldiff\DifferInterface;

use cogpowered\FineDiff\Diff;
use cogpowered\FineDiff\Granularity;
use cogpowered\FineDiff\Render\Text as TextRenderer;

class Htmldiff
{
	private $processor;
	private $differ;

	public function __construct(ProcessorInterface $processor = null, DifferInterface $differ = null)
	{
		$this->processor = $processor ?: new Processor;
		$this->differ    = $differ ?: new Differ;

		$this->checkForTidy();
	}

	private function checkForTidy()
	{
		if (!extension_loaded('tidy')) {
			throw new \Exception('Required Tidy extension not found.', 1);
		}
	}
	
	/**
	 * Get differences opcodes between the old and the new text
	 * @param string $old
	 * @param string $new
	 * @param GranularityInterface $granularity
	 * @return string
	 */
	public function getOpcodes($old, $new, Granularity\GranularityInterface $granularity = null)
	{
		$granularity = $granularity ?: new Granularity\Word;
		$differ = new Diff($granularity);
		return $differ->getOpcodes($old, $new);
	}

	/**
	 * Get the Git formatted differences string between the old and the new text
	 * @param string $old
	 * @param string $new
	 * @return string Differences patch (looks like Git's diff)
	 */
	public function diff($old, $new)
	{
		$old = $this->processor->prepareHtmlInput($old);
		$new = $this->processor->prepareHtmlInput($new);

		return $this->differ->diff($old, $new);
	}
	
	/**
	 * Process a string with the opcodes
	 * @param string $old
	 * @param string $opcodes
	 * @return string Processed string
	 */
	public function process($old, $opcodes)
	{
		$renderer = new TextRenderer;
		return $renderer->process($old, $opcodes);
	}
	
	/**
	 * Render the differences opcodes 
	 * @param string $diff Differences patch (looks like Git's diff)
	 * @return string HTML code
	 */
	public function render($diff)
	{
		return $this->processor->prepareHtmlOutput($diff);
	}
}
