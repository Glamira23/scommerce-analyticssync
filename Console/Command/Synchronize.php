<?php
/**
 * Scommerce AnalyticsSync Console command
 *
 * @category   Scommerce
 * @author     Scommerce Mage <core@scommerce-mage.com>
 */

namespace Scommerce\AnalyticsSync\Console\Command;

use Scommerce\AnalyticsSync\Model\AnalyticsSync;
use Scommerce\AnalyticsSync\Model\Synchronizer;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Magento\Framework\App\State;

/**
 * Usage:
 * scommerce:analyticssync:synchronize
 *
 */

/**
 * Class Synchronize console command
 */
class Synchronize extends Command
{
    /**
     * @var Synchronizer
     */
    private $synchronizer;

    /**
     * @var State
     */
    protected $_state = null;

    /**
     * Synchronize constructor
     *
     * @param State $state
     * @param AnalyticsSync $synchronizer
     */
    public function __construct(
        State $state,
        AnalyticsSync $synchronizer
    ) {
        parent::__construct();
        $this->_state = $state;
        $this->synchronizer = $synchronizer;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('scommerce:analyticssync:synchronize')
                ->setDescription(__('Synchronize Analytics Data'));

        parent::configure();
    }

    /**
     * Execute
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @throws
     */
    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ) {
        try {
            $this->_state->setAreaCode('adminhtml');
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
        }
        try {
            $output->writeln("Google Analytics Synchronization Started");
            $this->synchronizer->execute();
            $output->writeln("Google Analytics Synchronization Finished");
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
        }
    }
}
