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
 * @author		Björn Heyser <bheyser@databay.de>
 * @version		$Id$
 *
 * @package		Modules/Test
 */
abstract class ilTestRandomQuestionSetBuilder implements ilTestRandomSourcePoolDefinitionQuestionCollectionProvider
{
    protected $checkMessages = [];

    protected function __construct(
        protected ilDBInterface $db,
        protected ilLanguage $lng,
        protected ilLogger $log,
        protected ilObjTest $testOBJ,
        protected ilTestRandomQuestionSetConfig $questionSetConfig,
        protected ilTestRandomQuestionSetSourcePoolDefinitionList $sourcePoolDefinitionList,
        protected ilTestRandomQuestionSetStagingPoolQuestionList $stagingPoolQuestionList
    ) {
    }

    abstract public function checkBuildable();

    abstract public function performBuild(ilTestSession $testSession);


    // hey: fixRandomTestBuildable - rename/public-access to be aware for building interface
    public function getSrcPoolDefListRelatedQuestCombinationCollection(ilTestRandomQuestionSetSourcePoolDefinitionList $sourcePoolDefinitionList): ilTestRandomQuestionSetQuestionCollection
    // hey.
    {
        $questionStage = new ilTestRandomQuestionSetQuestionCollection();

        foreach ($sourcePoolDefinitionList as $definition) {
            /** @var ilTestRandomQuestionSetSourcePoolDefinition $definition */

            // hey: fixRandomTestBuildable - rename/public-access to be aware for building interface
            $questions = $this->getSrcPoolDefRelatedQuestCollection($definition);
            // hey.
            $questionStage->mergeQuestionCollection($questions);
        }

        return $questionStage;
    }

    // hey: fixRandomTestBuildable - rename/public-access to be aware for building interface
    /**
     * @param ilTestRandomQuestionSetSourcePoolDefinition $definition
     * @return ilTestRandomQuestionSetQuestionCollection
     */
    public function getSrcPoolDefRelatedQuestCollection(ilTestRandomQuestionSetSourcePoolDefinition $definition): ilTestRandomQuestionSetQuestionCollection
    // hey.
    {
        $questionIds = $this->getQuestionIdsForSourcePoolDefinitionIds($definition);
        $questionStage = $this->buildSetQuestionCollection($definition, $questionIds);

        return $questionStage;
    }

    // hey: fixRandomTestBuildable - rename/public-access to be aware for building interface
    /**
     * @param ilTestRandomQuestionSetSourcePoolDefinitionList $sourcePoolDefinitionList
     * @return ilTestRandomQuestionSetQuestionCollection
     */
    public function getSrcPoolDefListRelatedQuestUniqueCollection(ilTestRandomQuestionSetSourcePoolDefinitionList $sourcePoolDefinitionList): ilTestRandomQuestionSetQuestionCollection
    {
        $combinationCollection = $this->getSrcPoolDefListRelatedQuestCombinationCollection($sourcePoolDefinitionList);
        return $combinationCollection->getUniqueQuestionCollection();
    }
    // hey.

    private function getQuestionIdsForSourcePoolDefinitionIds(ilTestRandomQuestionSetSourcePoolDefinition $definition): array
    {
        $this->stagingPoolQuestionList->resetQuestionList();

        $this->stagingPoolQuestionList->setTestObjId($this->testOBJ->getId());
        $this->stagingPoolQuestionList->setTestId($this->testOBJ->getTestId());
        $this->stagingPoolQuestionList->setPoolId($definition->getPoolId());

        if ($this->hasTaxonomyFilter($definition)) {
            // fau: taxFilter/typeFilter - use new taxonomy filter
            foreach ($definition->getMappedTaxonomyFilter() as $taxId => $nodeIds) {
                $this->stagingPoolQuestionList->addTaxonomyFilter($taxId, $nodeIds);
            }
            #$this->stagingPoolQuestionList->addTaxonomyFilter(
            #	$definition->getMappedFilterTaxId(), array($definition->getMappedFilterTaxNodeId())
            #);
            // fau.
        }

        if (count($definition->getLifecycleFilter())) {
            $this->stagingPoolQuestionList->setLifecycleFilter($definition->getLifecycleFilter());
        }

        // fau: taxFilter/typeFilter - use type filter
        if ($this->hasTypeFilter($definition)) {
            $this->stagingPoolQuestionList->setTypeFilter($definition->getTypeFilter());
        }
        // fau.

        $this->stagingPoolQuestionList->loadQuestions();

        return $this->stagingPoolQuestionList->getQuestions();
    }

    private function buildSetQuestionCollection(ilTestRandomQuestionSetSourcePoolDefinition $definition, $questionIds): ilTestRandomQuestionSetQuestionCollection
    {
        $setQuestionCollection = new ilTestRandomQuestionSetQuestionCollection();

        foreach ($questionIds as $questionId) {
            $setQuestion = new ilTestRandomQuestionSetQuestion();

            $setQuestion->setQuestionId($questionId);
            $setQuestion->setSourcePoolDefinitionId($definition->getId());

            $setQuestionCollection->addQuestion($setQuestion);
        }

        return $setQuestionCollection;
    }

    private function hasTaxonomyFilter(ilTestRandomQuestionSetSourcePoolDefinition $definition): bool
    {
        // fau: taxFilter - check for existing taxonomy filter
        if (!count($definition->getMappedTaxonomyFilter())) {
            return false;
        }
        #if( !(int)$definition->getMappedFilterTaxId() )
        #{
        #	return false;
        #}
        #
        #if( !(int)$definition->getMappedFilterTaxNodeId() )
        #{
        #	return false;
        #}
        // fau.
        return true;
    }

    //	fau: typeFilter - check for existing type filter
    private function hasTypeFilter(ilTestRandomQuestionSetSourcePoolDefinition $definition): bool
    {
        if (count($definition->getTypeFilter())) {
            return true;
        }

        return false;
    }
    //	fau.

    protected function storeQuestionSet(ilTestSession $testSession, $questionSet)
    {
        $position = 0;

        foreach ($questionSet->getQuestions() as $setQuestion) {
            /* @var ilTestRandomQuestionSetQuestion $setQuestion */

            $setQuestion->setSequencePosition($position++);

            $this->storeQuestion($testSession, $setQuestion);
        }
    }

    private function storeQuestion(ilTestSession $testSession, ilTestRandomQuestionSetQuestion $setQuestion)
    {
        $nextId = $this->db->nextId('tst_test_rnd_qst');

        $this->db->insert('tst_test_rnd_qst', array(
            'test_random_question_id' => array('integer', $nextId),
            'active_fi' => array('integer', $testSession->getActiveId()),
            'question_fi' => array('integer', $setQuestion->getQuestionId()),
            'sequence' => array('integer', $setQuestion->getSequencePosition()),
            'pass' => array('integer', $testSession->getPass()),
            'tstamp' => array('integer', time()),
            'src_pool_def_fi' => array('integer', $setQuestion->getSourcePoolDefinitionId())
        ));
    }

    protected function fetchQuestionsFromStageRandomly(ilTestRandomQuestionSetQuestionCollection $questionStage, $requiredQuestionAmount): ilTestRandomQuestionSetQuestionCollection
    {
        $questionSet = $questionStage->getRandomQuestionCollection($requiredQuestionAmount);

        return $questionSet;
    }

    protected function handleQuestionOrdering(ilTestRandomQuestionSetQuestionCollection $questionSet)
    {
        if ($this->testOBJ->getShuffleQuestions()) {
            $questionSet->shuffleQuestions();
        }
    }

    // =================================================================================================================

    final public static function getInstance(
        ilDBInterface $db,
        ilLanguage $lng,
        ilLogger $log,
        ilObjTest $testOBJ,
        ilTestRandomQuestionSetConfig $questionSetConfig,
        ilTestRandomQuestionSetSourcePoolDefinitionList $sourcePoolDefinitionList,
        ilTestRandomQuestionSetStagingPoolQuestionList $stagingPoolQuestionList
    ) {
        if ($questionSetConfig->isQuestionAmountConfigurationModePerPool()) {
            return new ilTestRandomQuestionSetBuilderWithAmountPerPool(
                $db,
                $lng,
                $log,
                $testOBJ,
                $questionSetConfig,
                $sourcePoolDefinitionList,
                $stagingPoolQuestionList
            );
        }

        return new ilTestRandomQuestionSetBuilderWithAmountPerTest(
            $db,
            $lng,
            $log,
            $testOBJ,
            $questionSetConfig,
            $sourcePoolDefinitionList,
            $stagingPoolQuestionList
        );
    }

    //fau: fixRandomTestBuildable - function to get messages
    /**
     * @return array
     */
    public function getCheckMessages(): array
    {
        return $this->checkMessages;
    }
    // fau.
}
