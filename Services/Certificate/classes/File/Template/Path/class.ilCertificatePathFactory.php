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

class ilCertificatePathFactory
{
    public function create(ilObject $object): string
    {
        $type = $object->getType();

        return match ($type) {
            'tst' => ilCertificatePathConstants::TEST_PATH . $object->getId() . '/',
            'crs' => ilCertificatePathConstants::COURSE_PATH . $object->getId() . '/',
            'sahs' => ilCertificatePathConstants::SCORM_PATH . $object->getId() . '/',
            'exc' => ilCertificatePathConstants::EXERCISE_PATH . $object->getId() . '/',
            'lti' => ilCertificatePathConstants::LTICON_PATH . $object->getId() . '/',
            'cmix' => ilCertificatePathConstants::CMIX_PATH . $object->getId() . '/',
            'prg' => ilCertificatePathConstants::STUDY_PROGRAMME_PATH . $object->getId() . '/',
            default => throw new ilException(sprintf(
                'The type "%s" is currently not supported for certificates',
                $type
            )),
        };
    }
}
