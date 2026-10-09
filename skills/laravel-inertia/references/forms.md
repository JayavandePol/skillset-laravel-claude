# Forms & validation (FE-07)

Server-side FormRequest validation is mandatory (SEC-01); the client layer is for UX.

## Option A — Inertia `useForm` (default)

```tsx
import { useForm } from '@inertiajs/react';
import { store } from '@/actions/App/Http/Controllers/InvoiceController';

export function InvoiceForm({ customer }: { customer: CustomerData }) {
  const form = useForm({ issued_at: today(), due_at: inDays(30), notes: '', lines: [emptyLine()] });

  const submit = (e: React.FormEvent) => {
    e.preventDefault();
    form.post(store(customer).url, { preserveScroll: true, onSuccess: () => form.reset() });
  };

  return (
    <form onSubmit={submit} noValidate>
      <Label htmlFor="notes">Notes</Label>
      <Textarea id="notes" value={form.data.notes} onChange={(e) => form.setData('notes', e.target.value)}
        aria-invalid={!!form.errors.notes} aria-describedby="notes-error" />
      <InputError id="notes-error" message={form.errors.notes} />
      {/* nested errors: form.errors['lines.0.description'] */}
      <Button disabled={form.processing}>Save</Button>
    </form>
  );
}
```

Or the declarative `<Form action={store(customer)} …>` component (v2.1+/v3) which handles data from inputs, errors, `processing`, and reset options.

## Option B — React Hook Form + Zod (complex forms)

Multi-step wizards, dynamic arrays, cross-field rules, instant feedback:

```tsx
const schema = z.object({
  issued_at: z.string().date(),
  due_at: z.string().date(),
  notes: z.string().max(5000).optional(),
  lines: z.array(z.object({
    description: z.string().trim().min(1, 'Required').max(500),
    quantity: z.coerce.number().positive(),
    unit_price: z.coerce.number().int().min(0),
  })).min(1).max(200),
}).refine((v) => v.due_at >= v.issued_at, { path: ['due_at'], message: 'Due date must be after issue date' });

type Values = z.infer<typeof schema>;

export function InvoiceWizard({ customer }: Props) {
  const { register, control, handleSubmit, setError, formState } = useForm<Values>({ resolver: zodResolver(schema), defaultValues });
  const lines = useFieldArray({ control, name: 'lines' });

  const onSubmit = (values: Values) =>
    router.post(store(customer).url, values, {
      preserveScroll: true,
      onError: (errors) => {
        for (const [field, message] of Object.entries(errors)) {
          setError(field as FieldPath<Values>, { type: 'server', message }); // 'lines.0.description' maps directly
        }
      },
    });

  return <form onSubmit={handleSubmit(onSubmit)} noValidate>…</form>;
}
```

Keep Zod rules ⊆ FormRequest rules; the server is authoritative. Vue: VeeValidate + Zod; Svelte: Superforms-style or plain Zod.

## Option C — Precognition (live server validation, no duplicated rules)

```bash
npm install laravel-precognition-react   # or -vue / -react-inertia variants — check docs for v3
```

```php
Route::post('invoices', [InvoiceController::class, 'store'])->middleware([HandlePrecognitiveRequests::class]);
```

The client validates a field on blur against the real FormRequest. Great for registration/checkout.

## File uploads

```tsx
const form = useForm<{ file: File | null }>({ file: null });
<input type="file" onChange={(e) => form.setData('file', e.target.files?.[0] ?? null)} />
{form.progress && <Progress value={form.progress.percentage} />}
form.post(upload(invoice).url, { forceFormData: true });
```

PUT/PATCH with files: use `post` with `_method: 'put'` (multipart limitation).

## UX rules

- Disable submit while `processing`; show a spinner after ~300ms.
- Focus the first invalid field after a failed submit.
- Warn on unsaved changes when leaving (`form.isDirty` + router `before` event).
- Success → toast via flash; destructive actions → confirmation dialog.
