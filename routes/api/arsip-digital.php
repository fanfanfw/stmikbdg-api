<?php

use App\Http\Controllers\ArsipDigital\ArchiveFileController;
use App\Http\Controllers\ArsipDigital\AdminRequestAssignmentController;
use App\Http\Controllers\ArsipDigital\AdminDistributionController;
use App\Http\Controllers\ArsipDigital\AdminExportJobController;
use App\Http\Controllers\ArsipDigital\AdminRequestController;
use App\Http\Controllers\ArsipDigital\AdminTargetController;
use App\Http\Controllers\ArsipDigital\CategoryController;
use App\Http\Controllers\ArsipDigital\FoundationController;
use App\Http\Controllers\ArsipDigital\ScholarshipController;
use App\Http\Controllers\ArsipDigital\SegmentController;
use App\Http\Controllers\ArsipDigital\UserDistributionController;
use App\Http\Controllers\ArsipDigital\UserRequestController;
use Illuminate\Support\Facades\Route;

Route::prefix('/arsip-digital')
    ->middleware('auth.jwt')
    ->group(function () {
        Route::get('/me/archive-summary', [FoundationController::class, 'archiveSummary']);

        Route::get('/admin/settings', [FoundationController::class, 'settings']);
        Route::put('/admin/settings', [FoundationController::class, 'updateSettings']);

        Route::get('/categories', [CategoryController::class, 'index']);
        Route::post('/categories', [CategoryController::class, 'store']);
        Route::put('/categories/{category_id}', [CategoryController::class, 'update']);
        Route::delete('/categories/{category_id}', [CategoryController::class, 'destroy']);
        Route::post('/categories/{category_id}/restore', [CategoryController::class, 'restore']);

        Route::get('/files', [ArchiveFileController::class, 'index']);
        Route::post('/files', [ArchiveFileController::class, 'store']);
        Route::get('/files/{file_id}', [ArchiveFileController::class, 'show']);
        Route::get('/files/{file_id}/download', [ArchiveFileController::class, 'download']);
        Route::delete('/files/{file_id}', [ArchiveFileController::class, 'destroy']);
        Route::post('/files/{file_id}/restore', [ArchiveFileController::class, 'restore']);
        Route::post('/admin/files/upload-for-user', [AdminRequestAssignmentController::class, 'uploadForUser']);

        Route::get('/admin/requests', [AdminRequestController::class, 'index']);
        Route::get('/admin/targets', [AdminTargetController::class, 'index']);
        Route::post('/admin/requests', [AdminRequestController::class, 'store']);
        Route::post('/admin/requests/preview-targets', [AdminRequestController::class, 'previewTargets']);
        Route::get('/admin/requests/{request_id}', [AdminRequestController::class, 'show']);
        Route::put('/admin/requests/{request_id}', [AdminRequestController::class, 'update']);
        Route::delete('/admin/requests/{request_id}', [AdminRequestController::class, 'destroy']);
        Route::post('/admin/requests/{request_id}/publish', [AdminRequestController::class, 'publish']);
        Route::get('/admin/requests/{request_id}/assignments', [AdminRequestAssignmentController::class, 'assignments']);
        Route::get('/admin/requests/{request_id}/progress', [AdminRequestAssignmentController::class, 'progress']);
        Route::post('/admin/request-assignments/{assignment_id}/approve', [AdminRequestAssignmentController::class, 'approve']);
        Route::post('/admin/request-assignments/{assignment_id}/reject', [AdminRequestAssignmentController::class, 'reject']);
        Route::get('/admin/request-files/{request_file_id}/download', [AdminRequestAssignmentController::class, 'downloadRequestFile']);

        Route::get('/requests', [UserRequestController::class, 'index']);
        Route::get('/requests/{request_id}', [UserRequestController::class, 'show']);
        Route::post('/request-assignments/{assignment_id}/files/upload', [UserRequestController::class, 'upload']);
        Route::post('/request-assignments/{assignment_id}/files/reuse', [UserRequestController::class, 'reuse']);

        Route::get('/admin/distributions', [AdminDistributionController::class, 'index']);
        Route::post('/admin/distributions', [AdminDistributionController::class, 'store']);
        Route::post('/admin/distributions/preview-targets', [AdminDistributionController::class, 'previewTargets']);
        Route::get('/admin/distributions/{distribution_id}', [AdminDistributionController::class, 'show']);
        Route::put('/admin/distributions/{distribution_id}', [AdminDistributionController::class, 'update']);
        Route::delete('/admin/distributions/{distribution_id}', [AdminDistributionController::class, 'destroy']);
        Route::post('/admin/distributions/{distribution_id}/publish', [AdminDistributionController::class, 'publish']);
        Route::get('/admin/distributions/{distribution_id}/recipients', [AdminDistributionController::class, 'recipients']);
        Route::post('/admin/distribution-recipients/{recipient_id}/file', [AdminDistributionController::class, 'uploadRecipientFile']);

        Route::get('/distributions', [UserDistributionController::class, 'index']);
        Route::get('/distribution-files/{file_id}/download', [UserDistributionController::class, 'download']);

        Route::get('/admin/export-jobs', [AdminExportJobController::class, 'index']);
        Route::post('/admin/export-jobs', [AdminExportJobController::class, 'store']);
        Route::get('/admin/export-jobs/{export_job_id}', [AdminExportJobController::class, 'show']);
        Route::get('/admin/export-jobs/{export_job_id}/download', [AdminExportJobController::class, 'download']);

        Route::get('/admin/scholarship-types', [ScholarshipController::class, 'types']);
        Route::post('/admin/scholarship-types', [ScholarshipController::class, 'storeType']);
        Route::put('/admin/scholarship-types/{scholarship_type_id}', [ScholarshipController::class, 'updateType']);
        Route::delete('/admin/scholarship-types/{scholarship_type_id}', [ScholarshipController::class, 'deleteType']);
        Route::get('/admin/student-scholarships', [ScholarshipController::class, 'studentScholarships']);
        Route::post('/admin/student-scholarships', [ScholarshipController::class, 'storeStudentScholarship']);
        Route::post('/admin/student-scholarships/import', [ScholarshipController::class, 'importStudentScholarships']);
        Route::put('/admin/student-scholarships/{student_scholarship_id}', [ScholarshipController::class, 'updateStudentScholarship']);

        Route::get('/admin/segments', [SegmentController::class, 'index']);
        Route::post('/admin/segments', [SegmentController::class, 'store']);
        Route::get('/admin/segments/{segment_id}', [SegmentController::class, 'show']);
        Route::put('/admin/segments/{segment_id}', [SegmentController::class, 'update']);
        Route::delete('/admin/segments/{segment_id}', [SegmentController::class, 'destroy']);
        Route::post('/admin/segments/{segment_id}/members', [SegmentController::class, 'addMember']);
        Route::delete('/admin/segments/{segment_id}/members/{segment_member_id}', [SegmentController::class, 'deleteMember']);
        Route::post('/admin/segments/{segment_id}/import', [SegmentController::class, 'importMembers']);
    });
