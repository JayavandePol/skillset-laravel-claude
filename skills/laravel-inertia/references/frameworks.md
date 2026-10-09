# Framework specifics

## React 19

- Package: `@inertiajs/react`. Hooks: `usePage`, `useForm`, `useRemember`, `usePoll`, `usePrefetch`, `useHttp` (v3).
- UI: shadcn/ui (Radix or Base UI primitives), lucide-react icons, `sonner` toasts.
- Forms: Inertia `useForm`/`<Form>`; complex → React Hook Form + Zod.
- Tables: TanStack Table with server-side pagination/sort via URL params.
- React Compiler: enable if the starter kit ships it; otherwise memoise deliberately (`useMemo` for expensive derived data only).
- Strict Mode on; fix double-effect issues rather than disabling.

## Vue 3

- Package: `@inertiajs/vue3`. Composition API with `<script setup lang="ts">` only.
- `defineProps<Props>()` typed; `usePage<SharedData>()`.
- UI: shadcn-vue (Reka UI), lucide-vue-next.
- Forms: `useForm`; complex → VeeValidate + Zod.
- State: Pinia only for client-only cross-tree state.

```vue
<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
const props = defineProps<{ customer: CustomerData }>();
const form = useForm({ notes: '' });
const submit = () => form.post(store(props.customer).url, { preserveScroll: true });
</script>

<template>
  <form @submit.prevent="submit">
    <Textarea v-model="form.notes" :aria-invalid="!!form.errors.notes" />
    <InputError :message="form.errors.notes" />
    <Button :disabled="form.processing">Save</Button>
  </form>
</template>
```

## Svelte 5

- Package: `@inertiajs/svelte`. Runes (`$props()`, `$state`, `$derived`); TypeScript.
- UI: shadcn-svelte (Bits UI).
- Forms: `useForm` store from `@inertiajs/svelte`.

## Legacy (Inertia v1/v2) upgrades

- Read the v3 upgrade guide: React <19 unsupported, Node 24+, ESM-only, Axios optional, renamed events (`invalid`, `exception`), removed `hideProgress`/`revealProgress`, `useDataElementForInitialPage` → `useDataAttributeForInitialPage`.
- Ziggy → Wayfinder migration can be gradual (both can coexist).
