<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\JsonApi;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;

class VerifyEmailController extends Controller
{
    /**
     * The "signed" route middleware already validated the URL signature
     * and expiry before this action runs. We re-check the email hash as
     * defense in depth, mirroring Laravel's own default implementation.
     */
    public function verify(int $id, string $hash): JsonResponse
    {
        /**
         * @var User $user
         */
        $user = User::findOrFail($id);

        if (! hash_equals($hash, sha1($user->getEmailForVerification()))) {
            abort(403, 'Invalid verification link.');
        }

        if ($user->hasVerifiedEmail()) {
            return JsonApi::meta(['message' => 'Email address already verified.']);
        }

        $user->markEmailAsVerified();

        event(new Verified($user));

        return JsonApi::meta(['message' => 'Email address verified successfully.']);
    }
}
