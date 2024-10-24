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
 * Meta Data class (element contribute)
 * @author  Stefan Meyer <meyer@leifos.com>
 * @package ilias-core
 * @version $Id$
 */
class ilMDContribute extends ilMDBase
{
    /**
     * Compatibility fix for legacy MD classes for new db tables
     */
    private const ROLE_TRANSLATION = [
        'author' => 'Author',
        'publisher' => 'Publisher',
        'unknown' => 'Unknown',
        'initiator' => 'Initiator',
        'terminator' => 'Terminator',
        'editor' => 'Editor',
        'graphical designer' => 'GraphicalDesigner',
        'technical implementer' => 'TechnicalImplementer',
        'content provider' => 'ContentProvider',
        'technical validator' => 'TechnicalValidator',
        'educational validator' => 'EducationalValidator',
        'script writer' => 'ScriptWriter',
        'instructional designer' => 'InstructionalDesigner',
        'subject matter expert' => 'SubjectMatterExpert',
        'creator' => 'Creator',
        'validator' => 'Validator'
    ];

    // Subelements
    private string $date = '';
    private string $role = '';

    /**
     * @return int[]
     */
    public function getEntityIds(): array
    {
        return ilMDEntity::_getIds($this->getRBACId(), $this->getObjId(), (int) $this->getMetaId(), 'meta_contribute');
    }

    public function getEntity(int $a_entity_id): ?ilMDEntity
    {
        if (!$a_entity_id) {
            return null;
        }
        $ent = new ilMDEntity();
        $ent->setMetaId($a_entity_id);

        return $ent;
    }

    public function addEntity(): ilMDEntity
    {
        $ent = new ilMDEntity($this->getRBACId(), $this->getObjId(), $this->getObjType());
        $ent->setParentId($this->getMetaId());
        $ent->setParentType('meta_contribute');

        return $ent;
    }

    // SET/GET
    public function setRole(string $a_role): bool
    {
        switch ($a_role) {
            case 'Author':
            case 'Publisher':
            case 'Unknown':
            case 'Initiator':
            case 'Terminator':
            case 'Editor':
            case 'GraphicalDesigner':
            case 'TechnicalImplementer':
            case 'ContentProvider':
            case 'TechnicalValidator':
            case 'EducationalValidator':
            case 'ScriptWriter':
            case 'InstructionalDesigner':
            case 'SubjectMatterExpert':
            case 'Creator':
            case 'Validator':
            case 'PointOfContact':
                $this->role = $a_role;
                return true;

            default:
                return false;
        }
    }

    public function getRole(): string
    {
        return $this->role;
    }

    public function setDate(string $a_date): void
    {
        $this->date = $a_date;
    }

    public function getDate(): string
    {
        return $this->date;
    }

    public function save(): int
    {
        $fields = $this->__getFields();
        $fields['meta_contribute_id'] = array('integer', $next_id = $this->db->nextId('il_meta_contribute'));

        if ($this->db->insert('il_meta_contribute', $fields)) {
            $this->setMetaId($next_id);
            return $this->getMetaId();
        }
        return 0;
    }

    public function update(): bool
    {
        return $this->getMetaId() && $this->db->update(
            'il_meta_contribute',
            $this->__getFields(),
            array("meta_contribute_id" => array('integer', $this->getMetaId()))
        );
    }

    public function delete(): bool
    {
        if ($this->getMetaId()) {
            $query = "DELETE FROM il_meta_contribute " .
                "WHERE meta_contribute_id = " . $this->db->quote($this->getMetaId(), 'integer');
            $res = $this->db->manipulate($query);

            foreach ($this->getEntityIds() as $id) {
                $ent = $this->getEntity($id);
                $ent->delete();
            }
            return true;
        }
        return false;
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function __getFields(): array
    {
        /**
         * Compatibility fix for legacy MD classes for new db tables
         */
        $role = (string) array_search(
            $this->getRole(),
            self::ROLE_TRANSLATION
        );

        return array(
            'rbac_id' => array('integer', $this->getRBACId()),
            'obj_id' => array('integer', $this->getObjId()),
            'obj_type' => array('text', $this->getObjType()),
            'parent_type' => array('text', $this->getParentType()),
            'parent_id' => array('integer', $this->getParentId()),
            'role' => array('text', $role),
            'c_date' => array('text', $this->getDate())
        );
    }

    public function read(): bool
    {
        if ($this->getMetaId()) {
            $query = "SELECT * FROM il_meta_contribute " .
                "WHERE meta_contribute_id = " . $this->db->quote($this->getMetaId(), 'integer');

            $res = $this->db->query($query);
            while ($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT)) {
                /**
                 * Compatibility fix for legacy MD classes for new db tables
                 */
                if (key_exists($row->role ?? '', self::ROLE_TRANSLATION)) {
                    $row->role = self::ROLE_TRANSLATION[$row->role ?? ''];
                }

                $this->setRBACId((int) $row->rbac_id);
                $this->setObjId((int) $row->obj_id);
                $this->setObjType($row->obj_type ?? '');
                $this->setParentId((int) $row->parent_id);
                $this->setParentType($row->parent_type ?? '');
                $this->setRole($row->role ?? '');
                $this->setDate($row->c_date ?? '');
            }
        }
        return true;
    }

    public function toXML(ilXmlWriter $writer): void
    {
        $writer->xmlStartTag('Contribute', array(
            'Role' => $this->getRole() ?: 'Author'
        ));

        // Entities
        $entities = $this->getEntityIds();
        foreach ($entities as $id) {
            $ent = $this->getEntity($id);
            $ent->toXML($writer);
        }
        if (!count($entities)) {
            $ent = new ilMDEntity($this->getRBACId(), $this->getObjId());
            $ent->toXML($writer);
        }

        $writer->xmlElement('Date', null, $this->getDate());
        $writer->xmlEndTag('Contribute');
    }

    // STATIC

    /**
     * @return int[]
     */
    public static function _getIds(int $a_rbac_id, int $a_obj_id, int $a_parent_id, string $a_parent_type): array
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];

        $query = "SELECT meta_contribute_id FROM il_meta_contribute " .
            "WHERE rbac_id = " . $ilDB->quote($a_rbac_id, 'integer') . " " .
            "AND obj_id = " . $ilDB->quote($a_obj_id, 'integer') . " " .
            "AND parent_id = " . $ilDB->quote($a_parent_id, 'integer') . " " .
            "AND parent_type = " . $ilDB->quote($a_parent_type, 'text');

        $res = $ilDB->query($query);
        $ids = [];
        while ($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT)) {
            $ids[] = (int) $row->meta_contribute_id;
        }
        return $ids;
    }

    /**
     * @return string[]
     */
    public static function _lookupAuthors(int $a_rbac_id, int $a_obj_id, string $a_obj_type): array
    {
        global $DIC;

        $ilDB = $DIC['ilDB'];

        // Ask for 'author' later to use indexes
        $authors = [];
        $query = "SELECT entity,ent.parent_type,role FROM il_meta_entity ent " .
            "JOIN il_meta_contribute con ON ent.parent_id = con.meta_contribute_id " .
            "WHERE  ent.rbac_id = " . $ilDB->quote($a_rbac_id, 'integer') . " " .
            "AND ent.obj_id = " . $ilDB->quote($a_obj_id, 'integer') . " ";
        $res = $ilDB->query($query);
        while ($row = $res->fetchRow(ilDBConstants::FETCHMODE_OBJECT)) {
            if ($row->role === 'Author' && $row->parent_type === 'meta_contribute') {
                $authors[] = trim($row->entity);
            }
        }
        return $authors;
    }
}
