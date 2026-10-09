# spatie/laravel-medialibrary

v11 supports Laravel 13 from **11.21.0** — require `^11.21` (or newer major if released).

## Install

```bash
composer require spatie/laravel-medialibrary:"^11.21"
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-migrations"
php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-config"
php artisan migrate
```

System tools for conversions/optimisation: GD or Imagick, `jpegoptim`, `optipng`, `pngquant`, `gifsicle`, `webp` (`cwebp`), `ffmpeg` for video thumbnails (install in the Docker image).

## Config highlights (`config/media-library.php`)

- `disk_name` → `s3` (or a private local disk).
- `queue_conversions_by_default` → `true`; `queue_connection_name` / `queue_name` → `media`.
- `max_file_size` aligned with validation.
- `path_generator` → custom generator that prefixes `tenants/{id}/` for tenant isolation.

## Model

```php
final class Project extends Model implements HasMedia
{
    use InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp'])
            ->useFallbackUrl('/images/placeholder.webp');

        $this->addMediaCollection('documents')
            ->acceptsMimeTypes(['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
            ->useDisk('s3-private');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(Fit::Crop, 400, 300)
            ->format('webp')
            ->performOnCollections('cover')
            ->queued();

        $this->addMediaConversion('large')
            ->width(1600)
            ->format('webp')
            ->withResponsiveImages()
            ->performOnCollections('cover')
            ->queued();
    }
}
```

## Upload via Action

```php
final readonly class AttachProjectDocument
{
    public function handle(User $actor, Project $project, UploadedFile $file): Media
    {
        return $project->addMedia($file)
            ->usingFileName(Str::uuid().'.'.$file->extension())
            ->usingName(Str::limit(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), 100, ''))
            ->withCustomProperties(['uploaded_by' => $actor->id])
            ->toMediaCollection('documents');
    }
}
```

Validation stays in the FormRequest (SEC-50); `acceptsMimeTypes` is a second line of defence.

## Serving

- Public images: `$project->getFirstMediaUrl('cover', 'thumb')`; responsive: `{{ $media->img('large') }}` or `getSrcset('large')`.
- Private documents: controller with policy check → `return $media->toResponse($request)` (stream) or `redirect($media->getTemporaryUrl(now()->addMinutes(5)))`.
- Downloads of multiple: `MediaStream::create('documents.zip')->addMedia($media)`.

## Maintenance

- `php artisan media-library:regenerate` after changing conversions (queued, can be scoped by model).
- `php artisan media-library:clean` to remove orphaned files/conversions.
- Deleting a model deletes its media; for soft-deleted models, media stays until force delete.
