<?php

namespace Funda\QueryBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Funda\QueryBundle\Controller\QueryController;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Query command.
 */
class QueryCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('funda:query')
            ->setDescription('Querys Funda objects for sale in Amsterdam.')
            ->addOption('garden', 'g', InputOption::VALUE_NONE, 'Flag to fetch objects with a garden.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Setting up data ... ');

        $searchSubject = '/amsterdam/';
        if ($input->getOption('garden')) {
            $searchSubject .= 'tuin/';
        }

        $controller = new QueryController();

        $cacheRoot = $this->getContainer()->get('kernel')->getRootDir() . '/cache';
        $cache = new \Doctrine\Common\Cache\FilesystemCache($cacheRoot);
        $controller->setCache($cache);
        $controller->setFundaAPIKey($this->getContainer()->getParameter('funda_api_key'));

        $output->writeln('Fetching data ... ');

        try {
            $dataSet = $controller->getDataResult($searchSubject);
        } catch (\Exception $e) {
            $output->writeln('<error>' . $e->getMessage() . '</error>');
            return;
        }

        $table = new Table($output);
        $table->setHeaders(array('ID', 'Name', 'Quantity'));

        foreach ($dataSet as $data) {
            $table->addRow(array($data['id'], $data['name'], $data['quantity']));
        }
        $table->render();
    }
}
