<?php
/**
 * Copyright 2014 Infield Design
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License .
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied .
 * See the License for the specific language governing permissions and
 * limitations under the License .
 */


/**
 * A content extractor to get clean, indexable content from HTML markup.
 *
 * @category Asm
 * @package Asm_Solr
 * @author Ingo Renner <ingo@infielddesign.com>
 */
class Asm_Solr_Helper_ContentExtractor
{
	/**
	 * Unicode ranges which should get stripped before sending a document to solr.
	 * This is necessary if a document (PDF, etc.) contains unicode characters which
	 * are valid in the font being used in the document but are not available in the
	 * font being used for displaying results.
	 *
	 * This is often the case if PDFs are being indexed where special fonts are used
	 * for displaying bullets, etc. Usually those bullets reside in one of the unicode
	 * "Private Use Zones" or the "Private Use Area" (plane 15 + 16)
	 *
	 * @see http://en.wikipedia.org/wiki/Unicode_block
	 * @var array
	 */
	protected $stripUnicodeRanges = array(
		array('FFFD', 'FFFD'), // Replacement Character (�) @see http://en.wikipedia.org/wiki/Specials_%28Unicode_block%29
		array('E000', 'F8FF'), // Private Use Area (part of Plane 0)
		array('F0000', 'FFFFF'), // Supplementary Private Use Area (Plane 15)
		array('100000', '10FFFF'), // Supplementary Private Use Area (Plane 16)
	);


	/**
	 * Returns the cleaned indexable content from HTML markup.
	 *
	 * The content is cleaned from HTML tags and control chars Solr could
	 * stumble on.
	 *
	 * @param string $content Content to clean for indexing
	 * @return string Indexable, cleaned content ready for indexing.
	 */
	public function getIndexableContent($content) {
		$content = $this->cleanContent($content);
		$content = html_entity_decode($content, ENT_QUOTES, 'UTF-8');
		// after entity decoding we might have tags again
		$content = strip_tags($content);
		$content = trim($content);

		return $content;
	}

	/**
	 * Strips control characters that cause Jetty/Solr to fail.
	 *
	 * @param string $content The content to sanitize
	 * @return string The sanitized content
	 * @see http://w3.org/International/questions/qa-forms-utf-8.html
	 */
	public function stripControlCharacters($content) {
		// Printable utf-8 does not include any of these chars below x7F
		return preg_replace('@[\x00-\x08\x0B\x0C\x0E-\x1F]@', ' ', $content);
	}

	/**
	 * Strips a UTF-8 character range
	 *
	 * @param string $content Content to sanitize
	 * @param string $start Unicode range start character as uppercase hexadecimal string
	 * @param string $end Unicode range end character as uppercase hexadecimal string
	 * @return string Sanitized content
	 */
	public function stripUnicodeRange($content, $start, $end) {
		return preg_replace('/[\x{' . $start . '}-\x{' . $end . '}]/u', '', $content);
	}

	/**
	 * Strips unusable unicode ranges
	 *
	 * @param string $content Content to sanitize
	 * @return string Sanitized content
	 */
	public function stripUnicodeRanges($content) {
		foreach ($this->stripUnicodeRanges as $range) {
			$content = $this->stripUnicodeRange($content, $range[0], $range[1]);
		}

		return $content;
	}

	/**
	 * Strips html tags, and tab, new-line, carriage-return, &nbsp; whitespace
	 * characters.
	 *
	 * @param string $content String to clean
	 * @return string String cleaned from tags and special whitespace characters
	 */
	public function cleanContent($content) {
		$content = $this->stripControlCharacters($content);

		// remove Javascript
		$content = preg_replace('@<script[^>]*>.*?<\/script>@msi', '', $content);

		// remove internal CSS styles
		$content = preg_replace('@<style[^>]*>.*?<\/style>@msi', '', $content);

		// prevents concatenated words when stripping tags afterwards
		$content = str_replace(array('<', '>'), array(' <', '> '), $content);
		$content = strip_tags($content);
		$content = str_replace(array("\t", "\n", "\r", '&nbsp;'), ' ', $content);
		$content = $this->stripUnicodeRanges($content);
		$content = trim($content);

		return $content;
	}
}