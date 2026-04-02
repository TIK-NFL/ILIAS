<?php

declare(strict_types=1);

namespace USTUTT;

use ILIAS\Component;

class APCtest implements Component\Component
{
    public function init(
        array | \ArrayAccess &$define,
        array | \ArrayAccess &$implement,
        array | \ArrayAccess &$use,
        array | \ArrayAccess &$contribute,
        array | \ArrayAccess &$seek,
        array | \ArrayAccess &$provide,
        array | \ArrayAccess &$pull,
        array | \ArrayAccess &$internal,
    ): void {
        $contribute[Component\Resource\PublicAsset::class] = static fn() => new class () implements Component\Resource\PublicAsset {
            public function getSource(): string
            {
                return "components/USTUTT/APCtest/resources/apctest.php";
            }
            public function getTarget(): string
            {
                return "apctest.php";
            }
        };
    }
}