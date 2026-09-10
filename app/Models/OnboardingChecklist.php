<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class OnboardingChecklist extends Model
{
    protected $table = 'onboarding_checklists';

    protected $fillable = ['step_code', 'title', 'description', 'sort_order'];

    protected $casts = ['sort_order' => 'integer'];
}
