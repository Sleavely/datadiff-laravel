<?php
namespace Sleavely\Datadiff;

trait DatadiffTrait {

    public $diff_meta;

    public function diff($version = null)
    {
        return \Datadiff::getModelCommit($this, $version);
    }
}
