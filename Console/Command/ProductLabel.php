<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\ProductLabel\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Magefan\ProductLabel\Model\Config\Source\ApplyByOptions;
use Magento\Framework\Escaper;
use Magento\Framework\App\State;
use Magento\Framework\App\Area;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class ProductLabel
 */
class ProductLabel extends Command
{
    const RULE_IDS_PARAM = 'ids';

    /**
     * @var \Magefan\ProductLabel\Model\Config
     */
    protected $config;

    /**
     * @var \Magefan\ProductLabel\Model\ProductLabelAction
     */
    protected $productLabelAction;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @var State
     */
    private $state;

    /**
     * ProductLabel constructor.
     * @param \Magefan\ProductLabel\Model\ProductLabelAction $productLabelAction
     * @param \Magefan\ProductLabel\Model\Config $config
     * @param Escaper $escaper
     * @param State $state
     * @param null $name
     */
    public function __construct(
        \Magefan\ProductLabel\Model\ProductLabelAction $productLabelAction,
        \Magefan\ProductLabel\Model\Config $config,
        Escaper $escaper,
        State $state,
                                                       $name = null
    ) {
        $this->config = $config;
        $this->productLabelAction = $productLabelAction;
        $this->escaper = $escaper;
        $this->state = $state;
        parent::__construct($name);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->config->isEnabled()) {
            try {
                $this->state->setAreaCode(Area::AREA_GLOBAL);
            } catch (LocalizedException $e) {
                $output->writeln((string)__('Something went wrong. %1', $this->escaper->escapeHtml($e->getMessage())));
            }

            $ruleIDs = (string)$input->getOption(self::RULE_IDS_PARAM);

            $ruleIDs = $ruleIDs
                ? array_map('intval', explode(',', $ruleIDs))
                : [];

            if ($ruleIDs) {
                $output->writeln('<info>' . __('The provided rule IDs: %1', '`' . implode(',', $ruleIDs) . '`') . '</info>');
                $this->productLabelAction->execute(['rule_apply_type' => ApplyByOptions::MANUALLY, 'rule_id' => $ruleIDs]);
            } else {
                $this->productLabelAction->execute(['rule_apply_type' => ApplyByOptions::MANUALLY]);
            }

            $output->writeln(__("Product Label rules have been applied."));
        } else {
            $output->writeln(__("Product Label extension is disabled. Please turn on it."));
        }
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $options = [
            new InputOption(
                self::RULE_IDS_PARAM,
                null,
                InputOption::VALUE_OPTIONAL,
                'Rule Ids'
            )
        ];

        $this->setDefinition($options);

        $this->setName("magefan:product-label-rules:apply");
        $this->setDescription(__("Apply Product Label Rules by Rule IDs (comma separated)"));

        parent::configure();
    }
}
