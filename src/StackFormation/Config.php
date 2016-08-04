<?php

namespace StackFormation;

use StackFormation\Exception\BlueprintNotFoundException;

class Config
{

    /**
     * @var array
     */
    protected $conf;

    public function __construct(array $files=null)
    {
        $files = is_null($files) ? $this->findAllConfigurationFiles() : $files;
        if (count($files) == 0) {
            throw new \StackFormation\Exception\NoBlueprintsFoundException("Could not find any blueprints.yml configuration files");
        }

        $yamlParser = new \Symfony\Component\Yaml\Parser();

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
                    if (empty($blueprintConfig['template'])) {
                        throw new \Exception("Stackname '$stackname' does not specify a template.");
                    }

                    $stacknames[] = $stackname;

                    $blueprintConfig['basepath'] = $basePath;
                }
            }
            $config[] = $tmp;
        }

        $processor = new \Symfony\Component\Config\Definition\Processor();
        $this->conf = $processor->processConfiguration(new ConfigTreeBuilder(), $config);
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

    public function blueprintExists($blueprintName)
    {
        if (!is_string($blueprintName)) {
            throw new \InvalidArgumentException('Invalid blueprint name');
        }
        return isset($this->conf['blueprints'][$blueprintName]);
    }

    public function getGlobalVar($var)
    {
        $vars = $this->getGlobalVars();
        if (!isset($vars[$var])) {
            throw new \Exception("Variable '$var' not found");
        }
        return $vars[$var];
    }

    public function getGlobalVars()
    {
        return isset($this->conf['vars']) ? $this->conf['vars'] : [];
    }

    public function getBlueprintConfig($blueprintName)
    {
        if (!$this->blueprintExists($blueprintName)) {
            throw new BlueprintNotFoundException($blueprintName);
        }
        return $this->conf['blueprints'][$blueprintName];
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

    ///**
    // * TODO: this should not be here...
    // *
    // * @return mixed
    // */
    //public function getCurrentUsersAccountId()
    //{
    //    $iamClient = SdkFactory::getClient('Iam'); /* @var $iamClient \Aws\Iam\IamClient */
    //    $res = $iamClient->getUser();
    //    $arn = $res->search('User.Arn');
    //    $parts = explode(':', $arn);
    //    return $parts[4];
    //}
}
