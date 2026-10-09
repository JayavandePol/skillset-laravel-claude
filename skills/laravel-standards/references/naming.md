# Naming conventions

| Thing | Convention | Example |
|---|---|---|
| Model | singular StudlyCase | `InvoiceLine` |
| Table | plural snake_case | `invoice_lines` |
| Pivot table | singular models, alphabetical | `card_label` |
| Column | snake_case | `due_at`, `is_archived` |
| Foreign key | `<singular>_id` | `customer_id` |
| Timestamp | past participle + `_at` | `sent_at`, `approved_at` |
| Boolean | `is_`/`has_`/`can_` prefix | `is_active` |
| Migration | descriptive snake | `create_invoice_lines_table`, `add_paid_at_to_invoices_table` |
| Controller | singular + `Controller`; invokable = action | `InvoiceController`, `SendInvoiceController` |
| FormRequest | verb + model + `Request` | `StoreInvoiceRequest`, `UpdateInvoiceRequest` |
| Action | verb + noun | `SendInvoice`, `MergeCustomers` |
| Event | noun + past-tense verb | `InvoiceSent` |
| Listener | what it does | `NotifyAccountantOfInvoice` |
| Job | imperative | `GenerateInvoicePdf` |
| Notification / Mail | what the recipient gets | `InvoiceOverdueNotification`, `WelcomeMail` |
| Policy | model + `Policy` | `InvoicePolicy` |
| Enum | singular | `InvoiceStatus` |
| Enum case | StudlyCase, value snake/kebab | `case PartiallyPaid = 'partially_paid';` |
| Route URI | plural kebab | `/invoice-lines` |
| Route name | dotted, plural resource | `invoices.lines.store` |
| Config key | snake_case | `config('billing.trial_days')` |
| Translation key | dotted or JSON sentence | `__('invoices.sent')` / `__('Invoice sent.')` |
| Blade/Livewire view | kebab-case | `invoice-table.blade.php` |
| React/Vue component file | per starter kit (kebab or Pascal) — consistent | `invoice-table.tsx` |
| Test | describes behaviour | `it('rejects lines with negative quantities')` |
| Variable / method | camelCase, intent-revealing | `$overdueInvoices`, `markAsPaid()` |

Relationship methods: singular for `belongsTo`/`hasOne` (`customer()`), plural for `hasMany`/`belongsToMany` (`lines()`).
