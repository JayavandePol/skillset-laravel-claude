# Validation (SEC-01 … SEC-04)

## FormRequest template

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests\Invoices;

use App\Data\InvoiceData;
use App\Enums\VatRate;
use App\Models\Invoice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreInvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', [Invoice::class, $this->route('customer')]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $tenantId = $this->user()->current_tenant_id;

        return [
            'issued_at' => ['required', 'date'],
            'due_at' => ['required', 'date', 'after_or_equal:issued_at'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'lines' => ['required', 'array', 'min:1', 'max:200'],
            'lines.*.description' => ['required', 'string', 'max:500'],
            'lines.*.quantity' => ['required', 'numeric', 'gt:0', 'max:1000000'],
            'lines.*.unit_price' => ['required', 'integer', 'min:0'], // cents
            'lines.*.vat_rate' => ['required', Rule::enum(VatRate::class)],
            'lines.*.product_id' => ['nullable', Rule::exists('products', 'id')->where('tenant_id', $tenantId)],
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return ['lines.*.unit_price' => __('unit price')];
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['notes' => is_string($this->notes) ? trim($this->notes) : $this->notes]);
    }

    public function toData(): InvoiceData
    {
        return InvoiceData::fromValidated($this->validated());
    }
}
```

## Checklist per field

| Type | Rules to always include |
|---|---|
| string | `string`, `max:N` (match the column), `trim` via prepareForValidation where relevant |
| text/HTML | `string`, `max`, server-side sanitise on save if rendered as HTML |
| email | `email:rfc,dns` (or `email:rfc` offline), `max:254`, lowercase |
| integer/money | `integer`, `min`, `max` — money in cents |
| decimal | `decimal:0,2` or `numeric` + `between` |
| date | `date`/`date_format`, `after`/`before` where logical |
| enum | `Rule::enum(Enum::class)` (with `->only()`/`->except()` for allowed subsets) |
| foreign id | `Rule::exists(...)->where(tenant scope)` |
| array | `array`, `min`, `max` (always bound array size), `list` when sequential; whitelist keys with `array:key1,key2` |
| file | `File::types([...])->max(kb)`; images `File::image()->dimensions(...)` |
| URL | `url:https`, `active_url` if DNS check wanted, `max:2048` |
| boolean | `boolean` (and `accepted` for consents) |
| password | `Password::defaults()`, `confirmed` |

## Livewire & Filament

- Livewire 4: `#[Validate]` attributes or `$this->validate()` in actions; validate on every action, not only on `updated` hooks. Use Form objects for larger forms.
- Filament: form field rules (`->required()->maxLength(255)`), plus custom actions validate their own data. Filament still calls the policy, but custom actions must authorize themselves (`->authorize('update')`).

## Mass assignment

```php
protected $fillable = ['title', 'description', 'due_at'];   // never tenant_id, role, is_admin, owner_id from requests
```

Set ownership columns explicitly in the Action: `$model->owner()->associate($actor)`.

## Precognition (optional)

Laravel Precognition reuses FormRequest rules for live frontend validation — one source of truth instead of duplicating rules in Zod/JS.
