@component('mail::message')
# Reset Your Password

Hello {{ $user->contact_name ?? $user->name ?? 'there' }},

We received a request to reset your password for your Sydney Markets B2B buyer account. Click the button below to reset your password:

@component('mail::button', ['url' => $resetUrl, 'color' => 'primary'])
Reset Your Password
@endcomponent

**This link will expire in {{ $expiresIn }}** for your security.

If you didn't request a password reset, please ignore this email. Your password will remain unchanged.

For your security, this request was made from:
- **IP Address:** {{ request()->ip() }}
- **Time:** {{ now()->format('F j, Y \a\t g:i A T') }}

If you're having trouble clicking the "Reset Your Password" button, copy and paste the URL below into your web browser:

{{ $resetUrl }}

---

**Need Help?**

If you have any questions or concerns about your account security, please don't hesitate to contact our support team.

Thanks,<br>
{{ config('app.name') }} Team

@component('mail::subcopy')
This is an automated message. Please do not reply to this email.
@endcomponent
@endcomponent