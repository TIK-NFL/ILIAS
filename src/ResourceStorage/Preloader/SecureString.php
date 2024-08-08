<?php

/**
 * This file is part of ILIAS, a powerful learning management system
 * published by ILIAS open source e-Learning e.V.
 *
 * ILIAS is licensed with the GPL-3.0,
 * see https://www.gnu.org/licenses/gpl-3.0.en.html
 * You should have received a copy of said license along with the
 * source code, too.
 *
 * If this is not the case or you just want to try ILIAS, you'll find
 * us at:
 * https://www.ilias.de
 * https://github.com/ILIAS-eLearning
 *
 *********************************************************************/

declare(strict_types=1);

namespace ILIAS\ResourceStorage\Preloader;

/**
 * Trait SecureString
 *
 * @internal
 * @author Fabian Schmid <fabian@sr.solutions>
 */
trait SecureString
{
	protected function secure(string $string): string
	{
		// Normalize UTF-8 string, remove any invalid sequences
		$string = mb_convert_encoding($string, 'UTF-8', 'UTF-8');
	
		// Remove invalid UTF-8 sequences that could be 4-byte UTF-8 (utf8mb4)
		$string = preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $string);
	
		// Remove control characters
		$string = preg_replace('#\p{C}+#u', '', $string);
		if ($string === null) {
			throw new \RuntimeException('Failed to remove control characters from string. ' . preg_last_error_msg());
		}
	
		// Sanitize by stripping HTML tags and encoding special characters
		return htmlspecialchars(
			strip_tags($string),
			ENT_QUOTES,
			'UTF-8',
			false
		);
	}
}
