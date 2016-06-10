<?php
namespace Sleavely\Datadiff;

class DatadiffObserver {

    public function saved($model)
    {
        // Verify that the model implements our required traits
        $traits = class_uses($model);
        if(in_array('Sleavely\Datadiff\DatadiffTrait', $traits))
        {
            // Now that the model has been saved,
            // lets save a copy of the new version
            // along with a diff against the old one.
            \Datadiff::addModelCommit($model);
        }
        else
        {
            throw new Exceptions\TraitNotFoundException;
        }
    }

    public function deleted($model)
    {
        // Verify that the model implements our required traits
        $traits = class_uses($model);
        if(in_array('Sleavely\Datadiff\DatadiffTrait', $traits))
        {
            \Datadiff::deleteModel($model);
        }
        else
        {
            throw new Exceptions\TraitNotFoundException;
        }
    }

}
