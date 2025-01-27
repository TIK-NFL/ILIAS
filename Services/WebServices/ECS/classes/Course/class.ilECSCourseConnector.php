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
 */

declare(strict_types=1);

/**
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 */
class ilECSCourseConnector extends ilECSConnector
{
    /**
     * Get single directory tree
     * @return mixed an array of ecs cms directory tree entries
     */
    public function getCourse($course_id, $a_details = false)
    {
        $this->path_postfix = '/campusconnect/courses/' . (int) $course_id;

        if ($a_details && $course_id) {
            $this->path_postfix .= '/details';
        }

        $this->logger->debug('before preparing');
        try {
            $this->prepareConnection();
            $this->setHeader([]);
            if ($a_details) {
                $this->addHeader('Accept', 'application/json');
            } else {
                $this->addHeader('Accept', 'text/uri-list');
            }
            $this->curl->setOpt(CURLOPT_HTTPHEADER, $this->getHeader());
        $this->logger->debug('before call');
            $res = $this->call();
        $this->logger->debug('after call get result');

            if (strpos($res, 'http') === 0) {
                $json = file_get_contents($res);
                $ecs_result = new ilECSResult($json);
            } else {
                $ecs_result = new ilECSResult($res);
            }

            $this->logger->debug('got result');
            // Return ECSEContentDetails for details switch
            if ($a_details) {
                $details = new ilECSEContentDetails();
                $details->loadFromJson($ecs_result->getResult());
                $this->logger->debug('returning details');
                return $details;
            }
            // Return json result
            $this->logger->debug('returning result');
            return $ecs_result->getResult();
        } catch (ilCurlConnectionException $e) {
            $this->logger->error('Error calling ECS service: ' . $e->getMessage());
            throw new ilECSConnectorException('Error calling ECS service: ' . $e->getMessage());
        }
    }
}
