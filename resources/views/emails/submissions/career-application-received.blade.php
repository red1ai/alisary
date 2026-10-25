<x-mail::message>
<div dir="rtl" style="direction:rtl;text-align:right;">

# طلب توظيف جديد

تم استلام طلب جديد على وظيفة **{{ $application->careerOpening->title }}**.

- المتقدم: {{ $application->full_name }}
- البريد الإلكتروني: {{ $application->email }}
- الهاتف: {{ $application->phone }}
- الرقم المرجعي: {{ $application->reference_number }}

لا يتضمن هذا البريد الملفات المرفوعة.

</div>

<x-mail::button :url="$adminUrl" color="success">
فتح الطلب
</x-mail::button>
</x-mail::message>
