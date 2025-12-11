<x-mail::message>
# New reply in {{ $channel->name ?? 'channel' }}

**From:** {{ $message->user->name ?? 'Unknown' }}  
**To:** {{ $recipient->name ?? 'You' }}  
**When:** {{ $message->created_at?->toDayDateTimeString() }}

@if($message->parent)
**In reply to:** {{ $message->parent->user->name ?? 'Unknown' }} — "{{ \Illuminate\Support\Str::limit($message->parent->body, 80) }}"
@endif

---

{{ $message->body }}

@if($message->file_name)
> Attachment: {{ $message->file_name }} ({{ $message->formatted_file_size ?? '' }})
@endif

<x-mail::button :url="config('app.url').'/team/'.$channel->team->slug.'/channel/'.$channel->id">
Open Channel
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
