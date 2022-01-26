<?php declare(strict_types=1);
/* Copyright (c) 1998-2015 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * Class ilContactUserActionProvider
 * @author Michael Jansen <mjansen@databay.de>
 */
class ilContactUserActionProvider extends ilUserActionProvider
{
    private ilObjUser $user;
    /** @var array<string, string> */
    private array $stateToPermLinkMap = [
        'ilBuddySystemLinkedRelationState' => '_contact_approved',
        'ilBuddySystemIgnoredRequestRelationState' => '_contact_ignored',
    ];

    public function __construct()
    {
        global $DIC;

        parent::__construct();

        $this->user = $DIC['ilUser'];
    }

    /**
     * @inheritDoc
     */
    public function getComponentId() : string
    {
        return 'contact';
    }

    /**
     * @inheritDoc
     */
    public function getActionTypes() : array
    {
        $this->lng->loadLanguageModule('buddysystem');
        return [
            'handle_req' => $this->lng->txt('buddy_handle_contact_request'),
        ];
    }

    /**
     * @inheritDoc
     */
    public function collectActionsForTargetUser(int $a_target_user) : ilUserActionCollection
    {
        $coll = ilUserActionCollection::getInstance();

        if (!ilBuddySystem::getInstance()->isEnabled()) {
            return $coll;
        }

        if ($this->user->isAnonymous() || ilObjUser::_isAnonymous($this->getUserId())) {
            return $coll;
        }

        $buddyList = ilBuddyList::getInstanceByGlobalUser();
        $requested_contacts = $buddyList->getRequestRelationsForOwner()->getKeys();

        if (in_array($a_target_user, $requested_contacts)) {
            $this->lng->loadLanguageModule('buddysystem');

            $relation = $buddyList->getRelationByUserId($a_target_user);
            foreach ($relation->getCurrentPossibleTargetStates() as $target_state) {
                $f = new ilUserAction();
                $f->setText(
                    $this->lng->txt(
                        'buddy_bs_act_btn_txt_requested_to_' .
                        ilStr::convertUpperCamelCaseToUnderscoreCase($target_state->getName())
                    )
                );
                $f->setType('handle_req');
                $f->setHref(
                    ilLink::_getStaticLink(
                        $a_target_user,
                        'usr',
                        true,
                        $this->stateToPermLinkMap[get_class($target_state)]
                    )
                );
                $f->setData([
                    'current-state' => get_class($relation->getState()),
                    'target-state' => get_class($target_state),
                    'buddy-id' => $a_target_user,
                    'action' => $target_state->getAction()
                ]);
                $coll->addAction($f);
            }
        }

        return $coll;
    }
}
