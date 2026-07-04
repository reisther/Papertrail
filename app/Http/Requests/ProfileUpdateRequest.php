<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'firstname' => ['required', 'string', 'max:255'],
            'middlename' => ['nullable', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'campus' => ['required', 'string', 'max:255'],
            'course' => ['required', 'string', 'max:255'],
            'section' => ['required', 'string', 'max:255'],
            'student_number' => ['nullable', 'string', 'max:50'],
            'profile_picture' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'adviser_schedule' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp,pdf,doc,docx', 'max:10240'],
            'delete_adviser_schedule' => ['nullable', 'boolean'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            'expertise' => ['nullable', 'array'],
            'expertise.*' => ['string', Rule::in([
                'Machine Learning',
                'AI Integration',
                'Cybersecurity',
                'IoT',
                'Cloud Computing',
                'Data Analytics',
                'Web Development',
                'Mobile Development',
                'Database Systems',
                'Networking',
            ])],
            'custom_expertise' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
