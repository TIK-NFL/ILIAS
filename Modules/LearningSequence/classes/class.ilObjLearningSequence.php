<?php

declare(strict_types=1);

/**
 * Class ilObjLearningSequence
 *
 */
class ilObjLearningSequence extends ilContainer
{
    const OBJ_TYPE = 'lso';

    const E_CREATE = 'create';
    const E_UPDATE = 'update';
    const E_DELETE = 'delete';

    /**
     * @var ilLSItemsDB
     */
    protected $items_db;

    /**
     * @var ilLSPostConditionDB
     */
    protected $conditions_db;

    /**
     * @var ilLearnerProgressDB
     */
    protected $learner_progress_db;

    /**
     * @var ilLearningSequenceParticipant
     */
    protected $ls_participant;

    /**
     * @var ilLearningSequenceSettings
     */
    protected $ls_settings;

    /**
     * @var ilLSStateDB
     */
    protected $state_db;

    /**
     * @var LSRoles
     */
    protected $ls_roles;

    /*
     * @var ilLearningSequenceSettingsDB
     */
    protected $settings_db;

    /*
     * @var ilLearningSequenceSettingsDB
     */
    protected $activation_db;

    /*
     * @var ilLearningSequenceActivation
     */
    protected $ls_activation;


    public function __construct(int $id = 0, bool $call_by_reference = true)
    {
        global $DIC;
        $this->dic = $DIC;

        $this->type = self::OBJ_TYPE;
        $this->lng = $DIC['lng'];
        $this->ctrl = $DIC['ilCtrl'];
        $this->user = $DIC['ilUser'];
        $this->tree = $DIC['tree'];
        $this->log = $DIC["ilLoggerFactory"]->getRootLogger();
        $this->rbacadmin = $DIC['rbacadmin'];
        $this->app_event_handler = $DIC['ilAppEventHandler'];
        $this->il_news = $DIC->news();
        $this->il_condition_handler = new ilConditionHandler();

        parent::__construct($id, $call_by_reference);
    }

    public static function getInstanceByRefId(int $ref_id)
    {
        return ilObjectFactory::getInstanceByRefId($ref_id, false);
    }

    public function read()
    {
        $this->getLSSettings();
        if ($this->getRefId()) {
            $this->getLSActivation();
        }
        parent::read();
    }

    public function create() : int
    {
        $id = parent::create();
        if (!$id) {
            return 0;
        }
        $this->raiseEvent(self::E_CREATE);

        return (int) $this->getId();
    }

    public function update() : bool
    {
        if (!parent::update()) {
            return false;
        }
        $this->raiseEvent(self::E_UPDATE);

        return true;
    }

    public function delete() : bool
    {
        if (!parent::delete()) {
            return false;
        }

        ilLearningSequenceParticipants::_deleteAllEntries($this->getId());
        $this->getSettingsDB()->delete((int) $this->getId());
        $this->getStateDB()->deleteFor((int) $this->getRefId());
        $this->getActivationDB()->deleteForRefId((int) $this->getRefId());

        $this->raiseEvent(self::E_DELETE);

        return true;
    }

    protected function raiseEvent(string $event_type)
    {
        $this->app_event_handler->raise(
            'Modules/LearningSequence',
            $event_type,
            array(
                'obj_id' => $this->getId(),
                'appointments' => null
            )
        );
    }

    public function cloneObject($target_id, $copy_id = 0, $omit_tree = false)
    {
        $new_obj = parent::cloneObject($target_id, $copy_id, $omit_tree);

        $this->cloneAutoGeneratedRoles($new_obj);
        $this->cloneMetaData($new_obj);
        $this->cloneSettings($new_obj);
        $this->cloneLPSettings((int) $new_obj->getId());
        $this->cloneActivation($new_obj, (int) $copy_id);

        $roles = $new_obj->getLSRoles();
        $roles->addLSMember(
            (int) $this->user->getId(),
            $roles->getDefaultAdminRole()
        );
        return $new_obj;
    }


    protected function cloneAutoGeneratedRoles(ilObjLearningSequence $new_obj) : bool
    {
        $admin = $this->getDefaultAdminRole();
        $new_admin = $new_obj->getDefaultAdminRole();

        if (!$admin || !$new_admin || !$this->getRefId() || !$new_obj->getRefId()) {
            $this->log->write(__METHOD__ . ' : Error cloning auto generated role: il_lso_admin');
        }

        $this->rbacadmin->copyRolePermissions($admin, $this->getRefId(), $new_obj->getRefId(), $new_admin, true);
        $this->log->write(__METHOD__ . ' : Finished copying of role lso_admin.');

        $member = $this->getDefaultMemberRole();
        $new_member = $new_obj->getDefaultMemberRole();

        if (!$member || !$new_member) {
            $this->log->write(__METHOD__ . ' : Error cloning auto generated role: il_lso_member');
        }

        $this->rbacadmin->copyRolePermissions($member, $this->getRefId(), $new_obj->getRefId(), $new_member, true);
        $this->log->write(__METHOD__ . ' : Finished copying of role lso_member.');

        return true;
    }

    protected function cloneSettings(ilObjLearningSequence $new_obj)
    {
        $source = $this->getLSSettings();
        $target = $new_obj->getLSSettings();

        foreach ($source->getUploads() as $key => $upload_info) {
            $target = $target->withUpload($upload_info, $key);
        }

        foreach ($source->getDeletions() as $deletion) {
            $target = $target->withDeletion($deletion);
        }

        $target = $target
            ->withAbstract($source->getAbstract())
            ->withExtro($source->getExtro())
            ->withAbstractImage($source->getAbstractImage())
            ->withExtroImage($source->getExtroImage())
        ;

        $new_obj->updateSettings($target);
    }

    protected function cloneLPSettings(int $obj_id)
    {
        $lp_settings = new ilLPObjSettings($this->getId());
        $lp_settings->cloneSettings($obj_id);
    }

    protected function cloneActivation(ilObjLearningSequence $new_obj, int $a_copy_id) : void
    {
        // #14596
        $cwo = ilCopyWizardOptions::_getInstance($a_copy_id);
        if ($cwo->isRootNode($this->getRefId())) {
            $activation = $new_obj->getLSActivation()->withIsOnline(false);
        } else {
            $activation = $new_obj->getLSActivation()
                ->withIsOnline($this->getLSActivation()->getIsOnline())
                ->withActivationStart($this->getLSActivation()->getActivationStart())
                ->withActivationEnd($this->getLSActivation()->getActivationEnd());
        }

        $new_obj->getActivationDB()->store(
            $activation
        );
    }

    protected function getDIC() : \ArrayAccess
    {
        return $this->dic;
    }

    public function getDI() : \ArrayAccess
    {
        if (is_null($this->di)) {
            $di = new ilLSDI();
            $di->init($this->getDIC());
            $this->di = $di;
        }
        return $this->di;
    }

    public function getLocalDI() : \ArrayAccess
    {
        if (is_null($this->local_di)) {
            $di = new ilLSLocalDI();
            $di->init(
                $this->getDIC(),
                $this->getDI(),
                new \ILIAS\Data\Factory(),
                $this
            );
            $this->local_di = $di;
        }
        return $this->local_di;
    }

    protected function getSettingsDB() : ilLearningSequenceSettingsDB
    {
        if (!$this->settings_db) {
            $this->settings_db = $this->getDI()['db.settings'];
        }
        return $this->settings_db;
    }

    protected function getActivationDB() : ilLearningSequenceActivationDB
    {
        if (!$this->activation_db) {
            $this->activation_db = $this->getDI()['db.activation'];
        }
        return $this->activation_db;
    }

    public function getLSActivation() : ilLearningSequenceActivation
    {
        if (!$this->ls_activation) {
            $this->ls_activation = $this->getActivationDB()->getActivationForRefId((int) $this->getRefId());
        }

        return $this->ls_activation;
    }

    public function updateActivation(ilLearningSequenceActivation $settings)
    {
        $this->getActivationDB()->store($settings);
        $this->ls_activation = $settings;
    }

    public function getLSSettings() : ilLearningSequenceSettings
    {
        if (!$this->ls_settings) {
            $this->ls_settings = $this->getSettingsDB()->getSettingsFor((int) $this->getId());
        }

        return $this->ls_settings;
    }

    public function updateSettings(ilLearningSequenceSettings $settings)
    {
        $this->getSettingsDB()->store($settings);
        $this->ls_settings = $settings;
    }

    protected function getLSItemsDB() : ilLSItemsDB
    {
        if (!$this->items_db) {
            $this->items_db = $this->getLocalDI()['db.lsitems'];
        }
        return $this->items_db;
    }

    protected function getPostConditionDB() : ilLSPostConditionDB
    {
        if (!$this->conditions_db) {
            $this->conditions_db = $this->getDI()["db.postconditions"];
        }
        return $this->conditions_db;
    }

    public function getLSParticipants() : ilLearningSequenceParticipants
    {
        if (!$this->ls_participant) {
            $this->ls_participant = $this->getLocalDI()['participants'];
        }

        return $this->ls_participant;
    }
    public function getMembersObject() //used by Services/Membership/classes/class.ilMembershipGUI.php
    {
        return $this->getLSParticipants();
    }

    public function getLSAccess() : ilObjLearningSequenceAccess
    {
        if (is_null($this->ls_access)) {
            $this->ls_access = new ilObjLearningSequenceAccess();
        }

        return $this->ls_access;
    }

    /**
     * Get a list of LSItems
     */
    public function getLSItems() : array
    {
        $db = $this->getLSItemsDB();
        return $db->getLSItems((int) $this->getRefId());
    }

    /**
     * Update LSItems
     * @param LSItem[]
     */
    public function storeLSItems(array $ls_items)
    {
        $db = $this->getLSItemsDB();
        $db->storeItems($ls_items);
    }

    /**
     * Delete post conditions for ref ids.
     * @param int[]
     */
    public function deletePostConditionsForSubObjects(array $ref_ids)
    {
        $rep_utils = new ilRepUtil();
        $rep_utils->deleteObjects($this->getRefId(), $ref_ids);
        $db = $this->getPostConditionDB();
        $db->delete($ref_ids);
    }

    /**
     * @return array<"value" => "option_text">
     */
    public function getPossiblePostConditionsForType(string $type) : array
    {
        $condition_types = $this->il_condition_handler->getOperatorsByTriggerType($type);
        $conditions = [
            $this->getPostConditionDB()::STD_ALWAYS_OPERATOR => $this->lng->txt('condition_always')
        ];
        foreach ($condition_types as $cond_type) {
            $conditions[$cond_type] = $this->lng->txt($cond_type);
        }
        return $conditions;
    }

    protected function getLearnerProgressDB() : ilLearnerProgressDB
    {
        if (!$this->learner_progress_db) {
            $this->learner_progress_db = $this->getLocalDI()['db.progress'];
        }
        return $this->learner_progress_db;
    }

    //protected function getStateDB(): ilLSStateDB
    public function getStateDB() : ilLSStateDB
    {
        if (!$this->state_db) {
            $this->state_db = $this->getDI()['db.states'];
        }
        return $this->state_db;
    }

    /**
     * Get a list of LSLearnerItems
     */
    public function getLSLearnerItems(int $usr_id) : array
    {
        $db = $this->getLearnerProgressDB();
        return $db->getLearnerItems($usr_id, $this->getRefId());
    }

    public function getLSRoles() : ilLearningSequenceRoles
    {
        if (!$this->ls_roles) {
            $this->ls_roles = $this->getLocalDI()['roles'];
        }
        return $this->ls_roles;
    }

    /**
     * Get mail to members type
     * @return int
     */
    public function getMailToMembersType()
    {
        return $this->mail_members;
    }

    /**
     * Goto target learning sequence.
     *
     * @param int $target
     * @param string $add
     */
    public static function _goto($target, $add = "")
    {
        global $DIC;

        $ilAccess = $DIC['ilAccess'];
        $ilErr = $DIC['ilErr'];
        $lng = $DIC['lng'];
        $ilUser = $DIC['ilUser'];

        if (substr($add, 0, 5) == 'rcode') {
            if ($ilUser->getId() == ANONYMOUS_USER_ID) {
                // Redirect to login for anonymous
                ilUtil::redirect(
                    "login.php?target=" . $_GET["target"] . "&cmd=force_login&lang=" .
                    $ilUser->getCurrentLanguage()
                );
            }

            // Redirects to target location after assigning user to learning sequence
            ilMembershipRegistrationCodeUtils::handleCode(
                $target,
                ilObject::_lookupType(ilObject::_lookupObjId($target)),
                substr($add, 5)
            );
        }

        if ($add == "mem" && $ilAccess->checkAccess("manage_members", "", $target)) {
            ilObjectGUI::_gotoRepositoryNode($target, "members");
        }

        if ($ilAccess->checkAccess("read", "", $target)) {
            ilObjectGUI::_gotoRepositoryNode($target);
        } else {
            // to do: force flat view
            if ($ilAccess->checkAccess("visible", "", $target)) {
                ilObjectGUI::_gotoRepositoryNode($target, "infoScreenGoto");
            } else {
                if ($ilAccess->checkAccess("read", "", ROOT_FOLDER_ID)) {
                    ilUtil::sendFailure(
                        sprintf(
                            $lng->txt("msg_no_perm_read_item"),
                            ilObject::_lookupTitle(ilObject::_lookupObjId($target))
                        ),
                        true
                    );
                    ilObjectGUI::_gotoRepositoryRoot();
                }
            }
        }

        $ilErr->raiseError($lng->txt("msg_no_perm_read"), $ilErr->FATAL);
    }

    public function getShowMembers()
    {
        return $this->getLSSettings()->getMembersGallery();
    }

    public function announceLSOOnline()
    {
        $ns = $this->il_news;
        $context = $ns->contextForRefId((int) $this->getRefId());
        $item = $ns->item($context);
        $item->setContentIsLangVar(true);
        $item->setContentTextIsLangVar(true);
        $item->setTitle("lso_news_online_title");
        $item->setContent("lso_news_online_txt");
        $news_id = $ns->data()->save($item);
    }
    public function announceLSOOffline()
    {
        //NYI
    }

    public function setEffectiveOnlineStatus(bool $status)
    {
        $act_db = $this->getActivationDB();
        $act_db->setEffectiveOnlineStatus((int) $this->getRefId(), $status);
    }

    /***************************************************************************
    * Role Stuff
    ***************************************************************************/
    public function getLocalLearningSequenceRoles(bool $translate = false) : array
    {
        return $this->getLSRoles()->getLocalLearningSequenceRoles($translate);
    }

    public function getDefaultMemberRole() : int
    {
        return $this->getLSRoles()->getDefaultMemberRole();
    }

    public function getDefaultAdminRole()
    {
        return $this->getLSRoles()->getDefaultAdminRole();
    }

    public function getLearningSequenceMemberData($a_mem_ids, $active = 1)
    {
        return $this->getLSRoles()->getLearningSequenceMemberData($a_mem_ids, $active);
    }

    public function getDefaultLearningSequenceRoles($a_grp_id = "")
    {
        return $this->getLSRoles()->getDefaultLearningSequenceRoles($a_grp_id);
    }

    public function initDefaultRoles()
    {
        return $this->getLSRoles()->initDefaultRoles();
    }

    public function readMemberData(array $user_ids, array $columns = null)
    {
        return $this->getLsRoles()->readMemberData($user_ids, $columns);
    }

    public function getParentObjectInfo(int $ref_id, array $search_types)
    {
        foreach ($this->tree->getPathFull($ref_id) as $hop) {
            if (in_array($hop['type'], $search_types)) {
                return $hop;
            }
        }
        return null;
    }

    public function getLPCompletionStates() : array
    {
        return [
            \ilLPStatus::LP_STATUS_COMPLETED_NUM
        ];
    }
}
