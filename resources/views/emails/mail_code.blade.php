<x-mail::message>
# Ваш код
 
Никому не говорите!
 
<x-mail::panel>
{{ $code }}
</x-mail::panel>
 
С уважением,<br>
{{ config('app.name') }}
</x-mail::message>