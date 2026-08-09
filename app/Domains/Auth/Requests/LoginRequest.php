<?php

namespace App\Domains\Auth\Requests;

use App\Domains\User\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string'],
            'remember_me' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('email')) {
            $this->merge([
                'email' => Str::lower(trim((string) $this->input('email'))),
            ]);
        }
    }

    /**
     * Autentica o usuário e controla tentativas inválidas.
     *
     * @throws ValidationException
     */
    public function authenticate(): User
    {
        $this->ensureIsNotRateLimited();

        $authenticated = Auth::guard('web')->attempt(
            $this->only('email', 'password'),
            $this->boolean('remember_me'),
        );

        if (!$authenticated) {
            $this->failAuthentication();
        }

        /** @var User $user */
        $user = Auth::guard('web')->user();

        if (!$user->isActive()) {
            Auth::guard('web')->logout();
            RateLimiter::hit($this->throttleKey(), 60);

            throw ValidationException::withMessages([
                'email' => [$user->loginBlockMessage()],
            ]);
        }

        RateLimiter::clear($this->throttleKey());

        return $user;
    }

    /**
     * @throws ValidationException
     */
    private function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => ["Muitas tentativas de acesso. Tente novamente em {$seconds} segundos."],
        ]);
    }

    /**
     * @throws ValidationException
     */
    private function failAuthentication(): never
    {
        RateLimiter::hit($this->throttleKey(), 60);

        throw ValidationException::withMessages([
            'email' => ['E-mail ou senha inválidos.'],
        ]);
    }

    private function throttleKey(): string
    {
        return Str::transliterate(Str::lower((string) $this->input('email'))).'|'.$this->ip();
    }
}
