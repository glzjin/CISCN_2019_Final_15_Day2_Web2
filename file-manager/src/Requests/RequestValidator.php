<?php

namespace Ciscn\FM\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Storage;

class RequestValidator extends FormRequest
{
    use CustomErrorMessage;

    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'disk' => [
                'sometimes',
                'string',
                function ($attribute, $value, $fail) {
                    if (!in_array($value, config('file-manager.diskList')) ||
                        !array_key_exists($value, config('filesystems.disks'))
                    ) {
                        return $fail(trans('file-manager::response.diskNotFound'));
                    }
                },
            ]
        ];
    }

    /**
     * Not found message
     *
     * @return array|\Illuminate\Contracts\Translation\Translator|string|null
     */
    public function message()
    {
        return trans('file-manager::response.notFound');
    }
}
