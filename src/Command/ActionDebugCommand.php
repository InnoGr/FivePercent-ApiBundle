<?php

/**
 * This file is part of the FivePercentApiBundle package
 *
 * (c) InnovationGroup
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code
 */

namespace FivePercent\Bundle\ApiBundle\Command;

use FivePercent\Component\Reflection\Reflection;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Debug API actions
 *
 * @author Vitaliy Zhuk <zhuk2205@gmail.com>
 */
class ActionDebugCommand extends ContainerAwareCommand
{
    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this
            ->setName('debug:api:action')
            ->addArgument('handler', InputArgument::IS_ARRAY | InputArgument::OPTIONAL)
            ->setDescription('Debug all API actions for handler(s).');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $handlerRegistry = $this->getContainer()->get('api.handler_registry');
        $callableResolver = $this->getContainer()->get('api.callable_resolver');
        $projectDirectory = realpath($this->getContainer()->getParameter('kernel.root_dir') . '/..');

        $handlerKeys = $handlerRegistry->getHandlerKeys();

        if ($input->getArgument('handler')) {
            $handlerKeys = array_diff(
                $handlerKeys,
                array_diff($handlerKeys, $input->getArgument('handler'))
            );
        }

        if (!count($handlerKeys)) {
            $output->writeln('Not found handlers.');

            return 0;
        }

        foreach ($handlerKeys as $handlerKey) {
            $handler = $handlerRegistry->getHandler($handlerKey);
            $actions = $handler->getActions();

            /** @var \Symfony\Component\Console\Helper\Table $table */
            $table = $this->getHelper('table');
            $table->setHeaders([
                'Name',
                'Callable',
                'File / Line'
            ]);

            $rows = [];

            foreach ($actions as $action) {
                try {
                    $callable = $callableResolver->resolve($action);
                } catch (\Exception $e) {
                    $rows[] = [
                        $action->getName(),
                        '<error>Not supported</error>',
                        ''
                    ];

                    continue;
                }

                $reflection = $callable->getReflection();
                $file = str_replace($projectDirectory, '', $reflection->getFileName());

                $callableName = Reflection::getCalledMethod($callable->getReflection(), false);
                $fileAndLine = sprintf(
                    '%s on lines %d:%d',
                    ltrim($file, '/'),
                    $reflection->getStartLine(),
                    $reflection->getEndLine()
                );

                $rows[] = [
                    $action->getName(),
                    $callableName,
                    $fileAndLine
                ];
            }

            $output->writeln(sprintf(
                'Action list for handler <info>%s</info>:',
                $handlerKey
            ));

            $table->setRows($rows);
            $table->render($output);

            $output->writeln([null, null]);
        }

        return 0;
    }
}
