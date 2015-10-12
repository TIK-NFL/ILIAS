<?php
/* Copyright (c) 1998-2013 ILIAS open source, Extended GPL, see docs/LICENSE */

/**
 * @author		Björn Heyser <bheyser@databay.de>
 *
 * @version		$Id$
 *
 * @package		Modules/Test
 */
class ilTestPlayerCommands
{
	const START_TEST = 'startTest';
	const RESUME_PLAYER = 'resumePlayer';
	
	const SHOW_QUESTION = 'showQuestion';
	
	const PREVIOUS_QUESTION = 'previousQuestion';
	const NEXT_QUESTION = 'nextQuestion';

	const EDIT_SOLUTION = 'editSolution';
	const MARK_QUESTION = 'markQuestion';
	const MARK_QUESTION_SAVE = 'markQuestionAndSaveIntermediate';
	const UNMARK_QUESTION = 'unmarkQuestion';
	const UNMARK_QUESTION_SAVE = 'unmarkQuestionAndSaveIntermediate';

	const SUBMIT_SOLUTION = 'submitSolution';
	const DISCARD_SOLUTION = 'discardSolution';
	const SKIP_QUESTION = 'skipQuestion';
	const SHOW_INSTANT_RESPONSE = 'showInstantResponse';
	
	const CONFIRM_HINT_REQUEST = 'confirmHintRequest';
	const SHOW_REQUESTED_HINTS_LIST = 'showRequestedHintList';

	const QUESTION_SUMMARY = 'outQuestionSummary';
	const QUESTION_SUMMARY_INC_OBLIGATIONS = 'outQuestionSummaryWithObligationsInfo';
	const QUESTION_SUMMARY_OBLIGATIONS_ONLY = 'outObligationsOnlySummary';
	const TOGGLE_SIDE_LIST = 'toggleSideList';
	
	const SHOW_QUESTION_SELECTION = 'showQuestionSelection';
	const UNFREEZE_ANSWERS = 'unfreezeCheckedQuestionsAnswers';

	const SUSPEND_TEST = 'suspendTest';
	const FINISH_TEST = 'finishTest';
	const AFTER_TEST_PASS_FINISHED = 'afterTestPassFinished';
}