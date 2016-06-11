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
                // "json_decode, wtf?!" - You
                // "See saveCommits()" - Me
                static::$documents[$documentType.'-'.$id] = json_decode($response['_source']['commits'], TRUE);
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
        $dataBefore = [];
        if($lastCommit !== null)
        {
            $dataBefore = $lastCommit['data'];
        }

        $commits = $this->getCommits($documentType, $id);
        $newCommit = [
            'data' => $dataNow,
            'diff' => $this->compare($dataBefore, $dataNow),
            'meta' => $meta,
        ];
        $commits[] = $newCommit;

        // Only commit if there was a change
        if(empty($newCommit['diff']))
        {
          return $newCommit;
        }

        return $this->saveCommits($documentType, $id, $commits);
    }

    protected function saveCommits($documentType, $id, $commits)
    {
        $client = $this->esClient();

        // You'll think I'm crazy now (its gonna be stored as JSON anyway, right?!),
        // but bear with me. Eloquent models come in a lot of different shapes
        // and sizes, and the amount of fields we'll store can get a bit ridiculous.
        // https://www.elastic.co/guide/en/elasticsearch/guide/1.x/finite-scale.html

        // On top of this, by storing it as a string in ES we can avoid a lot of
        // issues with the mapping configuration when we sometimes have null
        // values where ES might expect relations represented as arrays.
        $jsonCommits = json_encode($commits);

        $params = [
            'index' => static::$es_index,
            'type' => $documentType,
            'id' => $id,
            'body' => [
                'commits' => $jsonCommits
            ]
        ];

        $client->index($params);

        // Empty the in-memory cache
        if(isset(static::$documents[$documentType.'-'.$id]))
        {
          unset(static::$documents[$documentType.'-'.$id]);
        }

        $lastCommit = array_pop((array_slice($commits, -1)));
        return $lastCommit;
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
