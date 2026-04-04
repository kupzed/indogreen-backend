<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Project;
use App\Models\Mitra;
use App\Http\Requests\StoreActivityRequest;
use App\Http\Requests\UpdateActivityRequest;
use App\Http\Resources\ActivityResource;
use App\Services\ActivityService;
use App\Services\AIDocumentExtractionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ActivityController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->integer('per_page', 10);
        $allowed = [10, 25, 50, 100];
        if (!in_array($perPage, $allowed, true)) {
            $perPage = 10;
        }

        $activities = Activity::with(['project', 'mitra', 'attachments'])
            ->filter($request->all())
            ->paginate($perPage);

        $vendorOptions = [];
        if ($request->filled('project_id')) {
            $vendorOptions = $this->activityService->getVendorOptions((int) $request->project_id);
        }

        return ActivityResource::collection($activities)->additional([
            'message' => 'Activities retrieved successfully',
            'vendor_options' => $vendorOptions,
            'form_dependencies' => $this->getFormDependenciesArray()
        ]);
    }

    public function store(StoreActivityRequest $request, AIDocumentExtractionService $aiService)
    {
        if ($request->input('action') === 'extract') {
            return $this->extractDocument($request, $aiService);
        }

        $validated = $request->validated();

        $files = $request->file('attachments', []);
        $names = $request->input('attachment_names', []);
        $descs = $request->input('attachment_descriptions', []);

        $activity = $this->activityService->createActivity($validated, $files, $names, $descs);

        return response()->json([
            'message' => 'Activity created successfully',
            'data' => new ActivityResource($activity),
        ], 201);
    }

    public function show(Activity $activity)
    {
        try {
            $activity->load(['project', 'mitra', 'attachments']);

            return (new ActivityResource($activity))->additional([
                'message' => 'Activity retrieved successfully',
                'form_dependencies' => $this->getFormDependenciesArray()
            ]);
        } catch (\Exception $e) {
            Log::error('Error showing activity: ' . $e->getMessage());
            return response()->json([
                'message' => 'Failed to retrieve activity',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(UpdateActivityRequest $request, Activity $activity)
    {
        $validated = $request->validated();

        $files         = $request->file('attachments', []);
        $names         = $request->input('attachment_names', []);
        $descs         = $request->input('attachment_descriptions', []);
        $removedIds    = $request->input('removed_existing_ids', []);
        $existingIds   = $request->input('existing_attachment_ids', []);
        $existingNames = $request->input('existing_attachment_names', []);
        $existingDescs = $request->input('existing_attachment_descriptions', []);

        $activity = $this->activityService->updateActivity(
            $activity, $validated, $files, $names, $descs,
            $removedIds, $existingIds, $existingNames, $existingDescs
        );

        return response()->json([
            'message' => 'Activity updated successfully',
            'data' => new ActivityResource($activity),
        ]);
    }

    public function destroy(Activity $activity)
    {
        $this->activityService->deleteActivity($activity);

        return response()->json([
            'message' => 'Activity deleted successfully'
        ]);
    }

    private function getFormDependenciesArray(): array
    {
        $projects  = Project::all(['id', 'name', 'mitra_id']);
        $customers = Mitra::where('is_customer', true)->get(['id', 'nama']);
        $vendors   = Mitra::where('is_vendor', true)->get(['id', 'nama']);

        return [
            'projects'      => $projects,
            'customers'     => $customers,
            'vendors'       => $vendors,
            'kategori_list' => [
                'Expense Report', 'Invoice', 'Invoice & FP', 'Purchase Order', 'Payment', 'Quotation',
                'Faktur Pajak', 'Kasbon', 'Laporan Teknis', 'Surat Masuk', 'Surat Keluar',
                'Kontrak', 'Berita Acara', 'Receive Item', 'Delivery Order', 'Legalitas', 'Other',
            ],
            'jenis_list'    => ['Internal', 'Customer', 'Vendor']
        ];
    }

    private function extractDocument(StoreActivityRequest $request, AIDocumentExtractionService $aiService)
    {
        try {
            $file      = $request->file('document');
            $projectId = $request->input('project_id');
            $result    = $aiService->extract($file, $projectId);

            return response()->json([
                'message' => 'Document extracted successfully',
                'data'    => $result,
            ]);
        } catch (\RuntimeException $e) {
            Log::error('AI Document Extraction: Runtime failure', [
                'error' => $e->getMessage(),
                'file'  => $request->file('document')?->getClientOriginalName() ?? 'unknown',
            ]);

            return response()->json([
                'message' => 'Ekstraksi dokumen gagal: ' . $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('AI Document Extraction: Unexpected failure', [
                'error' => $e->getMessage(),
                'file'  => $request->file('document')?->getClientOriginalName() ?? 'unknown',
            ]);

            return response()->json([
                'message' => 'Terjadi kesalahan tidak terduga pada layanan ekstraksi. Silakan coba lagi.',
            ], 500);
        }
    }

    public function __construct(protected ActivityService $activityService)
    {
        // hak untuk melihat data activity (list, detail, form dependencies)
        $this->middleware('permission:activity-view')->only([
            'index', 'show'
        ]);
        // hak membuat activity
        $this->middleware('permission:activity-create')->only(['store']);
        // hak memperbarui activity
        $this->middleware('permission:activity-update')->only(['update']);
        // hak menghapus activity
        $this->middleware('permission:activity-delete')->only(['destroy']);
    }
}
