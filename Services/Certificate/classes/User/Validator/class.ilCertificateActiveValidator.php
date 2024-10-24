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

/**
 * @author  Niels Theen <ntheen@databay.de>
 */
class ilCertificateActiveValidator
{
    private readonly ilSetting $setting;
    private readonly ilRPCServerSettings $rpcSettings;

    public function __construct(?ilSetting $setting = null, ?ilRPCServerSettings $rpcSettings = null)
    {
        if (null === $setting) {
            $setting = new ilSetting("certificate");
        }
        $this->setting = $setting;

        if (null === $rpcSettings) {
            $rpcSettings = ilRPCServerSettings::getInstance();
        }
        $this->rpcSettings = $rpcSettings;
    }

    public function validate(): bool
    {
        $globalCertificateActive = (bool) $this->setting->get('active', '0');

        if (!$globalCertificateActive) {
            return false;
        }

        return $this->rpcSettings->isEnabled();
    }
}
