<?php

namespace App\Http\Controllers;

use App\Models\ApprovalRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ApprovalController extends Controller
{
    /**
     * Get pending approvals for current user
     */
    public function pending()
    {
        $pendingApprovals = ApprovalRequest::getPendingForUser(auth()->user())
            ->map(function ($request) {
                $document = $request->document;
                return [
                    'id' => $request->id,
                    'workflow_name' => $request->workflow->name,
                    'document_type' => $request->document_type,
                    'document_id' => $request->document_id,
                    'document_number' => $document ? ($document->number ?? $document->po_number ?? $document->so_number ?? "#{$document->id}") : 'N/A',
                    'current_step' => $request->current_step,
                    'total_steps' => $request->workflow->steps->count(),
                    'requested_by' => $request->requester->name,
                    'requested_at' => $request->created_at->format('d M Y H:i'),
                ];
            });

        return response()->json(['approvals' => $pendingApprovals]);
    }

    /**
     * Get approval history for a document
     */
    public function history(string $documentType, int $documentId)
    {
        $request = ApprovalRequest::where('document_type', $documentType)
            ->where('document_id', $documentId)
            ->with(['histories.actor', 'workflow.steps'])
            ->first();

        if (!$request) {
            return response()->json(['history' => [], 'status' => null]);
        }

        return response()->json([
            'status' => $request->status,
            'current_step' => $request->current_step,
            'workflow' => $request->workflow->name,
            'history' => $request->histories->map(fn ($h) => [
                'step' => $h->step_order,
                'action' => $h->action,
                'actor' => $h->actor->name,
                'notes' => $h->notes,
                'acted_at' => $h->created_at->format('d M Y H:i'),
            ]),
        ]);
    }

    /**
     * Approve a document
     */
    public function approve(Request $request, ApprovalRequest $approvalRequest)
    {
        $validated = $request->validate([
            'notes' => 'nullable|string|max:500',
        ]);

        $success = $approvalRequest->approve(auth()->user(), $validated['notes'] ?? null);

        if ($success) {
            return back()->with('success', 'Document approved successfully.');
        }

        return back()->with('error', 'You are not authorized to approve this document.');
    }

    /**
     * Reject a document
     */
    public function reject(Request $request, ApprovalRequest $approvalRequest)
    {
        $validated = $request->validate([
            'notes' => 'required|string|max:500',
        ]);

        $success = $approvalRequest->reject(auth()->user(), $validated['notes']);

        if ($success) {
            return back()->with('success', 'Document rejected.');
        }

        return back()->with('error', 'You are not authorized to reject this document.');
    }
}
