{{-- Code the Super Admin added in Platform Settings. $where is "head" or "body", $area is public, auth, app or superadmin. --}}
{!! \App\Models\PlatformSetting::get()->customCode($where, $area) !!}
