@props([
    'customer' => [],
])

<table class="min-w-full divide-y divide-gray-100" style="table-layout: fixed;">

    <thead class="bg-gray-50 border-b border-gray-300 sticky top-0">

    <tr>
        <th scope="col"
            class="py-3 pl-2 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
            {{ __('Date') }}
        </th>
        <th scope="col"
            class="py-3 pr-2 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
            {{ __('Time') }}
        </th>
        <th scope="col"
            class="py-3 pr-2 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
            {{ __('User') }}
        </th>
        <th scope="col"
            class="py-3 pr-2 text-left text-xs font-medium uppercase tracking-wide text-gray-500">
            {{ __('Change') }}
        </th>
    </tr>
    </thead>

    <tbody class="divide-y divide-gray-200 bg-white">

    @foreach($customer['audits'] ?? [] as $audit)
        <tr>
            <td class="whitespace-nowrap px-3 py-2 text-sm text-gray-900">
                {{ Carbon\Carbon::parse($audit['created_at'])->format('d.m.Y') }}
            </td>
            <td class="whitespace-nowrap px-3 py-2 text-sm text-gray-900">
                {{ Carbon\Carbon::parse($audit['created_at'])->format('H:i') }}
            </td>
            <td class="whitespace-nowrap px-3 py-2 text-sm text-gray-900">
                {{ \Noerd\Models\NoerdUser::find($audit['user_id'])?->email }}
            </td>
            <td class="whitespace-nowrap px-3 py-2 text-sm text-gray-900">
                @foreach($audit['new_values'] as $key => $value)
                    <div>
                        <code
                                class="rounded-sm p-0.5 text-xs font-bold mr-1">{{ $key }}</code>
                        <code
                                class="rounded-sm p-0.5 px-1 text-xs bg-red-100 mx-1">{{ $audit['old_values'][$key] ?? '' }}</code>
                        to <code
                                class="rounded-sm p-0.5 px-1 text-xs bg-green-100 mx-1"> {{ $value }}</code>
                    </div>
                @endforeach
            </td>
        </tr>
    @endforeach

    </tbody>
</table>
