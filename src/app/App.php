<?php


namespace app;


use app\core\Entity;
use app\core\FileHelper;
use app\core\Std;

class App
{
    private $args;
    private $jsonFile;
    private $outputFile;

    private $overwrite = false;

    public function __construct($argv)
    {
        $this->args = $argv;
    }

    public function run()
    {
        if (!$this->parseArgs($this->args)) {
            return;
        }
        try {
            $json = $this->checkAll();

        } catch (\Exception $e) {
            Std::err($e->getMessage() . PHP_EOL, Std::FG_RED);
            return;
        }

        $import = $this->getImportPath();
        $entity = Entity::fromJSON($json);
        $keyPairs = $this->generatePairs($entity);
        $keyPairs['{{IMPORT}}'] = $import;
        $ent = $this->generateFileContent('Entity.tmpl', $keyPairs);
        $service = $this->generateFileContent('Service.tmpl', $keyPairs);
        $repository = $this->generateFileContent('Repository.tmpl', $keyPairs);
        $resource = $this->generateFileContent('Resource.tmpl', $keyPairs);

        $entityPath = $this->outputFile . "/entity/" . $entity->getName() . ".go";
        $servicePath = $this->outputFile . "/service/" . $entity->getServiceInterface() . ".go";
        $repositoryPath = $this->outputFile . "/repository/" . $entity->getRepository() . ".go";
        $resourcePath = $this->outputFile . "/resource/" . $entity->getResource() . ".go";

        FileHelper::save($entityPath, $ent, $this->overwrite);
        FileHelper::save($servicePath, $service, $this->overwrite);
        FileHelper::save($repositoryPath, $repository, $this->overwrite);
        FileHelper::save($resourcePath, $resource, $this->overwrite);
    }


    private function generateFileContent($file, $keypairs)
    {
        $content = FileHelper::Load(__DIR__ . "/tmpl/$file");
        foreach ($keypairs as $key => $value)
            $content = str_replace($key, $value, $content);
        return $content;
    }

    /**
     * @return mixed
     * @throws \Exception
     */
    function checkAll()
    {
        if (!FileHelper::isExistFile($this->jsonFile)) {
            throw new \Exception("File not exists: " . $this->jsonFile);
        }
        if (!FileHelper::isDirectory($this->outputFile)) {
            throw new \Exception("Path is not valid: " . $this->outputFile);
        }
        $content = FileHelper::Load($this->jsonFile);
        if ($content === false) {
            throw new \Exception("invalid json file");
        }
        $json = json_decode($content);
        return $json;
    }

    function parseArgs($args)
    {
        if (count($args) < 3) {
            $this->showHelp();
            return false;
        }
        if ($args[1] === "--overwrite") {
            if (count($args) < 4) {
                $this->showHelp();
                return false;
            }
            $args[1] = $args[2];
            $args[2] = $args[3];
            $args[3] = "--overwrite";
        }

        $this->jsonFile = $args[1];
        $this->outputFile = $args[2];

        if (isset($args[3])) {
            if ($args[3] !== "--overwrite") {
                Std::err("Invalid argument: " . $args[3] . PHP_EOL, Std::FG_RED);
                return false;
            }
            $this->overwrite = true;
        }

        return true;
    }

    function showHelp()
    {
        Std::err("Usage:" . PHP_EOL, Std::FG_GREEN);
        Std::err(basename(__FILE__) . " sample.json path/to/output/dir" . PHP_EOL, Std::FG_GREEN);
    }

    function getImportPath() {
        list($path, $content) = FileHelper::findMod($this->outputFile);
        if($path === false) {
            return basename($this->outputFile);
        }

        $data = explode("\n", $content);
        if (!preg_match("/module (.*$)/", $data[0],$result)) {
            return basename($this->outputFile);
        }
        return str_replace($path,trim($result[1]), $this->outputFile);
    }

    function generatePairs(Entity $entity)
    {
        $createFields = implode("\n\t", $entity->getCreateFields());
        return [
            "{{SERVICE_INTERFACE}}" => $entity->getServiceInterface(),
            "{{ENTITY}}" => $entity->getName(),
            "{{ENTITY_FIELDS}}" => implode("\n\t", $entity->getGeneratedFields()),
            "{{CREATE_FIELDS}}" => $createFields,
            "{{UPDATE_FIELDS}}" => $createFields,
            "{{VALIDATION_RULES}}" => $entity->getValidationRules(),
            "{{DEPOSITOR}}" => $entity->getDepositor(),
            "{{SERVICE_STRUCT}}" => $entity->getServiceStruct(),
            "{{COLLECTION_CONST}}" => $entity->getCollectionConst(),
            "{{RESOURCE}}" => $entity->getResource(),
            "{{SEARCH_FIELD}}" => $entity->getSearchField(),
            "{{COLLECTION_NAME}}" => $entity->getCollection(),
            "{{REPOSITOR}}" => $entity->getRepository(),
            "{{X=Y}}" => implode("\n\t",$entity->generateAssignment()),
        ];
    }
}