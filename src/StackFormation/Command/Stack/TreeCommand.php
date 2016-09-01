<?php

namespace StackFormation\Command\Stack;

use StackFormation\Helper\Decorator;
use StackFormation\Stack;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TreeCommand extends \StackFormation\Command\AbstractCommand
{

    protected function configure()
    {
        $this
            ->setName('stack:tree')
            ->setDescription('List all stacks as tree')
            ->addOption(
                'nameFilter',
                null,
                InputOption::VALUE_OPTIONAL,
                'Name Filter (regex). Example --nameFilter \'/^foo/\''
            )
            ->addOption(
                'statusFilter',
                null,
                InputOption::VALUE_OPTIONAL,
                'Name Filter (regex). Example --statusFilter \'/IN_PROGRESS/\''
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $nameFilter = $input->getOption('nameFilter');
        $statusFilter = $input->getOption('statusFilter');

        $stacks = $this->getStackFactory()->getStacksFromApi(false, $nameFilter, $statusFilter);
        $dataTree = $this->prepareTree($stacks);
        $dataTree = $this->flatternTree($dataTree);
        $this->renderNode($dataTree);
    }

    /**
     * prepare stack list
     *
     * @param StackFormation\Stack[]
     * @return array
     */
    protected function prepareTree(array $arr) {
        $tree = [];
        foreach ($arr as $a) {
            $name = $a->getName();
            $cur = &$tree;
            foreach (explode("-", $name) as $e) {
                if (empty($cur[$e])) $cur[$e] = [];
                $cur = &$cur[$e];
            }
        }
        return $tree;
    }

    /**
     * flattern tree to gain a better overview
     *
     * @param array $treeIn
     * @return array
     */
    protected function flatternTree(array $treeIn)
    {
        $treeOut = [];
        foreach ($treeIn as $name => $children) {
            if (count($children) === 0) {
                $treeOut[$name] = [];
            } elseif (count($children) === 1) {
                $name = sprintf('%s-%s', $name, key($children));
                if (count($children[key($children)]) > 0) {
                    $treeOut[$name] = $this->flatternTree($children[key($children)]);
                }else{
                    $treeOut[$name] = [];
                }
            } else {
                $treeOut[$name] = $this->flatternTree($children);
            }

        }
        return $treeOut;
    }

    /**
     * render tree node
     *
     * @param string $tree
     * @param int $depth
     * @param int $cap
     */
    protected function renderNode($tree, $depth = 0, $cap = 0) {

        $n = count($tree);
        foreach ($tree as $k => $next) {
            for ($pre = "", $i = $depth - 1; $i >= 0; $i--){
                $pre.= $cap >> $i & 1 ? "│  " : "   ";
            }
            echo $pre, --$n > 0 ? '├──' : '└──', $k, PHP_EOL;
            if (false === empty($next)){
                $this->renderNode($next, $depth + 1, ($cap << 1) | ($n > 0));
            }
        }
    }
}
