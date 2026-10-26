<?php

namespace App\Http\Controllers\Api\v1\Recruitment;

use App\Http\Controllers\Controller;
use App\Http\Requests\Recruitment\InterviewResultRequest;
use App\Http\Requests\Recruitment\InviteTestRequest;
use App\Http\Requests\Recruitment\ScheduleInterviewRequest;
use App\Http\Requests\Recruitment\StoreRecruitmentJobRequest;
use App\Http\Requests\Recruitment\StoreTestQuestionRequest;
use App\Http\Requests\Recruitment\StoreTestRequest;
use App\Http\Requests\Recruitment\UpdateCandidateStatusRequest;
use App\Http\Requests\Recruitment\UpdateRecruitmentJobRequest;
use App\Http\Requests\Recruitment\UpdateTestQuestionRequest;
use App\Http\Requests\Recruitment\UpdateTestRequest;
use App\Http\Requests\Recruitment\VerifyCandidateDocumentRequest;
use App\Http\Resources\Recruitment\CandidateApplicationResource;
use App\Http\Resources\Recruitment\CandidateDocumentResource;
use App\Http\Resources\Recruitment\CandidateResource;
use App\Http\Resources\Recruitment\InterviewResource;
use App\Http\Resources\Recruitment\RecruitmentJobResource;
use App\Http\Resources\Recruitment\TestQuestionResource;
use App\Http\Resources\Recruitment\TestResource;
use App\Models\Employee;
use App\Models\Notification;
use App\Models\Recruitment\Candidate;
use App\Models\Recruitment\CandidateApplication;
use App\Models\Recruitment\CandidateDocument;
use App\Models\Recruitment\Interview;
use App\Models\Recruitment\RecruitmentJob;
use App\Models\Recruitment\Test;
use App\Models\Recruitment\TestQuestion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * RMS — Endpoint ADMIN (ability:admin). Termasuk integrasi alur lama->baru saat HIRE.
 */
class AdminRecruitmentController extends Controller
{
    // ===================== LOWONGAN (recruitment_jobs) =====================

    public function jobs(Request $request): JsonResponse
    {
        $query = RecruitmentJob::query()
            ->with(['company', 'branch', 'department', 'position', 'tests'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('search'), fn ($q) =>
                $q->where('title', 'like', "%{$request->search}%")
                  ->orWhere('code', 'like', "%{$request->search}%"))
            ->orderByDesc('id');

        return response()->json([
            'success' => true,
            'message' => 'Daftar lowongan',
            'data' => RecruitmentJobResource::collection($query->paginate($request->integer('per_page', 15)))->response()->getData(true),
        ]);
    }

    public function storeJob(StoreRecruitmentJobRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;
        $data['vacancy_quota'] = $data['vacancy_quota'] ?? 1;

        $job = RecruitmentJob::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Lowongan dibuat.',
            'data' => new RecruitmentJobResource($job->load(['company', 'branch', 'department', 'position'])),
        ], 201);
    }

    public function showJob(RecruitmentJob $job): JsonResponse
    {
        $job->load(['company', 'branch', 'department', 'position', 'tests']);

        return response()->json([
            'success' => true,
            'message' => 'Detail lowongan',
            'data' => new RecruitmentJobResource($job),
        ]);
    }

    public function updateJob(UpdateRecruitmentJobRequest $request, RecruitmentJob $job): JsonResponse
    {
        $job->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Lowongan diperbarui.',
            'data' => new RecruitmentJobResource($job->fresh()->load(['company', 'branch', 'department', 'position'])),
        ]);
    }

    public function destroyJob(RecruitmentJob $job): JsonResponse
    {
        $job->delete();

        return response()->json([
            'success' => true,
            'message' => 'Lowongan dihapus.',
            'data' => null,
        ]);
    }

    // ===================== TEST + SOAL =====================

    public function tests(Request $request): JsonResponse
    {
        $query = Test::query()
            ->with(['recruitmentJob', 'questions'])
            ->when($request->filled('recruitment_job_id'), fn ($q) => $q->where('recruitment_job_id', $request->recruitment_job_id))
            ->orderByDesc('id');

        return response()->json([
            'success' => true,
            'message' => 'Daftar test',
            'data' => TestResource::collection($query->paginate($request->integer('per_page', 15)))->response()->getData(true),
        ]);
    }

    public function storeTest(StoreTestRequest $request): JsonResponse
    {
        $test = Test::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Test dibuat.',
            'data' => new TestResource($test),
        ], 201);
    }

    public function showTest(Test $test): JsonResponse
    {
        $test->load(['recruitmentJob', 'questions']);

        return response()->json([
            'success' => true,
            'message' => 'Detail test',
            'data' => new TestResource($test),
        ]);
    }

    public function updateTest(UpdateTestRequest $request, Test $test): JsonResponse
    {
        $test->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Test diperbarui.',
            'data' => new TestResource($test->fresh()->load('questions')),
        ]);
    }

    public function destroyTest(Test $test): JsonResponse
    {
        $test->delete();

        return response()->json([
            'success' => true,
            'message' => 'Test dihapus.',
            'data' => null,
        ]);
    }

    // ----- Soal (test_questions) -----

    public function storeQuestion(StoreTestQuestionRequest $request, Test $test): JsonResponse
    {
        $question = $test->questions()->create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Soal dibuat.',
            'data' => new TestQuestionResource($question),
        ], 201);
    }

    public function updateQuestion(UpdateTestQuestionRequest $request, Test $test, TestQuestion $question): JsonResponse
    {
        abort_if($question->test_id !== $test->id, 404, 'Soal tidak ditemukan pada test ini.');
        $question->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Soal diperbarui.',
            'data' => new TestQuestionResource($question),
        ]);
    }

    public function destroyQuestion(Test $test, TestQuestion $question): JsonResponse
    {
        abort_if($question->test_id !== $test->id, 404, 'Soal tidak ditemukan pada test ini.');
        $question->delete();

        return response()->json([
            'success' => true,
            'message' => 'Soal dihapus.',
            'data' => null,
        ]);
    }

    // ===================== CANDIDATES =====================

    public function candidates(Request $request): JsonResponse
    {
        $query = Candidate::query()
            ->with(['applications.job', 'documents'])
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->status))
            ->when($request->filled('job_id'), fn ($q) => $q->whereHas('applications', fn ($a) => $a->where('recruitment_job_id', $request->job_id)))
            ->when($request->filled('search'), fn ($q) =>
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('email', 'like', "%{$request->search}%")
                  ->orWhere('phone', 'like', "%{$request->search}%"))
            ->when($request->filled('verified'), function ($q) use ($request) {
                if ($request->boolean('verified')) {
                    $q->whereHas('documents', fn ($d) => $d->where('is_verified', true));
                } else {
                    $q->whereDoesntHave('documents', fn ($d) => $d->where('is_verified', true));
                }
            })
            ->orderByDesc('id');

        return response()->json([
            'success' => true,
            'message' => 'Daftar kandidat',
            'data' => CandidateResource::collection($query->paginate($request->integer('per_page', 15)))->response()->getData(true),
        ]);
    }

    public function candidateDetail(Candidate $candidate): JsonResponse
    {
        $candidate->load(['applications.job', 'applications.interviews', 'documents']);

        return response()->json([
            'success' => true,
            'message' => 'Detail kandidat',
            'data' => new CandidateResource($candidate),
        ]);
    }

    public function updateCandidateStatus(UpdateCandidateStatusRequest $request, Candidate $candidate): JsonResponse
    {
        $data = $request->validated();
        $candidate->update(['status' => $data['status']]);

        // sinkronkan application aktif
        if ($request->filled('application_id')) {
            $application = CandidateApplication::where('candidate_id', $candidate->id)->findOrFail($request->application_id);
            $application->update(['status' => $data['status'], 'notes' => $data['notes'] ?? $application->notes]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Status kandidat diperbarui.',
            'data' => new CandidateResource($candidate->fresh()->load('applications', 'documents')),
        ]);
    }

    // ===================== VERIFIKASI DOKUMEN =====================

    public function verifyDocument(VerifyCandidateDocumentRequest $request, Candidate $candidate, CandidateDocument $document): JsonResponse
    {
        abort_if($document->candidate_id !== $candidate->id, 404, 'Dokumen tidak ditemukan.');

        $document->update([
            'is_verified' => $request->boolean('is_verified'),
            'notes' => $request->input('notes'),
        ]);

        return response()->json([
            'success' => true,
            'message' => $request->boolean('is_verified') ? 'Dokumen terverifikasi.' : 'Verifikasi dokumen dibatalkan.',
            'data' => new CandidateDocumentResource($document),
        ]);
    }

    // ===================== INVITE TEST =====================

    public function inviteTest(InviteTestRequest $request, CandidateApplication $application): JsonResponse
    {
        $data = $request->validated();
        $test = Test::findOrFail($data['test_id']);

        // pastikan test terkait lowongan tsb (boleh test umum = recruitment_job_id null)
        if ($test->recruitment_job_id !== null && $test->recruitment_job_id !== $application->recruitment_job_id) {
            throw ValidationException::withMessages([
                'test_id' => ['Test tidak terkait dengan lowongan lamaran ini.'],
            ]);
        }

        $application->update(['status' => 'test_invited', 'notes' => 'Diundang test: ' . $test->name]);
        $application->candidate()->update(['status' => 'test_invited']);

        return response()->json([
            'success' => true,
            'message' => 'Kandidat diundang mengerjakan test.',
            'data' => new CandidateApplicationResource($application->fresh()->load('candidate', 'job')),
        ]);
    }

    // ===================== SCHEDULE INTERVIEW =====================

    public function scheduleInterview(ScheduleInterviewRequest $request, CandidateApplication $application): JsonResponse
    {
        $interview = Interview::create([
            'application_id' => $application->id,
            'mode' => $request->mode,
            'location' => $request->input('location'),
            'meeting_link' => $request->input('meeting_link'),
            'scheduled_at' => $request->input('scheduled_at'),
            'interviewer_id' => $request->input('interviewer_id'),
            'status' => 'scheduled',
        ]);

        $application->update(['status' => 'interview_invited']);
        $application->candidate()->update(['status' => 'interview_invited']);

        return response()->json([
            'success' => true,
            'message' => 'Wawancara dijadwalkan.',
            'data' => new InterviewResource($interview),
        ], 201);
    }

    // ===================== INTERVIEW RESULT =====================

    public function interviewResult(InterviewResultRequest $request, CandidateApplication $application, Interview $interview): JsonResponse
    {
        abort_if($interview->application_id !== $application->id, 404, 'Wawancara tidak ditemukan.');

        $data = $request->validated();

        $interview->update([
            'status' => $data['status'] ?? 'completed',
            'result_notes' => $data['result_notes'] ?? null,
            'rating' => $data['rating'] ?? $interview->rating,
            'decided' => $data['decided'],
        ]);

        // update status application sesuai keputusan
        $appStatus = match ($data['decided']) {
            'hire' => 'shortlisted',
            'reject' => 'rejected',
            'hold' => 'on_hold',
        };
        $application->update(['status' => $appStatus]);
        $application->candidate()->update(['status' => $appStatus]);

        return response()->json([
            'success' => true,
            'message' => 'Hasil wawancara disimpan.',
            'data' => new InterviewResource($interview->fresh()),
        ]);
    }

    // ===================== HIRE — INTEGRASI ALUR LAMA->BARU =====================

    public function hire(CandidateApplication $application): JsonResponse
    {
        return DB::transaction(function () use ($application) {
            $application->load(['candidate', 'job.company', 'job.branch', 'job.department', 'job.position']);

            if ($application->status === 'hired') {
                throw ValidationException::withMessages([
                    'application' => ['Lamaran ini sudah berstatus hired.'],
                ]);
            }

            $candidate = $application->candidate;
            $job = $application->job;

            // cek duplikat email
            $existing = Employee::where('email', $candidate->email)->first();
            if ($existing) {
                throw ValidationException::withMessages([
                    'email' => ['Email kandidat sudah terdaftar sebagai karyawan (ID: ' . $existing->employee_id . '). Tidak membuat duplikat.'],
                ]);
            }

            // job harus punya company & branch utk insert employees (keduanya Wajib di tabel employees)
            $companyId = $job->company_id ?? $job->branch?->company_id;
            $branchId = $job->branch_id;

            if (!$companyId || !$branchId) {
                throw ValidationException::withMessages([
                    'job' => ['Lowongan wajib memiliki company_id dan branch_id agar bisa dibuatkan karyawan baru.'],
                ]);
            }

            $employee = Employee::create([
                'employee_id' => $this->nextEmployeeId(),
                'name' => $candidate->name,
                'email' => $candidate->email,
                'phone' => $candidate->phone,
                'password' => 'password123', // default, hashed otomatis oleh cast model
                'birth_date' => $candidate->birth_date,
                'gender' => $candidate->gender,
                'address' => $candidate->address,
                'photo' => $candidate->photo,
                'ktp_number' => $candidate->ktp_number,
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'department_id' => $job->department_id,
                'division_id' => $job->position?->division_id,
                'position_id' => $job->position_id,
                'join_date' => now()->toDateString(),
                'employment_status' => 'probation',
                'is_active' => true,
            ]);

            // update status application + candidate jadi hired
            $application->update([
                'status' => 'hired',
                'notes' => 'Diterima bekerja (karyawan: ' . $employee->employee_id . ')',
            ]);
            $candidate->update(['status' => 'hired']);

            // kirim notifikasi ke employee baru (tabel notifications employee_id)
            Notification::create([
                'employee_id' => $employee->id,
                'type' => 'recruitment',
                'title' => 'Selamat, Anda diterima! 🎉',
                'body' => 'Selamat ' . $employee->name . '! Anda diterima sebagai ' . ($job->position->name ?? $job->title) . ' di ' . ($job->company->name ?? 'perusahaan') . '. Akun login: email Anda / password123. Silakan login melalui aplikasi.',
                'data' => [
                    'application_id' => $application->id,
                    'job_title' => $job->title,
                    'employee_id' => $employee->id,
                ],
                'is_read' => false,
                'read_at' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Kandidat di-HIRE dan karyawan baru berhasil dibuat.',
                'data' => [
                    'application' => new CandidateApplicationResource($application->fresh()->load('candidate', 'job', 'interviews')),
                    'employee' => [
                        'id' => $employee->id,
                        'employee_id' => $employee->employee_id,
                        'name' => $employee->name,
                        'email' => $employee->email,
                        'company_id' => $employee->company_id,
                        'branch_id' => $employee->branch_id,
                        'department_id' => $employee->department_id,
                        'position_id' => $employee->position_id,
                        'join_date' => $employee->join_date->toDateString(),
                        'employment_status' => $employee->employment_status,
                    ],
                    'notification_sent' => true,
                    'default_password' => 'password123',
                ],
            ], 201);
        });
    }

    /** Auto-generate EMP-XXXX */
    protected function nextEmployeeId(): string
    {
        $last = Employee::query()
            ->where('employee_id', 'like', 'EMP-%')
            ->orderByDesc('employee_id')
            ->value('employee_id');

        $num = 1;
        if ($last && preg_match('/^EMP-(\d+)$/', $last, $m)) {
            $num = (int) $m[1] + 1;
        }

        // pastikan unik
        while (Employee::where('employee_id', 'EMP-' . str_pad($num, 4, '0', STR_PAD_LEFT))->exists()) {
            $num++;
        }

        return 'EMP-' . str_pad($num, 4, '0', STR_PAD_LEFT);
    }
}
