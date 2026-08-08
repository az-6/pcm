<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Approval;
use App\Models\FundSubmission;
use App\Models\FundSubmissionItem;
use App\Models\Majelis;
use App\Models\MajelisMembership;
use App\Models\ProgramReport;
use App\Models\ReportExpense;
use App\Models\WorkProgram;
use App\Enums\ReportStatus;
use App\Enums\SubmissionStatus;
use App\Enums\WorkProgramStatus;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $users = collect([
            ['name' => 'Satria Pratama', 'email' => 'superadmin@demo.test', 'global_role' => 'super-admin'],
            ['name' => 'Nur Aisyah', 'email' => 'pimpinan@demo.test', 'global_role' => 'pimpinan'],
            ['name' => 'Rizky Maulana', 'email' => 'admin.majelis@demo.test', 'global_role' => null],
            ['name' => 'Dina Kurnia', 'email' => 'ketua.bidang@demo.test', 'global_role' => null],
            ['name' => 'Fajar Hidayat', 'email' => 'anggota@demo.test', 'global_role' => null],
        ])->mapWithKeys(function (array $attributes): array {
            $user = User::updateOrCreate(
                ['email' => $attributes['email']],
                [...$attributes, 'password' => Hash::make('password'), 'email_verified_at' => now()],
            );

            return [$attributes['email'] => $user];
        });

        $education = Majelis::updateOrCreate([
            'code' => 'DIK',
        ], [
            'name' => 'Majelis Pendidikan',
            'description' => 'Program pendidikan dan pengembangan kapasitas anggota.',
            'is_active' => true,
        ]);

        $economy = Majelis::updateOrCreate([
            'code' => 'EKO',
        ], [
            'name' => 'Majelis Ekonomi',
            'description' => 'Program pemberdayaan ekonomi dan penguatan usaha warga.',
            'is_active' => true,
        ]);

        $memberships = [
            [$education, $users['admin.majelis@demo.test'], 'admin-majelis', 'Admin Majelis'],
            [$education, $users['ketua.bidang@demo.test'], 'ketua-bidang', 'Ketua Bidang Program'],
            [$education, $users['anggota@demo.test'], 'anggota', 'Anggota'],
            [$economy, $users['admin.majelis@demo.test'], 'admin-majelis', 'Admin Majelis'],
            [$economy, $users['ketua.bidang@demo.test'], 'ketua-bidang', 'Ketua Bidang UMKM'],
            [$economy, $users['anggota@demo.test'], 'anggota', 'Anggota'],
        ];

        foreach ($memberships as [$majelis, $user, $role, $position]) {
            MajelisMembership::updateOrCreate(
                ['majelis_id' => $majelis->id, 'user_id' => $user->id, 'role' => $role],
                ['position' => $position, 'is_active' => true],
            );
        }

        $literacy = WorkProgram::updateOrCreate(['code' => 'DIK-2026-01'], [
            'majelis_id' => $education->id,
            'created_by' => $users['ketua.bidang@demo.test']->id,
            'name' => 'Literasi Digital untuk Kader',
            'description' => 'Pelatihan praktis untuk memperkuat kemampuan digital organisasi.',
            'starts_on' => '2026-02-01',
            'ends_on' => '2026-06-30',
            'status' => WorkProgramStatus::Active,
            'budget' => 18500000,
        ]);

        $microBusiness = WorkProgram::updateOrCreate(['code' => 'EKO-2026-01'], [
            'majelis_id' => $economy->id,
            'created_by' => $users['ketua.bidang@demo.test']->id,
            'name' => 'Pendampingan UMKM Berdaya',
            'description' => 'Pendampingan pencatatan dan pemasaran digital bagi UMKM binaan.',
            'starts_on' => '2026-01-15',
            'ends_on' => '2026-08-31',
            'status' => WorkProgramStatus::Active,
            'budget' => 32000000,
        ]);

        WorkProgram::updateOrCreate(['code' => 'DIK-2026-02'], [
            'majelis_id' => $education->id,
            'created_by' => $users['ketua.bidang@demo.test']->id,
            'name' => 'Kelas Baca Anak Akhir Pekan',
            'description' => 'Kegiatan pendampingan membaca untuk anak usia sekolah dasar.',
            'starts_on' => '2026-08-10',
            'ends_on' => '2026-12-20',
            'status' => WorkProgramStatus::Draft,
            'budget' => 9750000,
        ]);

        WorkProgram::updateOrCreate(['code' => 'EKO-2025-03'], [
            'majelis_id' => $economy->id,
            'created_by' => $users['admin.majelis@demo.test']->id,
            'name' => 'Bazar Produk Warga',
            'description' => 'Bazar satu hari untuk mempertemukan pelaku usaha warga dan konsumen lokal.',
            'starts_on' => '2025-11-01',
            'ends_on' => '2025-11-30',
            'status' => WorkProgramStatus::Completed,
            'budget' => 14200000,
        ]);

        $submission = FundSubmission::updateOrCreate(['reference' => 'SUB-2026-0001'], [
            'work_program_id' => $literacy->id,
            'submitted_by' => $users['admin.majelis@demo.test']->id,
            'title' => 'Pengadaan perangkat pelatihan',
            'purpose' => 'Mendukung pelaksanaan tiga sesi pelatihan literasi digital.',
            'amount' => 12500000,
            'status' => SubmissionStatus::Submitted,
            'submitted_at' => now()->subDays(2),
        ]);
        FundSubmissionItem::updateOrCreate(['fund_submission_id' => $submission->id, 'description' => 'Sewa perangkat dan proyektor'], ['quantity' => 3, 'unit_price' => 2500000]);
        FundSubmissionItem::updateOrCreate(['fund_submission_id' => $submission->id, 'description' => 'Modul dan konsumsi peserta'], ['quantity' => 1, 'unit_price' => 5000000]);

        $report = ProgramReport::updateOrCreate(['reference' => 'RPT-2026-0001'], [
            'work_program_id' => $microBusiness->id,
            'submitted_by' => $users['admin.majelis@demo.test']->id,
            'title' => 'Laporan realisasi pendampingan UMKM tahap 1',
            'summary' => 'Empat belas UMKM menyelesaikan pendampingan pencatatan sederhana dan katalog digital.',
            'realized_amount' => 9200000,
            'status' => ReportStatus::Submitted,
            'submitted_at' => now()->subDay(),
        ]);
        ReportExpense::updateOrCreate(['program_report_id' => $report->id, 'description' => 'Lokakarya pencatatan keuangan'], ['amount' => 6200000, 'spent_on' => '2026-03-12']);
        ReportExpense::updateOrCreate(['program_report_id' => $report->id, 'description' => 'Produksi materi katalog digital'], ['amount' => 3000000, 'spent_on' => '2026-03-20']);

        Approval::updateOrCreate([
            'approvable_type' => FundSubmission::class,
            'approvable_id' => $submission->id,
            'reviewer_id' => $users['pimpinan@demo.test']->id,
        ], ['decision' => 'pending', 'decided_at' => now()]);
    }
}
