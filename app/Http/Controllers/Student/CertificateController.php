<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class CertificateController extends Controller
{
    /**
     * Display a specific certificate for the authenticated student.
     */
    public function show(Request $request, Certificate $certificate): View
    {
        $user = $request->user();

        // Enforce strict ownership authorization (IDOR protection)
        if ((int) $certificate->user_id !== (int) $user->id && ! $user->isAdmin()) {
            abort(403, 'Unauthorized access to this certificate.');
        }

        $certificate->loadMissing(['course', 'user']);

        return view('student.certificates.show', [
            'certificate' => $certificate,
            'course' => $certificate->course,
            'user' => $certificate->user,
            'headerTitle' => 'Certificate of Completion',
        ]);
    }
}