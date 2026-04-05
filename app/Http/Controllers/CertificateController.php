<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Http\Resources\CertificateResource;
use App\Http\Requests\CertificateRequest;
use App\Services\CertificateService;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 10);
        $certificates = $this->certificateService->getPaginatedCertificates($request->all(), $perPage);

        return CertificateResource::collection($certificates)->additional([
            'message' => 'Certificates retrieved successfully',
            'form_dependencies' => $this->certificateService->getFormDependencies($request),
        ]);
    }

    public function store(CertificateRequest $request)
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
        $certificate = $this->certificateService->getCertificateDetail($certificate);

        return (new CertificateResource($certificate))->additional([
            'message' => 'Certificate retrieved successfully',
            'form_dependencies' => $this->certificateService->getFormDependencies(request())
        ]);
    }

    public function update(CertificateRequest $request, Certificate $certificate)
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

    public function __construct(protected CertificateService $certificateService)
    {
        $this->middleware('permission:certificate-view')->only(['index', 'show']);
        $this->middleware('permission:certificate-create')->only(['store']);
        $this->middleware('permission:certificate-update')->only(['update']);
        $this->middleware('permission:certificate-delete')->only(['destroy']);
    }
}
