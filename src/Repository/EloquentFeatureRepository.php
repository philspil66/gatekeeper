<?php

namespace Gatekeeper\Repository;

use Gatekeeper\Domain\Exception\FeatureException;
use Gatekeeper\Domain\Repository\FeatureRepositoryInterface;
use Gatekeeper\Domain\Model\Feature;
use Gatekeeper\Featurable\FeaturableInterface;
use Gatekeeper\Model\Feature as Model;

class EloquentFeatureRepository implements FeatureRepositoryInterface
{
    public function save(Feature $feature)
    {
        /** @var Model $model */
        $model = Model::where('name', '=', $feature->getName())->first();

        if (!$model) {
            $model = new Model();
        }

        $model->name = $feature->getName();
        $model->is_enabled = $feature->isEnabled();

        try {
            $model->save();
        } catch (\Exception $e) {
            throw new FeatureException('Unable to save the feature: ' . $e->getMessage());
        }
    }

    public function remove(Feature $feature)
    {
        /** @var Model $model */
        $model = Model::where('name', '=', $feature->getName())->first();
        if (!$model) {
            throw new FeatureException('Unable to find the feature.');
        }

        $model->delete();
    }

    public function findByName($featureName)
    {
        /** @var Model $model */
        $model = Model::where('name', '=', $featureName)->first();
        if (!$model) {
            throw new FeatureException('Unable to find the feature.');
        }

        return Feature::fromNameAndStatus(
            $model->name,
            $model->is_enabled
        );
    }

    public function enableFor($featureName, FeaturableInterface $featurable)
    {
        /** @var Model $model */
        $model = Model::where('name', '=', $featureName)->first();
        if (!$model) {
            throw new FeatureException('Unable to find the feature.');
        }

        if ((bool) $model->is_enabled === true || $featurable->hasFeature($featureName) === true) {
            return;
        }

        $featurable->features()->attach($model->id);
    }

    public function disableFor($featureName, FeaturableInterface $featurable)
    {
        /** @var Model $model */
        $model = Model::where('name', '=', $featureName)->first();
        if (!$model) {
            throw new FeatureException('Unable to find the feature.');
        }

        if ((bool) $model->is_enabled === true || $featurable->hasFeature($featureName) === false) {
            return;
        }

        $featurable->features()->detach($model->id);
    }

    public function isEnabledFor($featureName, FeaturableInterface $featurable)
    {
        /** @var Model $model */
        $model = Model::where('name', '=', $featureName)->first();
        if (!$model) {
            throw new FeatureException('Unable to find the feature.');
        }

        return ($model->is_enabled) ? true : $featurable->hasFeature($featureName);
    }
}
