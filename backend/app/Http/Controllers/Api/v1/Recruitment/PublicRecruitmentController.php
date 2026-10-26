<?php

namespace App\Http\Controllers\Api\v1\Recruitment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recruitment\ApplyRecruitmentRequest;
use App\Http\Requests\Recruitment\SubmitTestRequest;
use App\Http\Resources\Recruitment\CandidateApplicationResource;
use App\Http\Resources\Recruitment\CandidateDocumentResource;
use App\Http\Resources\Recruitment\CandidateResource;
use App\Http\Resources\Recruitment\RecruitmentJobResource;
use App\Http\Resources\Recruitment\TestAttemptResource;
use App\Http\Resources\Recruitment\TestQuestionResource;
use App\Http\Resources\Recruitment\TestResource;
use App\Models\Recruitment\Candidate;
use App\Models\Recruitment\CandidateApplication;
use App\Models\Recruitment\CandidateDocument;
use App\Models\Recruitment\RecruitmentJob;
use App\Models\Recruitment\Test;
use App\Models\Recruitment\TestAnswer;
use App\Models\Recruitment\TestAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * RMS — Endpoint PUBLIC (tanpa auth) untuk kandidat.
 */
class PublicRecruitmentController extends Controller
{
    /** GET /recruitment/jobs — daftar lowongan yang open */
    public function jobs(Request $request): JsonResponse
    {
        $query = RecruitmentJob::query()
            ->with(['company', 'branch', 'department', 'position', 'tests'])
            ->where('status', 'open')
            ->orderByDesc('id');

        $pagination = $query->paginate($request->integer('per_page', 20));

        return response()->json([
            'success' => true,
            'message' => 'Daftar lowongan',
            'data' => RecruitmentJobResource::collection($pagination)->response()->getData(true),
        ]);
    }

    /** GET /recruitment/jobs/{job} — detail lowongan + tests terpaut */
    public function jobDetail(RecruitmentJob $job): JsonResponse
    {
        $job->load(['company', 'branch', 'department', 'position', 'tests']);

        return response()->json([
            'success' => true,
            'message' => 'Detail lowongan',
            'data' => new RecruitmentJobResource($job),
        ]);
    }

    /** POST /recruitment/apply — daftar jadi candidate + apply ke lowongan (+upload dokumen) */
    public function apply(ApplyRecruitmentRequest $request): JsonResponse
    {
        $data = $request->validated();

        return DB::transaction(function () use ($data, $request) {
            $job = RecruitmentJob::findOrFail($data['job_id']);

            if ($job->status !== 'open') {
                throw ValidationException::withMessages([
                    'job_id' => ['Lowongan tidak sedang dibuka.'],
                ]);
            }

            $candidate = Candidate::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'phone' => $data['phone'] ?? null,
                    'birth_date' => $data['birth_date'] ?? null,
                    'gender' => $data['gender'] ?? null,
                    'address' => $data['address'] ?? null,
                    'ktp_number' => $data['ktp_number'] ?? null,
                    'source' => $data['source'] ?? null,
                    'status' => 'applied',
                    'applied_date' => now()->toDateString(),
                ]
            );

            // Upload dokumen bila ada
            if (!empty($data['documents'])) {
                foreach ($data['documents'] as $doc) {
                    $path = $doc['file']->store('recruitment/candidates/' . $candidate->id, 'public');
                    CandidateDocument::create([
                        'candidate_id' => $candidate->id,
                        'document_type' => $doc['document_type'],
                        'file_path' => $path,
                        'original_name' => $doc['file']->getClientOriginalName(),
                        'is_verified' => false,
                    ]);
                }
            }

            // Buat/ambil application (unique candidate+job)
            $application = CandidateApplication::firstOrCreate(
                ['candidate_id' => $candidate->id, 'recruitment_job_id' => $job->id],
                ['status' => 'applied', 'applied_at' => now()]
            );

            // update status candidate ke applied
            if ($candidate->status === 'hired' || $candidate->status === 'applied') {
                // biarkan
            } else {
                $candidate->update(['status' => 'applied']);
            }

            return response()->json([
                'success' => true,
                'message' => 'Lamaran terkirim.',
                'data' => [
                    'candidate' => new CandidateResource($candidate->load('documents')),
                    'application_id' => $application->id,
                    'application_status' => $application->status,
                ],
            ], 201);
        });
    }

    /** POST /recruitment/documents — upload dokumen tambahan utk candidate yang sudah terdaftar */
    public function uploadDocuments(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'candidate_id' => 'required|integer|exists:candidates,id',
            'documents' => 'required|array',
            'documents.*.document_type' => 'required|in:cv,kk,ktp,foto,skck,ijazah,lainnya',
            'documents.*.file' => 'required|file|mimes:pdf,jpeg,jpg,png,doc,docx|max:4096',
        ]);

        $candidate = Candidate::findOrFail($validated['candidate_id']);
        $docs = [];

        foreach ($validated['documents'] as $doc) {
            $path = $doc['file']->store('recruitment/candidates/' . $candidate->id, 'public');
            $docs[] = CandidateDocument::create([
                'candidate_id' => $candidate->id,
                'document_type' => $doc['document_type'],
                'file_path' => $path,
                'original_name' => $doc['file']->getClientOriginalName(),
                'is_verified' => false,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Dokumen terunggah.',
            'data' => CandidateDocumentResource::collection(collect($docs))->response()->getData(true),
        ], 201);
    }

    /** GET /recruitment/tests/{test} — soal test utk kandidat (tanpa kunci jawaban) */
    public function testDetail(Test $test): JsonResponse
    {
        abort_if(!$test->status, 404, 'Test tidak tersedia.');

        $questions = $test->questions()
            ->orderBy('id')
            ->get()
            ->map(fn ($q) => (new TestQuestionResource($q))->forCandidate(true));

        return response()->json([
            'success' => true,
            'message' => 'Soal test',
            'data' => [
                'test' => new TestResource($test),
                'questions' => $questions,
            ],
        ]);
    }

    /** POST /recruitment/tests/{test}/submit — kerjakan & hitung skor otomatis */
    public function submitTest(SubmitTestRequest $request, Test $test): JsonResponse
    {
        $data = $request->validated();

        abort_if(!$test->status, 404, 'Test tidak tersedia.');

        // identifikasi candidate
        $candidate = $data['candidate_id'] ?? null
            ? Candidate::findOrFail($data['candidate_id'])
            : Candidate::where('email', $data['email'])->firstOrFail();

        // ambil application (job test ini) utk update status
        $application = CandidateApplication::where('candidate_id', $candidate->id)
            ->when($test->recruitment_job_id, fn ($q) => $q->where('recruitment_job_id', $test->recruitment_job_id))
            ->latest('id')
            ->first();

        $questions = $test->questions()->get()->keyBy('id');
        $correctCount = 0;
        $totalScore = 0;

        $attempt = TestAttempt::create([
            'test_id' => $test->id,
            'candidate_id' => $candidate->id,
            'application_id' => $application?->id,
            'started_at' => now(),
            'status' => 'in_progress',
        ]);

        foreach ($data['answers'] as $answer) {
            $question = $questions->get($answer['question_id']);
            if (!$question) {
                continue;
            }

            $chosen = $answer['chosen'] ?? null;
            $isCorrect = $chosen !== null && strtoupper($chosen) === $question->correct_answer;

            if ($isCorrect) {
                $correctCount++;
                $totalScore += (int) $question->score;
            }

            TestAnswer::create([
                'test_attempt_id' => $attempt->id,
                'question_id' => $question->id,
                'chosen_answer' => $chosen ? strtoupper($chosen) : null,
                'is_correct' => $isCorrect,
            ]);
        }

        $maxScore = $test->questions()->sum('score');
        $percentage = $maxScore > 0 ? round(($totalScore / $maxScore) * 100) : 0;

        $attempt->update([
            'score' => $percentage,
            'total_questions' => $questions->count(),
            'correct_count' => $correctCount,
            'submitted_at' => now(),
            'status' => 'submitted',
        ]);

        // update status application & candidate (test_done jika lulus passing, on_hold jika gagal)
        if ($application) {
            if ($percentage >= $test->passing_score) {
                $application->update(['status' => 'test_done']);
                $candidate->update(['status' => 'test_done']);
            } else {
                $application->update(['status' => 'rejected']);
                $candidate->update(['status' => 'rejected']);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Test disubmit.',
            'data' => [
                'attempt' => new TestAttemptResource($attempt->load('answers', 'test')),
                'score' => $percentage,
                'correct_count' => $correctCount,
                'total_questions' => $questions->count(),
                'passing_score' => $test->passing_score,
                'passed' => $percentage >= $test->passing_score,
            ],
        ], 201);
    }

    /** GET /recruitment/status/{nik} — status lamaran kandidat berdasarkan NIK */
    public function status($nik): JsonResponse
    {
        $candidate = Candidate::where('ktp_number', $nik)->firstOrFail();
        $candidate->load(['applications.job.tests', 'applications.interviews', 'applications.attempts', 'documents']);

        return response()->json([
            'success' => true,
            'message' => 'Status lamaran',
            'data' => new CandidateResource($candidate),
        ]);
    }
}
