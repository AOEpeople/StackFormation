<?php

namespace StackFormation;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Yaml\Parser;

class Config
{

    protected $conf;

    protected $stackManager;

    public function __construct()
    {
        $files = $this->findAllConfigurationFiles();
        if (count($files) == 0) {
            throw new \StackFormation\Exception\NoBlueprintsFoundException("Could not find any blueprints.yml configuration files");
        }
        $processor = new Processor();
        $yamlParser = new Parser();

        $config = [];
        $stacknames = [];
        foreach ($files as $file) {
            $basePath = dirname(realpath($file));
            $tmp = $yamlParser->parse(file_get_contents($file));
            if (isset($tmp['blueprints']) && is_array($tmp['blueprints'])) {
                foreach ($tmp['blueprints'] as &$blueprintConfig) {

                    // check for multiple usage of the same stackname
                    $stackname = $blueprintConfig['stackname'];
                    if (in_array($stackname, $stacknames)) {
                        throw new \Exception("Stackname '$stackname' was declared more than once.");
                    }
                    $stacknames[] = $stackname;

                    $blueprintConfig['basepath'] = $basePath;
                    $blueprintConfig['template'] = (array)$blueprintConfig['template'];
                    foreach ($blueprintConfig['template'] as &$template) {
                        $realPathFile = realpath($basePath . '/' . $template);
                        if ($realPathFile === false) {
                            throw new \Exception('Could not find template file ' . $template);
                        }
                        $template = $realPathFile;
                    }
                    if (isset($blueprintConfig['stackPolicy'])) {
                        $realPathFile = realpath($basePath . '/' . $blueprintConfig['stackPolicy']);
                        if ($realPathFile === false) {
                            throw new \Exception('Could not find stack policy '.$blueprintConfig['stackPolicy'].' referenced in file ' . $template, 1452679777);
                        }
                        $blueprintConfig['stackPolicy'] = $realPathFile;
                    }
                }
            }
            $config[] = $tmp;
        }

        $this->conf = $processor->processConfiguration(
            new ConfigTreeBuilder(),
            $config
        );
    }

    public static function findAllConfigurationFiles()
    {
        $files = array_merge(
            glob('blueprints/*/*/*/blueprints.yml'),
            glob('blueprints/*/*/blueprints.yml'),
            glob('blueprints/*/blueprints.yml'),
            glob('blueprints/blueprints.yml'),
            glob('blueprints.yml')
        );
        return $files;
    }

    public function blueprintExists($blueprint)
    {
        if (!is_string($blueprint)) {
            throw new \InvalidArgumentException('Invalid blueprint name');
        }
        return isset($this->conf['blueprints'][$blueprint]);
    }

    public function getGlobalVars()
    {
        return isset($this->conf['vars']) ? $this->conf['vars'] : [];
    }

    /**
     * @param $blueprint
     * @throws \Exception
     * @deprecated
     */
    public function getBlueprintVars($blueprint)
    {
        throw new \Exception('Use $blueprint->getVars() instead');

        //if (!is_string($blueprint)) {
        //    throw new \InvalidArgumentException('Invalid blueprint name');
        //}
        //$blueprintConfig = $this->getBlueprintConfig($blueprint);
        //$localVars = isset($blueprintConfig['vars']) ? $blueprintConfig['vars'] : [];
        //return array_merge($this->getGlobalVars(), $localVars);
    }

    public function getBlueprintConfig($blueprint)
    {
        if (!is_string($blueprint)) {
            throw new \InvalidArgumentException('Invalid stack name');
        }
        if (!$this->blueprintExists($blueprint)) {
            throw new \Exception("Blueprint '$blueprint' not found.");
        }

        return $this->conf['blueprints'][$blueprint];
    }

    public function getBlueprintNames()
    {
        $blueprintNames = array_keys($this->conf['blueprints']);
        sort($blueprintNames);
        return $blueprintNames;
    }

    public function convertBlueprintNameIntoRegex($blueprintName)
    {
        return '/^'.preg_replace('/\{[^\}]+?\}/', '(.*)', $blueprintName) .'$/';
    }

    public function getCurrentUsersAccountId()
    {
        $iamClient = SdkFactory::getClient('Iam'); /* @var $iamClient \Aws\Iam\IamClient */
        $res = $iamClient->getUser();
        $arn = $res->search('User.Arn');
        $parts = explode(':', $arn);
        return $parts[4];
    }
}
