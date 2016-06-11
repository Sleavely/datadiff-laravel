<?php
namespace Sleavely\Datadiff;

use Elasticsearch\Client as ElasticsearchClient;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use mikemccabe\JsonPatch\JsonPatch;

class Datadiff {

    static protected $es_client;
    static protected $es_index;
    static protected $documents = [];

    public function compare($before, $after){
        //TODO: verify that we're passing arrays
        $diff = JsonPatch::diff($before, $after);
        return $diff;
    }

    public function getModelCommit($model, $version = null)
    {
        $type = $model->getTable();
        $id = $model->getAttribute($model->getKeyName());
        if(empty($id)) return null;

        return $this->getCommit($type, $id, $version);
    }

    public function getCommit($documentType, $id, $version = null)
    {
        // Gotta fetch 'em all!
        $commits = $this->getCommits($documentType, $id);

        if(!count($commits))
        {
            return null;
        }

        // Latest version, or specific?
        if(!$version) $version = count($commits);

        // Human-to-computer conversion of index
        $versionIndex = intval($version) - 1;
        if($versionIndex < 0) $versionIndex = 0;

        return $commits[$versionIndex];
    }

    public function getModelCommits($model)
    {
        $type = $model->getTable();
        $id = $model->getAttribute($model->getKeyName());
        if(empty($id)) return null;

        return $this->getCommits($type, $id);
    }

    public function getCommits($documentType, $id)
    {
        if(empty(static::$documents[$documentType.'-'.$id]))
        {
            $client = $this->esClient();

            $params = [
                'index' => static::$es_index,
                'type' => $documentType,
                'id' => $id
            ];

            try {
                $response = $client->get($params);
                static::$documents[$documentType.'-'.$id] = $response['_source']['commits'];
            }
            catch(Missing404Exception $e)
            {
                // Always return an array, because ES will hate us later if we dont
                static::$documents[$documentType.'-'.$id] = [];
            }
        }

        return static::$documents[$documentType.'-'.$id];
    }

    public function addModelCommit($model)
    {
        $type = $model->getTable();
        $id = $model->getAttribute($model->getKeyName());
        if(empty($id)) return null;
        return $this->addCommit($type, $id, $model->diff_meta, $model->toArray());
    }

    public function addCommit($documentType, $id, $meta, $dataNow)
    {
        // Try to load the last commit so we can generate a diff
        $lastCommit = $this->getCommit($documentType, $id);
        if($lastCommit !== null)
        {
            $dataBefore = $lastCommit['data'];
        }

        $commits = $this->getCommits($documentType, $id);
        $commits[] = [
            'data' => $dataNow,
            'diff' => $this->compare($dataBefore, $dataNow),
            'meta' => $meta,
        ];

        return $this->saveCommits($documentType, $id, $commits);
    }

    protected function saveCommits($documentType, $id, $commits)
    {
        $client = $this->esClient();

        $params = [
            'index' => static::$es_index,
            'type' => $documentType,
            'id' => $id,
            'body' => [
                'commits' => $commits
            ]
        ];

        $client->index($params);
        return true; // Because we havent had any exceptions so far. :D
    }

    public function deleteModel($model)
    {
        $type = $model->getTable();
        $id = $model->getAttribute($model->getKeyName());
        if(empty($id)) return null;
        return $this->deleteDocument($type, $id);
    }

    public function deleteDocument($documentType, $id)
    {
        $client = $this->esClient();

        $params = [
            'index' => static::$es_index,
            'type' => $documentType,
            'id' => $id,
        ];

        $client->delete($params);
        return true;
    }

    protected function esClient()
    {
        if(static::$es_client === NULL)
        {
            // Init ES
            $hosts = \Config::get('datadiff::elasticsearch.hosts');
            $index = \Config::get('datadiff::elasticsearch.index');

            static::$es_client = new ElasticsearchClient(['hosts' => $hosts]);
            static::$es_index = $index;
        }
        return static::$es_client;
    }
    public function rebootEsClient()
    {
        static::$es_client = NULL;
        return $this->esClient();
    }

}
