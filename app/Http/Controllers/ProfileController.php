<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form
     *
     * Returns a view with the authenticated user's profile data
     * for editing purposes.
     *
     * @param Request $request The authenticated request
     * @return View The profile edit view
     *
     * @source None (returns view with authenticated user)
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information
     *
     * Validates and updates the authenticated user's profile data.
     * If the email is changed, the email_verified_at field is reset to null
     * to require re-verification.
     *
     * @param ProfileUpdateRequest $request Contains validated profile data
     * @return RedirectResponse Redirects back to profile edit page with success status
     *
     * @source Database: Authenticated User (updates)
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account
     *
     * Validates the user's password, logs them out, deletes their account,
     * invalidates their session, and regenerates the CSRF token.
     *
     * @param Request $request The authenticated request with password confirmation
     * @return RedirectResponse Redirects to home page after account deletion
     *
     * @source Database: Authenticated User (deletes)
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
