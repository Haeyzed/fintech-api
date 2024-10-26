<?php

namespace App\Http\Requests;

class BlockedIpRequest extends BaseRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            /**
             * The IP address to be blocked.
             * @var string $ip_address
             * @example "192.168.1.1"
             */
            'ip_address' => ['required', 'ip'],

            /**
             * The reason for blocking the IP address.
             * @var string $reason
             * @example "Suspicious activity detected"
             */
            'reason' => ['nullable', 'string', 'max:255'],

            /**
             * The date and time until which the IP address should be blocked.
             * @var string $blocked_until
             * @example "2023-12-31 23:59:59"
             */
            'blocked_until' => ['nullable', 'date', 'after:now'],
        ];
    }
}
