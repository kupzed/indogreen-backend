<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Project;
use App\Models\BarangCertificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CertificateController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $query = Certificate::with(['project', 'barangCertificate']);

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by project_id
        if ($request->filled('project_id')) {
            $query->where('project_id', $request->project_id);
        }

        // Filter by barang_certificate_id
        if ($request->filled('barang_certificate_id')) {
            $query->where('barang_certificate_id', $request->barang_certificate_id);
        }

        // Filter by date range
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('date_of_issue', [$request->date_from, $request->date_to]);
        } elseif ($request->filled('date_from')) {
            $query->where('date_of_issue', '>=', $request->date_from);
        } elseif ($request->filled('date_to')) {
            $query->where('date_of_issue', '<=', $request->date_to);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('no_certificate', 'like', "%$search%")
                  ->orWhereHas('project', function($q2) use ($search) {
                      $q2->where('name', 'like', "%$search%");
                  })
                  ->orWhereHas('barangCertificate', function($q2) use ($search) {
                      $q2->where('name', 'like', "%$search%");
                  });
            });
        }
        $perPage  = $request->integer('per_page', 10);
        $allowed  = [10, 25, 50, 100];
        if (!in_array($perPage, $allowed, true)) {
            $perPage = 10;
        }

        $certificates = $query->paginate($perPage);

        return response()->json([
            'message' => 'Certificates retrieved successfully',
            'data' => $certificates->items(),
            'pagination' => [
                'total' => $certificates->total(),
                'per_page' => $certificates->perPage(),
                'current_page' => $certificates->currentPage(),
                'last_page' => $certificates->lastPage(),
                'from' => $certificates->firstItem(),
                'to' => $certificates->lastItem(),
            ]
        ]);
    }

    /**
     * Store a newly created resource in storage.
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'no_certificate' => 'required|string|max:30|unique:certificates,no_certificate',
            'project_id' => 'required|exists:projects,id',
            'barang_certificate_id' => 'required|exists:barang_certificates,id',
            'status' => ['required', Rule::in(['Belum', 'Tidak Aktif', 'Aktif'])],
            'date_of_issue' => 'nullable|date',
            'date_of_expired' => 'nullable|date|after:date_of_issue',
            'attachment' => 'nullable|file|max:10240', // 10MB
        ]);

        // Convert empty date strings to null
        if (isset($validated['date_of_issue']) && $validated['date_of_issue'] === '') {
            $validated['date_of_issue'] = null;
        }
        if (isset($validated['date_of_expired']) && $validated['date_of_expired'] === '') {
            $validated['date_of_expired'] = null;
        }

        if ($request->hasFile('attachment')) {
            $validated['attachment'] = $request->file('attachment')->store('attachments/certificates', 'public');
        }

        $certificate = Certificate::create($validated);

        return response()->json([
            'message' => 'Certificate created successfully',
            'data' => $certificate->load(['project', 'barangCertificate'])
        ], 201);
    }

    /**
     * Display the specified resource.
     * @param  \App\Models\Certificate  $certificate
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Certificate $certificate)
    {
        return response()->json([
            'message' => 'Certificate retrieved successfully',
            'data' => $certificate->load(['project', 'barangCertificate'])
        ]);
    }

    /**
     * Update the specified resource in storage.
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Certificate  $certificate
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Certificate $certificate)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'no_certificate' => ['required', 'string', 'max:30', Rule::unique('certificates', 'no_certificate')->ignore($certificate->id)],
            'project_id' => 'required|exists:projects,id',
            'barang_certificate_id' => 'required|exists:barang_certificates,id',
            'status' => ['required', Rule::in(['Belum', 'Tidak Aktif', 'Aktif'])],
            'date_of_issue' => 'nullable|date',
            'date_of_expired' => 'nullable|date|after:date_of_issue',
            'attachment' => 'nullable|file|max:10240', // 10MB
        ]);

        // Convert empty date strings to null
        if (isset($validated['date_of_issue']) && $validated['date_of_issue'] === '') {
            $validated['date_of_issue'] = null;
        }
        if (isset($validated['date_of_expired']) && $validated['date_of_expired'] === '') {
            $validated['date_of_expired'] = null;
        }

        if ($request->hasFile('attachment')) {
            // Delete old file if exists
            if ($certificate->attachment) {
                Storage::disk('public')->delete($certificate->attachment);
            }
            $validated['attachment'] = $request->file('attachment')->store('attachments/certificates', 'public');
        } else if ($request->input('attachment_removed', false)) {
            // If frontend signals attachment removal
            if ($certificate->attachment) {
                Storage::disk('public')->delete($certificate->attachment);
            }
            $validated['attachment'] = null;
        }

        $certificate->update($validated);

        return response()->json([
            'message' => 'Certificate updated successfully',
            'data' => $certificate->load(['project', 'barangCertificate'])
        ]);
    }

    /**
     * Remove the specified resource from storage.
     * @param  \App\Models\Certificate  $certificate
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Certificate $certificate)
    {
        if ($certificate->attachment) {
            Storage::disk('public')->delete($certificate->attachment);
        }
        $certificate->delete();

        return response()->json([
            'message' => 'Certificate deleted successfully'
        ]);
    }

    /**
     * Get form dependencies for certificates
     * @return \Illuminate\Http\JsonResponse
     */
    public function getFormDependencies()
    {
        $projects = Project::select('id', 'name')->get();
        $barangCertificates = BarangCertificate::select('id', 'name', 'no_seri')->get();

        return response()->json([
            'projects' => $projects,
            'barang_certificates' => $barangCertificates
        ]);
    }

    /**
     * Get barang certificates by project ID
     * @param int $projectId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getBarangCertificatesByProject($projectId)
    {
        try {
            $project = Project::findOrFail($projectId);
            $barangCertificates = BarangCertificate::where('mitra_id', $project->mitra_id)
                ->select('id', 'name', 'no_seri')
                ->get();

            return response()->json([
                'message' => 'Barang certificates retrieved successfully',
                'data' => $barangCertificates
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Project not found',
                'data' => []
            ], 404);
        }
    }
}
