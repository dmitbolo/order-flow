<?php


namespace App\Actions\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginUserAction
{
    /**
     * @throws ValidationException
     */
    public function execute(string $email, string $password): string
    {
        $user = User::where('email', $email)->first();

        if (!$user || !Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => 'Неверный логин или пароль',
            ]);
        }

        return $user->createToken('api-token')->plainTextToken;
    }
}
