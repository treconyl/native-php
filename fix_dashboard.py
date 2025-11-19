import pathlib, re
path = pathlib.Path('resources/views/admin/dashboard.blade.php')
text = path.read_text(encoding='utf-8')
pattern = r'\s{24}<tbody>.*?\s{24}</tbody>'
new_block = '''                        <tbody>
                        @forelse( as )
                            @php( = ->meta ?? [])
                            @php( = ['last_proxy_http'] ?? null)
                            @php( = ['last_proxy_username'] ?? null)
                            @php( = ['last_proxy_password'] ?? null)
                            @php( =  ?  . (( && ) ? ':' .  . ':' .  : '') : null)
                            @php( = match (->status) {
                                'running' => 'Ðang ch?y',
                                'expired' => 'H?t h?n',
                                default => 'Chua ch?y',
                            })
                            @php( = match (->status) {
                                'running' => 'bg-emerald-100 text-emerald-900',
                                'expired' => 'bg-rose-100 text-rose-700',
                                default => 'bg-slate-100 text-slate-600',
                            })
                            <tr class="border-t border-slate-100">
                                <td class="px-4 py-3">{{ ->label }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-medium {{  }}">
                                        {{  }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-600">
                                    @if()
                                        <div class="font-mono">{{  }}</div>
                                    @else
                                        <span class="text-slate-400">Chua có IP</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">{{ optional(->last_used_at)->format('d/m/Y H:i') ?? 'Chua s? d?ng' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-2">
                                        @if(->status !== 'running')
                                            <form method="POST" action="{{ route('admin.proxy.start', ) }}">
                                                @csrf
                                                <button type="submit" class="px-3 py-1 rounded-full bg-slate-900 text-white text-xs cursor-pointer">Ch?y</button>
                                            </form>
                                        @endif
                                        @if(->status === 'running')
                                            <form method="POST" action="{{ route('admin.proxy.stop', ) }}">
                                                @csrf
                                                <button type="submit" class="px-3 py-1 rounded-full bg-rose-100 text-rose-700 text-xs cursor-pointer">D?ng</button>
                                            </form>
                                        @endif
                                        <form method="POST" action="{{ route('admin.proxy.test', ) }}">
                                            @csrf
                                            <input type="hidden" name="nhamang" value="random">
                                            <input type="hidden" name="tinhthanh" value="0">
                                            <button type="submit" class="px-3 py-1 rounded-full bg-emerald-100 text-emerald-700 text-xs cursor-pointer">Test API</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.proxy.rotate', ) }}">
                                            @csrf
                                            <button type="submit" class="px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-xs cursor-pointer">Xoay IP</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-6 text-center text-slate-400">Chua có key nào.</td>
                            </tr>
                        @endforelse
                        </tbody>
'''
new_text, count = re.subn(pattern, new_block, text, flags=re.S)
if count == 0:
    raise SystemExit('pattern not found')
path.write_text(new_text, encoding='utf-8')
