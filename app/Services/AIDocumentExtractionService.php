<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIDocumentExtractionService
{
    private string $baseUrl;
    private string $apiKey;
    private string $model;

    private const IMAGE_MIMES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.ai.base_url'), '/');
        $this->apiKey  = config('services.ai.api_key');
        $this->model   = config('services.ai.model');
    }

    public function extract(UploadedFile $file, ?int $projectId = null): array
    {
        if (empty($this->apiKey)) {
            throw new \RuntimeException('AI_API_KEY is not configured.');
        }

        $context = $this->buildProjectContext($projectId);
        $mimeType = $file->getClientMimeType();
        // Menentukan apakah file adalah gambar untuk menggunakan Vision API atau sekadar teks
        $isImage  = in_array($mimeType, self::IMAGE_MIMES, true);

        $messages = $isImage
            ? $this->buildVisionMessages($file, $mimeType, $context)
            : $this->buildTextMessages($file, $context);

        $payload = [
            'model'       => $this->model,
            'messages'    => $messages,
            'temperature' => 0.1, // Suhu rendah untuk akurasi data yang lebih konsisten
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

        $rawText = $responseData['choices'][0]['message']['content'] ?? '';

        if (empty(trim($rawText))) {
            Log::error('Provider AI returned empty content', ['response' => $responseData]);
            throw new \RuntimeException('AI returned an empty response. Please try again.');
        }

        // Membersihkan blok kode markdown (```json ... ```) dari output AI agar bisa di-parse sebagai JSON murni
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

    private function buildVisionMessages(UploadedFile $file, string $mimeType, string $context): array
    {
        // Mengonversi gambar ke base64 agar bisa dikirimkan ke model vision
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
                            'detail' => 'high', // Menggunakan detail tinggi untuk pembacaan teks yang lebih akurat
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

    private function buildTextMessages(UploadedFile $file, string $context): array
    {
        $rawContent = @file_get_contents($file->getRealPath());
        // Membersihkan karakter non-printable/aneh dari data teks mentah
        $textContent = preg_replace('/[^\x20-\x7E\xA0-\xFF\n\r\t]/u', ' ', $rawContent ?? '');
        // Membatasi isi teks agar tidak melebihi batasan token model (keep within token budget)
        $textContent = mb_substr(trim($textContent), 0, 8000);

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

    private function buildProjectContext(?int $projectId): string
    {
        // Menyediakan petunjuk (context hints) ke AI guna meningkatkan akurasi identifikasi mitra/customer
        $lines = [];

        if ($projectId) {
            $project = \App\Models\Project::with('mitra')->find($projectId);
            if ($project) {
                $customer = $project->mitra ? $project->mitra->nama : 'N/A';
                $lines[] = "Nama Proyek: {$project->name}";
                $lines[] = "Customer Proyek: {$customer}";
            } else {
                $lines[] = 'Data proyek tidak ditemukan.';
            }
        } else {
            $lines[] = 'Tidak ada konteks proyek spesifik.';
        }

        // Menyertakan daftar vendor yang tersedia agar AI bisa mencocokkan nama vendor → ID
        $vendors = \App\Models\Mitra::where('is_vendor', true)->get(['id', 'nama']);
        if ($vendors->isNotEmpty()) {
            $lines[] = '';
            $lines[] = 'DAFTAR VENDOR TERSEDIA (gunakan untuk mencocokkan mitra_id jika jenis=Vendor):';
            foreach ($vendors as $v) {
                $lines[] = "- ID: {$v->id}, Nama: {$v->nama}";
            }
        }

        return implode("\n", $lines);
    }

    private function systemPrompt(): string
    {
        // Instruksi ketat ke model AI dalam Bahasa Indonesia agar output stabil berbentuk JSON
        return <<<'PROMPT'
Anda adalah alat ekstraksi data otomatis yang sangat presisi. Tugas SATU-SATUNYA Anda adalah menganalisis dokumen dan mengembalikan data dalam format JSON terstruktur.

ATURAN PENTING:
1. Kembalikan HANYA objek JSON mentah. Tanpa markdown, tanpa blok kode (```), tanpa teks penjelasan apapun.
2. JSON harus menggunakan KUNCI dan BATASAN NILAI berikut ini secara TEPAT:

{
    "name": "(string) Judul dokumen yang logis dan singkat. Contoh: 'Kasbon Kekurangan Alat Project Cisem-2 #KB/I-2026/Log/045'.",
    "jenis": "(string) WAJIB persis salah satu dari: 'Internal', 'Customer', 'Vendor'. Gunakan 'Internal' untuk dokumen internal perusahaan (kasbon, memo, surat internal). Gunakan 'Customer' untuk dokumen yang melibatkan klien/pelanggan. Gunakan 'Vendor' untuk dokumen yang melibatkan supplier/vendor.",
    "mitra_id": "(number|null) ID vendor dari DAFTAR VENDOR yang diberikan di PROJECT CONTEXT. HANYA diisi jika jenis='Vendor'. Cocokkan nama vendor/supplier di dokumen dengan nama vendor di daftar. Jika tidak cocok atau jenis bukan 'Vendor', isi null."
    "kategori": "(string) WAJIB persis salah satu dari: 'Expense Report', 'Invoice', 'Invoice & FP', 'Purchase Order', 'Payment', 'Quotation', 'Faktur Pajak', 'Kasbon', 'Laporan Teknis', 'Surat Masuk', 'Surat Keluar', 'Kontrak', 'Berita Acara', 'Receive Item', 'Delivery Order', 'Legalitas', 'Other'. (Pilih 'Invoice' jika ini adalah tagihan).",
    "from": "(string) Pihak pengirim/pembuat dokumen. Lihat ATURAN FROM & TO di bawah.",
    "to": "(string) Pihak penerima dokumen. Lihat ATURAN FROM & TO di bawah.",
    "short_desc": "(string) Ringkasan satu kalimat mengenai isi dokumen. MAKSIMAL 80 karakter.",
    "description": "(string) Ringkasan detail yang mencakup fakta penting, item pekerjaan/barang, dan rincian nominal (seperti DPP, PPN).",
    "value": "(number) Nilai akhir/Total Tagihan/Grand Total. HANYA ANGKA MURNI. Hilangkan simbol 'Rp', spasi, dan semua tanda pemisah ribuan (titik/koma). Contoh: Jika di dokumen tertulis 'Rp 49,950,000', kembalikan angka 49950000. Jika tidak ditemukan, isi 0.",
    "activity_date": "(string) Tanggal utama dokumen diformat ketat sebagai YYYY-MM-DD. (Contoh: '28/Jan/2026' dikonversi menjadi '2026-01-28').",
}

3. ATURAN FROM & TO (WAJIB DIPATUHI SECARA KETAT):
    a. Jika jenis = 'Internal':
        - Opsi 1: from = "NAMA PEMOHON/PEMBUAT" (ambil dari dokumen, misal nama yang tertera di field 'Nama Pemohon', 'Dilaporkan Oleh', atau penandatangan), to = "INDOGREEN"
        - Opsi 2: from = "INDOGREEN", to = "NAMA ORANG" (penerima yang tertera di dokumen)
        - Gunakan NAMA ORANG ASLI dari dokumen, bukan label generik. Contoh: from = "Ujang Winarya", to = "INDOGREEN"
    b. Jika jenis = 'Customer':
        - Opsi 1: from = "CUSTOMER", to = "INDOGREEN"
        - Opsi 2: from = "INDOGREEN", to = "CUSTOMER"
        - WAJIB gunakan kata "CUSTOMER" saja, JANGAN gunakan nama perusahaan customer.
    c. Jika jenis = 'Vendor':
        - Opsi 1: from = "VENDOR", to = "INDOGREEN"
        - Opsi 2: from = "INDOGREEN", to = "VENDOR"
        - WAJIB gunakan kata "VENDOR" saja, JANGAN gunakan nama perusahaan vendor.
    d. Tentukan arah (siapa from, siapa to) berdasarkan konteks dokumen: siapa yang MENGIRIM/MEMBUAT dan siapa yang MENERIMA.
    e. JANGAN menulis alamat, jabatan, atau informasi tambahan di field from/to. Hanya nama singkat.

4. JANGAN tambahkan kunci lain di luar skema di atas.
5. Gunakan string kosong "" jika teks tidak ditemukan. Gunakan null untuk mitra_id jika tidak berlaku.
PROMPT;
    }

    private function sanitize(array $data): array
    {
        $allowedKategori = [
            'Expense Report', 'Invoice', 'Invoice & FP', 'Purchase Order', 'Payment',
            'Quotation', 'Faktur Pajak', 'Kasbon', 'Laporan Teknis', 'Surat Masuk',
            'Surat Keluar', 'Kontrak', 'Berita Acara', 'Receive Item', 'Delivery Order',
            'Legalitas', 'Other',
        ];

        $allowedJenis = ['Internal', 'Customer', 'Vendor'];

        // Mengonversi nilai nominal dari format teks/ribuan ke angka float murni
        $rawValue = $data['value'] ?? 0;
        if (is_string($rawValue)) {
            $cleanValue = preg_replace('/[^0-9.]/', '', str_replace(',', '', $rawValue));
            $finalValue = (float) $cleanValue; 
        } else {
            $finalValue = (float) $rawValue;
        }

        $jenis = in_array($data['jenis'] ?? '', $allowedJenis, true)
            ? $data['jenis']
            : 'Internal';

        // Validasi mitra_id: hanya berlaku jika jenis = 'Vendor' dan ID vendor ada di database
        $mitraId = null;
        if ($jenis === 'Vendor' && !empty($data['mitra_id'])) {
            $vendorExists = \App\Models\Mitra::where('id', (int) $data['mitra_id'])
                ->where('is_vendor', true)
                ->exists();
            if ($vendorExists) {
                $mitraId = (int) $data['mitra_id'];
            }
        }

        return [
            'name'          => substr((string) ($data['name'] ?? ''), 0, 255),
            'short_desc'    => substr((string) ($data['short_desc'] ?? ''), 0, 80),
            'description'   => (string) ($data['description'] ?? ''),
            'value'         => $finalValue,
            'activity_date' => $this->parseDate((string) ($data['activity_date'] ?? '')),
            'from'          => substr((string) ($data['from'] ?? ''), 0, 255),
            'to'            => substr((string) ($data['to'] ?? ''), 0, 255),
            // Memastikan kategori dan jenis sesuai dengan daftar ENUM yang diizinkan sistem
            'kategori'      => in_array($data['kategori'] ?? '', $allowedKategori, true)
                                    ? $data['kategori']
                                    : 'Other',
            'jenis'         => $jenis,
            // Mitra ID vendor yang sudah divalidasi terhadap database
            'mitra_id'      => $mitraId,
        ];
    }

    private function parseDate(string $date): string
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $ts = strtotime($date);
            if ($ts !== false) {
                return date('Y-m-d', $ts);
            }
        }

        return date('Y-m-d');
    }
}
