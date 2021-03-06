<?php
declare(strict_types = 1);

namespace Pagemachine\Formlog\Tests\Unit\Domain\Form\Finishers;

/*
 * This file is part of the Pagemachine TYPO3 Formlog project.
 */

use Nimut\TestingFramework\TestCase\UnitTestCase;
use Pagemachine\Formlog\Domain\Form\Finishers\LoggerFinisher;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Form\Domain\Finishers\FinisherContext;
use TYPO3\CMS\Form\Domain\Finishers\FinisherVariableProvider;
use TYPO3\CMS\Form\Domain\Model\FormDefinition;
use TYPO3\CMS\Form\Domain\Runtime\FormRuntime;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Testcase for Pagemachine\Formlog\Domain\Form\Finishers\LoggerFinisher
 */
class LoggerFinisherTest extends UnitTestCase
{
    /**
     * @var LoggerFinisher
     */
    protected $loggerFinisher;

    /**
     * @var FinisherContext|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $finisherContext;

    /**
     * @var Connection|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $connection;

    /**
     * @var TypoScriptFrontendController|\Prophecy\Prophecy\ObjectProphecy
     */
    protected $frontendController;

    /**
     * Set up this testcase
     */
    protected function setUp()
    {
        $GLOBALS['EXEC_TIME'] = 1490191502;

        /** @var FormDefinition|\Prophecy\Prophecy\ObjectProphecy */
        $formDefinition = $this->prophesize(FormDefinition::class);
        $formDefinition->getIdentifier()->willReturn('test-form');

        /** @var FormRuntime|\Prophecy\Prophecy\ObjectProphecy */
        $formRuntime = $this->prophesize(FormRuntime::class);
        $formRuntime->getFormDefinition()->willReturn($formDefinition->reveal());

        /** @var FinisherContext|\Prophecy\Prophecy\ObjectProphecy */
        $this->finisherContext = $this->prophesize(FinisherContext::class);
        $this->finisherContext->getFormRuntime()->willReturn($formRuntime->reveal());

        /** @var Connection|\Prophecy\Prophecy\ObjectProphecy */
        $this->connection = $this->prophesize(Connection::class);
        /** @var ConnectionPool|\Prophecy\Prophecy\ObjectProphecy */
        $connectionPool = $this->prophesize(ConnectionPool::class);
        $connectionPool->getConnectionForTable('tx_formlog_entries')->willReturn($this->connection->reveal());
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPool->reveal());

        /** @var TypoScriptFrontendController|\Prophecy\Prophecy\ObjectProphecy */
        $frontendController = $this->prophesize(TypoScriptFrontendController::class)->reveal();
        $this->frontendController = $frontendController;
        $this->frontendController->id = 2;
        $this->frontendController->sys_language_uid = 20;
        $this->loggerFinisher = new LoggerFinisher('test', $this->frontendController);
    }

    /**
     * Tear down this testcase
     */
    protected function tearDown()
    {
        GeneralUtility::purgeInstances();
        unset($GLOBALS['EXEC_TIME']);
    }

    /**
     * @test
     */
    public function logsFormValuesToDatabase()
    {
        $this->finisherContext->getFormValues()->willReturn(['foo' => 'bar', 'qux' => 10]);
        $this->finisherContext->getFinisherVariableProvider()->willReturn(new FinisherVariableProvider());

        $this->connection->insert('tx_formlog_entries', [
            'pid' => 2,
            'crdate' => 1490191502,
            'language' => 20,
            'identifier' => 'test-form',
            'data' => '{"foo":"bar","qux":10}',
            'finisher_variables' => '[]',
        ])->shouldBeCalled();

        $this->loggerFinisher->execute($this->finisherContext->reveal());
    }

    /**
     * @test
     */
    public function addsFinisherVariables()
    {
        $this->finisherContext->getFormValues()->willReturn(['foo' => 'bar', 'qux' => 10]);
        $variableProvider = new FinisherVariableProvider();
        $variableProvider->add('FinisherA', 'optionA1', 'valueA1');
        $variableProvider->add('FinisherA', 'optionA2', 'valueA2');
        $variableProvider->add('FinisherB', 'optionB1', 'valueB1');
        $this->finisherContext->getFinisherVariableProvider()->willReturn($variableProvider);

        $this->loggerFinisher->setOption('finisherVariables', [
            'FinisherA' => [
                'optionA1',
                'optionA2',
            ],
            'FinisherB' => [
                'optionB1',
            ],
        ]);

        $this->connection->insert('tx_formlog_entries', [
            'pid' => 2,
            'crdate' => 1490191502,
            'language' => 20,
            'identifier' => 'test-form',
            'data' => '{"foo":"bar","qux":10}',
            'finisher_variables' => '{"FinisherA":{"optionA1":"valueA1","optionA2":"valueA2"},"FinisherB":{"optionB1":"valueB1"}}',
        ])->shouldBeCalled();

        $this->loggerFinisher->execute($this->finisherContext->reveal());
    }
}
