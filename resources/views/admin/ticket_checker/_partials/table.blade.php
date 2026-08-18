<div class="table-responsive mt-4">
    <table class="table mob-view">
        <thead>
            <tr>
                <th>S.No</th>
                <th>Name</th>
                <th>Email</th>
                <th>Created By</th>
                <th>Operations</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($checkers as $index => $checker)
                <!-- TR 1 -->
                <tr>
                    <td>
                        <div class="data-label">S.No</div>
                        <div>{{ $checkers->firstItem() + $index }}</div>
                    </td>

                    <td>
                        <div class="data-label">Name</div>
                        <div>{{ $checker->name ?? 'N/A' }}</div>
                    </td>
                    <td>
                        <div class="data-label">Email</div>
                        <div><span class="text-break">{{ $checker->email ?? 'N/A' }}</span></div>
                    </td>
                    <td>
                        <div class="data-label">Created By</div>
                            @if($checker->creator)
                                <div style="font-weight: 500;">
                                    <div>{{ $checker->creator->name }}</div>
                                   <div style="font-size: 0.85em; margin-top: 2px;" class="text-break ">
                                    {{ $checker->creator->email }}
                                   </div>
                                </div>
                            @else
                                <div>N/A</div>
                            @endif
                        <div>                       
                    </td>

                    <td>
                        <div class="data-label">Operations</div>
                        <div>
                            <div class="action-row">
                                <a href="{{ route('admin.checkers.view', $checker->id) }}" class="action-btn universal">
                                    <i class="fa-regular fa-eye"></i>
                                </a>
                                <a href="{{ route('admin.checkers.edit', $checker->id) }}" role="button"
                                    class="action-btn edit">
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </a>
                                <button class="action-btn delete"
                                    data-url="{{ route('admin.checkers.destroy', $checker->id) }}"
                                    data-name="{{ $checker->name ?: ($checker->email ?: 'this checker account') }}"
                                    data-bs-toggle="modal"
                                    data-bs-target="#deleteModal">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">
                        <div class="text-center">No data found!</div>
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if ($checkers->hasPages())
    <div class="pagination">
        {{ $checkers->links() }}
    </div>
@endif
