<x-mail::message>
<div dir="rtl" style="direction:rtl;text-align:right;">

# تم نشر وظيفة جديدة

الوظيفة: **{{ $opening->title }}**

<x-mail::button :url="$publicUrl">
فتح صفحة الوظيفة
</x-mail::button>

{{ $publicUrl }}

</div>
</x-mail::message>
