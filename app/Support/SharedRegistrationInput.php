<?php

namespace App\Support;

use Illuminate\Http\Request;

final class SharedRegistrationInput
{
    public static function normalize(Request $request): void
    {
        $values = [];
        if (is_string($request->input('email'))) {
            $values['email'] = mb_strtolower(trim($request->input('email')));
        }
        if (is_string($request->input('mobile_no'))) {
            // Accept presentation separators, but never turn letters into a
            // different valid number by silently discarding them.
            $values['mobile_no'] = preg_replace('/[\s()+.\-]/', '', $request->input('mobile_no'));
        }
        $request->merge($values);
    }

    public static function messages(): array
    {
        return [
            'email.unique' => 'This email is already registered with Artera. Please log in.',
            'mobile_no.unique' => 'This mobile number is already registered with Artera. Please log in.',
        ];
    }
}
