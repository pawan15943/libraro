<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLibraryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        if ($this->is('library/store')) {
            return $this->libraryStoreRules();
        }
        return [];
    }

    // Validation rules for library store form
    protected function libraryStoreRules()
    {
        return [
            'library_name'   => 'required|string|max:255',
            'library_email'  => 'required|email|max:255',
            'library_mobile' => 'required|digits:10',
            'state_id'       => 'required|exists:states,id',
            'city_id'        => 'required|exists:cities,id',
            'library_address'=> 'required|string|max:500',
            'library_zip'    => 'nullable|digits:6',
            'library_type'   => 'nullable|string|max:255',
            'library_owner'  => 'nullable|string|max:255',
            'library_logo'   => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            
        ];
    }

}
