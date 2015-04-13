<?php

namespace GatherContent\Htmldiff;

use GatherContent\Htmldiff\DifferInterface;
use GatherContent\Htmldiff\GitDiffRenderer;

use cogpowered\FineDiff\Diff;
use cogpowered\FineDiff\Granularity\Paragraph as ParagraphGranularity;

class Differ implements DifferInterface
{
	private $diff;

	/**
	 * Constructor
	 * @param Diff $diff
	 */
	public function __construct(Diff $diff = null)
	{
		$this->diff = $diff ?: new Diff(new ParagraphGranularity, new GitDiffRenderer);
	}
	
	/**
	 * Get differences opcodes between the old and the new text
	 * @param string $old Old text
	 * @param string $new New text
	 * @return \cogpowered\FineDiff\Parser\Opcodes
	 */
	public function getOpcodes($old, $new)
	{
		return $this->diff->getOpcodes($old, $new);
	}

	/**
	 * Get the Git formatted differences string between the old and the new text
	 * @param string $old Old text
	 * @param string $new New text
	 * @return string
	 */
	public function diff($old, $new)
	{
		return $this->diff->render($old, $new);
	}
}
