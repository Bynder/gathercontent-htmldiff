<?php

namespace GatherContent\Htmldiff;

use GatherContent\Htmldiff\ProcessorInterface;

class Processor implements ProcessorInterface
{
	/**
	 * Prepare HTML input
	 * @param string $html
	 * @return array
	 */
	public function prepareHtmlInput($html)
	{
		$config = [
			'wrap' => false,
			'show-body-only' => true,
		];
		$tidyNode = tidy_parse_string($html, $config, 'utf8')->body();
		$htmlArray = $this->toArray($tidyNode);
		$html = implode("\n", $htmlArray);
		return $html;
	}
	
	/**
	 * Convert a tidy node to an array
	 * @param \tidyNode $tidyNode
	 * @param string $prefix
	 * @return array
	 */
	private function toArray(\tidyNode $tidyNode, $prefix = '')
	{
		$result = [];
		if (trim($tidyNode->name) !== '') {
			$attributesString = '';
			if (is_array($tidyNode->attribute) && count($tidyNode->attribute) > 0) {
				foreach ($tidyNode->attribute as $name => $value) {
					$attributesString .= ' '.$name.'="'.$value.'"';
				}
			}
			$prefix .= '<'.$tidyNode->name.$attributesString.'>';
			$result[] = $prefix.'start';
			if ($tidyNode->hasChildren()) {
				foreach ($tidyNode->child as $childNode) {
					$tokenized = $this->toArray($childNode, $prefix);
					$result = array_merge($result, $tokenized);
				}
			}
			$result[] = $prefix.'end';

		} else {
			if (trim($tidyNode->value) !== '') {
				$words = explode(' ', trim($tidyNode->value));
				foreach ($words as $word) {
					if ($word !== '') {
						$result[] = $prefix.'"'.$word.'"';
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Prepare HTML output
	 * @param string $diff
	 * @return string HTML code
	 */
	public function prepareHtmlOutput($diff)
	{
		$html = '';
		
		$lines = explode("\n", $diff);
		foreach ($lines as $line) {
			$lineParts = [];
			if (preg_match('/([+\-\s])(<.*>)(.*)/', $line, $lineParts)) {
				$type = $lineParts[1];
				$path = $lineParts[2];
				$leaf = $lineParts[3];

				if ($leaf == 'start') {
					$html .= $this->openTag($path);

				} elseif ($leaf == 'end') {
					$html .= $this->closeTag($path);

				} else {
					$html .= $this->prepareLeaf($leaf, $type);
				}
			}
		}

		$html = $this->cleanup($html);
		
		return $html;
	}

	private function openTag($path)
	{
		$tags = [];
		preg_match_all('/<[^>]*>/', $path, $tags);
		return end($tags[0]);
	}

	private function closeTag($path)
	{
		$openTag = $this->openTag($path);
		$output = [];
		preg_match("/<([^\s>]*)/", $openTag, $output);
		$tagName = $output[1];
		return '</'.$tagName.'> ';
	}

	private function prepareLeaf($leaf, $type)
	{
		$realLeaf = substr($leaf, 1, -1);

		if ($type == '+') {
			return '<ins>'.$realLeaf.'</ins> ';

		} elseif ($type == '-') {
			return '<del>'.$realLeaf.'</del> ';

		} else {
			return $realLeaf.' ';
		}
	}

	private function cleanup($html)
	{
		$html = preg_replace('/\s+<\/(ins|del)/', '</\1', $html);
		$html = preg_replace('/(ins|del)> <(ins|del)/', '\1><\2', $html);

		$html = str_replace(array('</ins><ins>', '</del><del>'), ' ', $html);
		$html = str_replace(array('<body>', '</body>', '<ins></ins>', '<del></del>'), '', $html);

		$singletonClosingTags = [
			'</area>',
			'</base>',
			'</br>',
			'</col>',
			'</embed>',
			'</hr>',
			'</img>',
			'</input>',
			'</keygen>',
			'</link>',
			'</menuitem>',
			'</meta>',
			'</param>',
			'</source>',
			'</track>',
			'</wbr>'
		];
		$html = str_replace($singletonClosingTags, '', $html);
		
		return trim($html);
	}
}
