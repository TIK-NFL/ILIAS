<?php

declare(strict_types=1);

namespace USTUTT;

use ILIAS\Component;

class RootFiles implements Component\Component
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
                return "components/USTUTT/ECSredi/resources/ecsredi.php";
                return "components/USTUTT/ECSredi/resources/apctest.php";
                return "components/USTUTT/ECSredi/resources/opcache.php";
                return "components/USTUTT/ECSredi/resources/robots.txt";
            }
            public function getTarget(): string
            {
                return "ecsredi.php";
                return "apctest.php";
                return "opcache.php";
                return "robots.txt";
            }
        };
    }
}