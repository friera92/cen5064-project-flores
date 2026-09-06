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
            'category_id' => [$prefix, 'integer', 'exists:categories,id'],
            'daily_rate' => [$prefix, 'numeric', 'min:0'],
            'availability_status' => ['sometimes', new Enum(ToolStatus::class)],
            'condition' => [$prefix, new Enum(ToolCondition::class)],
            'returned_condition' => ['nullable', new Enum(ToolReturnedCondition::class)],
            'picture' => [$prefix, 'string', 'max:255']
        ];
    }
}
