<x-mail::message>
# {{ $model }} Export Ready

Your requested {{ $model }} export is ready. The file **{{ $fileName }}** has been attached to this email.

The export includes the following columns:

<x-mail::table>
| Included Columns |
|------------------|
@foreach ($columns as $column)
| {{ $column }} |
@endforeach
</x-mail::table>

Thank you for using our service!,<br>
{{ config('app.name') }}
</x-mail::message>
