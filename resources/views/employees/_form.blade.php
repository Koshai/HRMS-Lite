@php
    $user = $employee?->user;
@endphp

<div>
    <label for="name" class="mb-1 block text-sm font-medium">Name</label>
    <input id="name" name="name" type="text" value="{{ old('name', $user?->name) }}" required class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
    @error('name') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
</div>

<div>
    <label for="email" class="mb-1 block text-sm font-medium">Email</label>
    <input id="email" name="email" type="email" value="{{ old('email', $user?->email) }}" required class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
    @error('email') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
</div>

@if ($includePassword)
    <div>
        <label for="password" class="mb-1 block text-sm font-medium">Temporary password</label>
        <input id="password" name="password" type="password" required autocomplete="new-password" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
        <p class="mt-1 text-xs text-slate-500">Share this outside the app. The employee must change it on first sign-in. It will not be shown again.</p>
        @error('password') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
    </div>
@endif

<div>
    <label for="phone" class="mb-1 block text-sm font-medium">Phone</label>
    <input id="phone" name="phone" type="text" value="{{ old('phone', $employee?->phone) }}" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
    @error('phone') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
</div>

<div>
    <label for="address" class="mb-1 block text-sm font-medium">Address</label>
    <textarea id="address" name="address" rows="3" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">{{ old('address', $employee?->address) }}</textarea>
    @error('address') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
</div>

<div>
    <label for="blood_group" class="mb-1 block text-sm font-medium">Blood group</label>
    <select id="blood_group" name="blood_group" class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
        <option value="">Not specified</option>
        @foreach ($bloodGroups as $group)
            <option value="{{ $group->value }}" @selected(old('blood_group', $employee?->blood_group?->value) === $group->value)>{{ $group->value }}</option>
        @endforeach
    </select>
    @error('blood_group') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
</div>

<div>
    <label for="designation" class="mb-1 block text-sm font-medium">Designation</label>
    <input id="designation" name="designation" type="text" value="{{ old('designation', $employee?->designation) }}" required class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
    @error('designation') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
</div>

<div>
    <label for="base_salary_amount" class="mb-1 block text-sm font-medium">Monthly base salary ({{ config('hrms.currency') }})</label>
    <input id="base_salary_amount" name="base_salary_amount" type="number" min="0" step="0.01" value="{{ old('base_salary_amount', $employee?->base_salary_amount) }}" required class="w-full rounded-md border border-slate-300 px-3 py-2 text-sm">
    @error('base_salary_amount') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror
</div>

<div>
    <label for="image" class="mb-1 block text-sm font-medium">Profile image</label>
    <input id="image" name="image" type="file" accept="image/jpeg,image/png,image/webp" class="w-full text-sm">
    <p class="mt-1 text-xs text-slate-500">JPEG, PNG, or WebP up to 2 MB. Stored privately.</p>
    @error('image') <p class="mt-1 text-sm text-red-700">{{ $message }}</p> @enderror

    @if ($employee?->hasPhoto())
        <label class="mt-2 flex items-center gap-2 text-sm">
            <input type="checkbox" name="remove_image" value="1" @checked(old('remove_image'))>
            Remove current image
        </label>
    @endif
</div>
