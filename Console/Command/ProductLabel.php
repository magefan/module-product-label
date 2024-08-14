<?php
/**
 * Copyright © Magefan (support@magefan.com). All rights reserved.
 * Please visit Magefan.com for license details (https://magefan.com/end-user-license-agreement).
 */

namespace Magefan\ProductLabel\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magefan\ProductLabel\Model\Config\Source\ApplyByOptions;

/**
 * Class ProductLabel
 */
class ProductLabel extends Command
{
    /**
     * @var \Magefan\ProductLabel\Model\Config
     */
    protected $config;

    /**
     * @var \Magefan\ProductLabel\Model\ProductLabelAction
     */
    protected $productLabelAction;

    /**
     * ProductLabel constructor.
     * @param \Magefan\ProductLabel\Model\ProductLabelAction $productLabelAction
     * @param \Magefan\ProductLabel\Model\Config $config
     * @param null $name
     */
    public function __construct(
        \Magefan\ProductLabel\Model\ProductLabelAction $productLabelAction,
        \Magefan\ProductLabel\Model\Config $config,
        $name = null
    ) {
        $this->config = $config;
        $this->productLabelAction = $productLabelAction;
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
            $this->productLabelAction->execute(['rule_apply_type' => ApplyByOptions::MANUALLY]);
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
        $this->setName("magefan:product-label-rules:apply");
        $this->setDescription(__("Apply Product Label Rules"));

        parent::configure();
    }
}
