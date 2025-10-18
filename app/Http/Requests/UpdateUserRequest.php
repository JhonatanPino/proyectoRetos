<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // admin o el propio usuario pueden actualizar
        $user = $this->user();
        $targetId = $this->route('user')?->id;
        return $user && ($user->role === 'admin' || $user->id === (int) $targetId);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        $userId = $this->route('user')?->id ?? null;

        return [
            'username' => ['sometimes','string','max:255', Rule::unique('users','username')->ignore($userId)],
            'password' => ['sometimes','nullable','string','min:6'],
            'score'    => ['sometimes','integer'],
            'role'     => ['sometimes', Rule::in(['admin','user'])],
        ];
    }
}
