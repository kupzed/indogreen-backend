<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIDocumentExtractionService
{
    private string $apiKey;
    private string $baseUrl;
    private string $model;

    // MIME types the vision model can handle natively as images
    private const IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    public function __construct()
    {
        $this->apiKey  = config('services.ai.api_key', '');
        $this->baseUrl = rtrim(config('services.ai.base_url', 'https://api.x.ai/v1'), '/');
        $this->model   = config('services.ai.model', 'grok-4-1-fast-non-reasoning');
    }

    /**
     * Extract structured data from an uploaded document/image.
     *
     * @param  UploadedFile  $file
     * @param  int|null $projectId
     * @return array
     * @throws \RuntimeException
     */
    public function extract(UploadedFile $file, ?int $projectId = null): array
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('AI_API_KEY is not configured.');
        }

        $context = $this->buildProjectContext($projectId);
        $mimeType = $file->getClientMimeType();
        $isImage  = in_array($mimeType, self::IMAGE_MIMES, true);

        $messages = $isImage
            ? $this->buildVisionMessages($file, $mimeType, $context)
            : $this->buildTextMessages($file, $context);

        $payload = [
            'model'       => $this->model,
            'messages'    => $messages,
            'temperature' => 0.1,
            'max_tokens'  => 2048,
        ];

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->apiKey}",
            'Content-Type'  => 'application/json',
        ])->post("{$this->baseUrl}/chat/completions", $payload);

        if ($response->failed()) {
            Log::error('Provider AI API error', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
            throw new \RuntimeException(
                'AI extraction failed (HTTP ' . $response->status() . '): ' . $response->body()
            );
        }

        $responseData = $response->json();

        // OpenAI-compatible response: choices[0].message.content
        $rawText = $responseData['choices'][0]['message']['content'] ?? '';

        if (empty(trim($rawText))) {
            Log::error('Provider AI returned empty content', ['response' => $responseData]);
            throw new \RuntimeException('AI returned an empty response. Please try again.');
        }

        // Strip markdown code fences if present (```json ... ```)
        $cleanedText = trim(preg_replace(
            ['/^```(?:json)?\s*/i', '/\s*```$/'],
            '',
            trim($rawText)
        ));

        $parsed = json_decode($cleanedText, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($parsed)) {
            Log::error('Provider AI returned non-JSON response', ['raw' => $rawText]);
            throw new \RuntimeException(
                'AI returned an invalid response. Please try again with a clearer document.'
            );
        }

        return $this->sanitize($parsed);
    }

    /**
     * Build OpenAI vision-format messages for image files (multimodal).
     */
    private function buildVisionMessages(UploadedFile $file, string $mimeType, string $context): array
    {
        $base64 = base64_encode(file_get_contents($file->getRealPath()));

        return [
            [
                'role'    => 'system',
                'content' => $this->systemPrompt(),
            ],
            [
                'role'    => 'user',
                'content' => [
                    [
                        'type'      => 'image_url',
                        'image_url' => [
                            'url'    => "data:{$mimeType};base64,{$base64}",
                            'detail' => 'high',
                        ],
                    ],
                    [
                        'type' => 'text',
                        'text' => "Analyze this document/image and extract the data according to the JSON schema in the system prompt.\n\n"
                               . "PROJECT CONTEXT (Hints):\n{$context}",
                    ],
                ],
            ],
        ];
    }

    /**
     * Build plain-text messages for non-image files (PDF, DOCX, etc.).
     * The raw text content is extracted from the file and sent as context.
     */
    private function buildTextMessages(UploadedFile $file, string $context): array
    {
        // For non-image files, read as much raw text as possible
        $rawContent = @file_get_contents($file->getRealPath());

        // Strip binary noise for binary formats and limit length
        $textContent = preg_replace('/[^\x20-\x7E\xA0-\xFF\n\r\t]/u', ' ', $rawContent ?? '');
        $textContent = mb_substr(trim($textContent), 0, 8000); // keep within token budget

        $originalName = $file->getClientOriginalName();

        return [
            [
                'role'    => 'system',
                'content' => $this->systemPrompt(),
            ],
            [
                'role'    => 'user',
                'content' => "The following is the text content extracted from a file named \"{$originalName}\".\n\n"
                           . "PROJECT CONTEXT (Hints):\n{$context}\n\n"
                           . "---\n{$textContent}\n---\n\n"
                           . "Extract the relevant data and return ONLY the JSON object as specified in the system prompt.",
            ],
        ];
    }

    /**
     * Fetch relevant project name/customer hints.
     */
    private function buildProjectContext(?int $projectId): string
    {
        if (!$projectId) return 'Tidak ada konteks proyek spesifik.';

        $project = \App\Models\Project::with('mitra')->find($projectId);
        if (!$project) return 'Data proyek tidak ditemukan.';

        $customer = $project->mitra ? $project->mitra->nama : 'N/A';
        return "Nama Proyek: {$project->name}\nCustomer Proyek: {$customer}";
    }

    /**
     * The system prompt that enforces strict JSON output.
     */
    private function systemPrompt(): string
    {
        return <<<'PROMPT'
Anda adalah alat ekstraksi data otomatis yang sangat presisi. Tugas SATU-SATUNYA Anda adalah menganalisis dokumen dan mengembalikan data dalam format JSON terstruktur.

ATURAN PENTING:
1. Kembalikan HANYA objek JSON mentah. Tanpa markdown, tanpa blok kode (```), tanpa teks penjelasan apapun.
2. JSON harus menggunakan KUNCI dan BATASAN NILAI berikut ini secara TEPAT:

{
    "name": "(string) Judul dokumen yang logis dan singkat. Contoh: 'Invoice Jasa Modifikasi #ITM/INV/1/26/002'.",
    "short_desc": "(string) Ringkasan satu kalimat mengenai isi dokumen. MAKSIMAL 80 karakter.",
    "description": "(string) Ringkasan detail yang mencakup fakta penting, item pekerjaan/barang, dan rincian nominal (seperti DPP, PPN).",
    "value": "(number) Nilai akhir/Total Tagihan/Grand Total. HANYA ANGKA MURNI. Hilangkan simbol 'Rp', spasi, dan semua tanda pemisah ribuan (titik/koma). Contoh: Jika di dokumen tertulis 'Rp 49,950,000', kembalikan angka 49950000. Jika tidak ditemukan, isi 0.",
    "activity_date": "(string) Tanggal utama dokumen diformat ketat sebagai YYYY-MM-DD. (Contoh: '28/Jan/2026' dikonversi menjadi '2026-01-28').",
    "from": "(string) Pihak pengirim, penerbit, atau pembuat dokumen (Contoh: PT INDOGREEN).",
    "to": "(string) Pihak penerima atau pelanggan (Contoh: KSO PT TIMAS SUPLINDO).",
    "kategori": "(string) WAJIB persis salah satu dari: 'Expense Report', 'Invoice', 'Invoice & FP', 'Purchase Order', 'Payment', 'Quotation', 'Faktur Pajak', 'Kasbon', 'Laporan Teknis', 'Surat Masuk', 'Surat Keluar', 'Kontrak', 'Berita Acara', 'Receive Item', 'Delivery Order', 'Legalitas', 'Other'. (Pilih 'Invoice' jika ini adalah tagihan).",
    "jenis": "(string) WAJIB persis salah satu dari: 'Internal', 'Customer', 'Vendor'. Gunakan 'Customer' untuk dokumen tagihan ke klien/pelanggan, 'Vendor' untuk tagihan dari supplier."
}

3. JANGAN tambahkan kunci lain di luar skema di atas.
4. Gunakan string kosong "" jika teks tidak ditemukan.
PROMPT;
    }

    /**
     * Sanitize and strictly validate the parsed AI response.
     */
    private function sanitize(array $data): array
    {
        $allowedKategori = [
            'Expense Report', 'Invoice', 'Invoice & FP', 'Purchase Order', 'Payment',
            'Quotation', 'Faktur Pajak', 'Kasbon', 'Laporan Teknis', 'Surat Masuk',
            'Surat Keluar', 'Kontrak', 'Berita Acara', 'Receive Item', 'Delivery Order',
            'Legalitas', 'Other',
        ];

        $allowedJenis = ['Internal', 'Customer', 'Vendor'];

        // Pembersihan ekstraksi nilai (value) untuk mengantisipasi AI yang tidak patuh
        $rawValue = $data['value'] ?? 0;
        if (is_string($rawValue)) {
            // Hapus semua karakter kecuali angka dan titik desimal
            // Ini akan merubah "Rp 49,950,000" atau "49.950.000,00" menjadi angka yang bisa diproses PHP
            $cleanValue = preg_replace('/[^0-9]/', '', $rawValue);
            // Asumsi tidak pakai sen/desimal di akhir untuk invoice Indonesia, 
            // jika ada, penanganannya mungkin butuh regex yg lebih spesifik.
            // Untuk case ini, ambil angka utuhnya saja.
            $finalValue = (float) $cleanValue; 
        } else {
            $finalValue = (float) $rawValue;
        }

        return [
            'name'          => substr((string) ($data['name'] ?? ''), 0, 255),
            'short_desc'    => substr((string) ($data['short_desc'] ?? ''), 0, 80),
            'description'   => (string) ($data['description'] ?? ''),
            'value'         => $finalValue,
            'activity_date' => $this->parseDate((string) ($data['activity_date'] ?? '')),
            'from'          => substr((string) ($data['from'] ?? ''), 0, 255),
            'to'            => substr((string) ($data['to'] ?? ''), 0, 255),
            'kategori'      => in_array($data['kategori'] ?? '', $allowedKategori, true)
                                    ? $data['kategori']
                                    : 'Other',
            'jenis'         => in_array($data['jenis'] ?? '', $allowedJenis, true)
                                    ? $data['jenis']
                                    : 'Internal',
        ];
    }

    /**
     * Validate and normalize a date string to YYYY-MM-DD.
     */
    private function parseDate(string $date): string
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $ts = strtotime($date);
            if ($ts !== false) {
                return date('Y-m-d', $ts);
            }
        }

        return date('Y-m-d'); // fallback: today
    }
}
