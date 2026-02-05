<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\SupplierDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class PortalDocumentController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        if (!$user->supplier_id) {
            abort(403, 'User is not linked to a supplier.');
        }

        $query = SupplierDocument::where('supplier_id', $user->supplier_id)
            ->with('uploadedBy:id,name');

        // Filter by category
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        // Search
        if ($request->filled('search')) {
            $query->where('title', 'like', '%' . $request->search . '%');
        }

        $documents = $query->latest()->paginate(12);

        return Inertia::render('Portal/Documents/Index', [
            'documents' => $documents,
            'categories' => SupplierDocument::categories(),
            'filters' => $request->only(['category', 'search']),
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        if (!$user->supplier_id) {
            abort(403, 'User is not linked to a supplier.');
        }

        $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|in:certificate,catalog,contract,compliance,other',
            'file' => 'required|file|max:10240', // 10MB max
            'expires_at' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        $file = $request->file('file');
        $path = $file->store('supplier-documents/' . $user->supplier_id, 'public');

        SupplierDocument::create([
            'supplier_id' => $user->supplier_id,
            'company_id' => $user->supplier->company_id ?? 1,
            'title' => $request->title,
            'category' => $request->category,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_size' => $file->getSize(),
            'mime_type' => $file->getMimeType(),
            'expires_at' => $request->expires_at,
            'notes' => $request->notes,
            'uploaded_by' => $user->id,
        ]);

        return back()->with('success', 'Document uploaded successfully.');
    }

    public function download(SupplierDocument $document)
    {
        $user = auth()->user();

        if ($document->supplier_id !== $user->supplier_id) {
            abort(403);
        }

        if (!Storage::disk('public')->exists($document->file_path)) {
            abort(404, 'File not found.');
        }

        return Storage::disk('public')->download($document->file_path, $document->file_name);
    }

    public function destroy(SupplierDocument $document)
    {
        $user = auth()->user();

        if ($document->supplier_id !== $user->supplier_id) {
            abort(403);
        }

        // Delete file from storage
        if (Storage::disk('public')->exists($document->file_path)) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        return back()->with('success', 'Document deleted successfully.');
    }
}
