<?php

namespace App\Http\Controllers;

use App\Mail\PendingMail;
use App\Models\PendingRegistration;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class PendingController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $pendings = PendingRegistration::with('student')->get();

        return view('pendings.index', compact('pendings'));
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $pending = PendingRegistration::with('student')->findOrFail($id);

        return view('pendings.details', compact('pending'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $req, string $id)
    {
        $validated = $req->validate([
            'status' => 'required',
            'student_id' => 'required|exists:students,id',
            'relationship' => 'required|string',
        ]);

        $pending_applicant = PendingRegistration::findOrFail($id);

        $pending_applicant->update([
            'status' => $validated['status'],
        ]);

        $default_password = '';

        if ($pending_applicant->status === 'Approved') {
            $default_password = $pending_applicant->first_name[0]
                . str_replace(' ', '', $pending_applicant->last_name)
                . Carbon::parse($pending_applicant->birthdate)->year;

            $new_user = User::create([
                'first_name' => $pending_applicant['first_name'],
                'last_name' => $pending_applicant['last_name'],
                'middle_name' => $pending_applicant['middle_name'],
                'suffix' => $pending_applicant['suffix'],
                'gender' => $pending_applicant['gender'],
                'birthdate' => $pending_applicant['birthdate'],
                'email' => $pending_applicant['email'],
                'password' => Hash::make($default_password),
                'phone_number' => $pending_applicant['phone_number'],
                'profile_image_url' => null,
                'status' => 'Active',
            ]);

            $new_parent_user = $new_user->parent_guardian()->create([
                'user_id' => $new_user->id,
                'parent_code' => 'PG-' . str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT),
                'occupation'=> $pending_applicant['occupation'],
            ]);

            $new_parent_user->students()->attach(
                $validated['student_id'],
                ['relationship' => $validated['relationship']]
            );
        }

        $this->sendResultEmail($pending_applicant->last_name, $pending_applicant->email, $default_password,
            $validated['status'], $pending_applicant->gender);

        $pending_applicant->delete();

        return redirect()->route('web.pendings.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    protected function sendResultEmail(string $lastname, string $email, string $password, string $status, string $gender)
    {
        Mail::to($email)
            ->send(new PendingMail($lastname, $password, $status, $gender));

        return true;
    }
}
