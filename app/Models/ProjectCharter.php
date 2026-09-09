<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectCharter extends Model
{
    use SoftDeletes;

    // Deliberately excludes price_score/complexity_score/time_score/
    // risk_score/admin_notes — those are admin-only and only ever written
    // through updateFeasibility(), never mass-assigned from the public
    // submission request.
    protected $fillable = [
        'title',
        'sponsor',
        'description',
        'importance',
        'goal',
        'success_measures',
        'measurement_methods',
        'measurement_approach',
        'start_date',
        'completion_date',
        'milestones',
        'team_members',
        'community_partners',
        'resources_needed',
        'resources_other',
        'documentation_methods',
        'documentation_plan',
        'risks',
        'leadership_reflection',
        'commitment_signature',
        'commitment_date',
    ];

    protected $casts = [
        'success_measures' => 'array',
        'measurement_methods' => 'array',
        'milestones' => 'array',
        'resources_needed' => 'array',
        'documentation_methods' => 'array',
        'risks' => 'array',
        'start_date' => 'date',
        'completion_date' => 'date',
        'commitment_date' => 'date',
    ];

    /**
     * Average of whichever of the 4 feasibility scores are set (1-5, 5 =
     * best each way) — null until at least one is scored. Computed rather
     * than stored so it can never drift out of sync with the individual
     * scores.
     */
    public function getFeasibilityScoreAttribute(): ?float
    {
        $scores = array_filter([
            $this->price_score,
            $this->complexity_score,
            $this->time_score,
            $this->risk_score,
        ], fn ($score) => $score !== null);

        if (empty($scores)) {
            return null;
        }

        return round(array_sum($scores) / count($scores), 1);
    }

    protected $appends = ['feasibility_score'];
}
