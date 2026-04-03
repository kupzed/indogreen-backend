<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Project;
use App\Models\BarangCertificate;
use App\Http\Resources\CertificateResource;
use App\Http\Requests\StoreCertificateRequest;
use App\Http\Requests\UpdateCertificateRequest;
use App\Services\CertificateService;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    public function index(Request $request)
    {
        $query = Certificate::with(['project', 'barangCertificate', 'attachments']);

        $query->filter($request->only([
            'status', 'project_id', 'barang_certificate_id', 'date_from', 'date_to', 'search'
        ]));

        $sortBy  = $request->input('sort_by', 'created');
        $sortDir = strtolower($request->input('sort_dir', 'desc'));
        if (!in_array($sortDir, ['asc','desc'], true)) $sortDir = 'desc';

        if ($sortBy === 'date_of_issue') {
            $query->orderBy('date_of_issue', $sortDir)->orderBy('id', $sortDir);
        } elseif ($sortBy === 'date_of_expired') {
            $query->orderBy('date_of_expired', $sortDir)->orderBy('id', $sortDir);
        } else {
            $query->orderBy('id', $sortDir);
        }

        $perPage = $request->integer('per_page', 10);
        $certificates = $query->paginate($perPage);

        return CertificateResource::collection($certificates)->additional([
            'message' => 'Certificates retrieved successfully',
            'form_dependencies' => $this->getFormDependenciesData($request),
        ]);
    }

    public function store(StoreCertificateRequest $request)
    {
        $certificate = $this->certificateService->createCertificate(
            $request->validated(),
            $request->file('attachments', []),
            $request->input('attachment_names', []),
            $request->input('attachment_descriptions', [])
        );

        return (new CertificateResource($certificate))->additional([
            'message' => 'Certificate created successfully'
        ]);
    }

    public function show(Certificate $certificate)
    {
        $certificate->load(['project', 'barangCertificate', 'attachments']);
        
        return (new CertificateResource($certificate))->additional([
            'message' => 'Certificate retrieved successfully',
            'form_dependencies' => $this->getFormDependenciesData(request())
        ]);
    }

    public function update(UpdateCertificateRequest $request, Certificate $certificate)
    {
        $updatedCertificate = $this->certificateService->updateCertificate(
            $certificate,
            $request->validated(),
            $request->input('removed_existing_ids', []),
            $request->input('existing_attachment_ids', []),
            $request->input('existing_attachment_names', []),
            $request->input('existing_attachment_descriptions', []),
            $request->file('attachments', []),
            $request->input('attachment_names', []),
            $request->input('attachment_descriptions', [])
        );

        return (new CertificateResource($updatedCertificate))->additional([
            'message' => 'Certificate updated successfully'
        ]);
    }

    public function destroy(Certificate $certificate)
    {
        $this->certificateService->deleteCertificate($certificate);

        return response()->json([
            'message' => 'Certificate deleted successfully'
        ]);
    }

    private function getFormDependenciesData(Request $request): array
    {
        $projects = Project::select('id', 'name')->get();
        $barangCertificates = BarangCertificate::select('id', 'name', 'no_seri')->get();
        $statuses = ['Belum', 'Tidak Aktif', 'Aktif'];

        $barangOptions = [];
        if ($request->filled('project_id')) {
            $project = Project::find($request->project_id);
            if ($project) {
                $barangOptions = BarangCertificate::where('mitra_id', $project->mitra_id)
                    ->select('id', 'name', 'no_seri')
                    ->get();
            }
        }

        return [
            'projects' => $projects,
            'barang_certificates' => $barangCertificates,
            'statuses' => $statuses,
            'barang_options' => $barangOptions,
        ];
    }

    public function __construct(protected CertificateService $certificateService)
    {
        // Read/list/show
        $this->middleware('permission:certificate-view')->only([
            'index', 'show'
        ]);

        // Create / update / delete
        $this->middleware('permission:certificate-create')->only(['store']);
        $this->middleware('permission:certificate-update')->only(['update']);
        $this->middleware('permission:certificate-delete')->only(['destroy']);
    }
}
