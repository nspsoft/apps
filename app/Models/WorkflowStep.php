<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkflowStep extends Model
{
    use HasFactory;

    protected $fillable = [
        'workflow_id',
        'step_order',
        'approver_type',
        'approver_id',
        'can_skip',
        'timeout_days',
    ];

    protected function casts(): array
    {
        return [
            'step_order' => 'integer',
            'approver_id' => 'integer',
            'can_skip' => 'boolean',
            'timeout_days' => 'integer',
        ];
    }

    /**
     * Get parent workflow
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(Workflow::class);
    }

    /**
     * Get the approver (user or role name)
     */
    public function getApproverNameAttribute(): string
    {
        if ($this->approver_type === 'user') {
            $user = User::find($this->approver_id);
            return $user ? $user->name : 'Unknown User';
        } else {
            $role = \Spatie\Permission\Models\Role::find($this->approver_id);
            return $role ? $role->name : 'Unknown Role';
        }
    }

    /**
     * Check if user can approve this step
     */
    public function canBeApprovedBy(User $user): bool
    {
        if ($this->approver_type === 'user') {
            return $user->id === $this->approver_id;
        } else {
            $role = \Spatie\Permission\Models\Role::find($this->approver_id);
            return $role && $user->hasRole($role->name);
        }
    }
}
