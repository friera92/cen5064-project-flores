<?php

namespace App\Http\Requests;

use App\Domain\States\ToolCondition;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use App\Domain\States\ToolStatus;
use App\Domain\States\ToolReturnedCondition;

class ToolRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $prefix = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'title' => [$prefix, 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'daily_rate' => [$prefix, 'numeric', 'min:0.01', 'max:999999.99'],
            'category_id' => [$prefix, 'integer', 'exists:categories,id'],
            'availability_status' => ['sometimes', Rule::Enum(ToolStatus::class)],
            'condition' => [$prefix, Rule::Enum(ToolCondition::class)],
            'returned_condition' => ['nullable', Rule::Enum(ToolReturnedCondition::class)],
            'picture' => ['nullable', 'url', 'max:2048']
        ];
    }
}
