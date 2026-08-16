<x-layouts.dashboard heading="الأقسام" nav="admin">
    <div class="split">
        <div class="stack-12">
            @foreach ($roots as $root)
                <section class="panel">
                    <div class="panel__head">
                        <h2>{{ $root->name }}</h2>
                        <span class="small muted num">{{ $root->products_count }} منتج</span>
                    </div>
                    <div class="panel__body">
                        <details>
                            <summary class="small strong" style="cursor:pointer">تعديل القسم</summary>
                            <form method="POST" action="{{ route('admin.categories.update', $root) }}" style="margin-block-start:12px">
                                @csrf @method('PUT')
                                <x-ui.field name="name" label="الاسم" :value="$root->name" required />
                                <x-ui.field name="meta_title" label="عنوان الصفحة" :value="$root->meta_title" />
                                <x-ui.field name="meta_description" label="وصف الصفحة" type="textarea" :value="$root->meta_description" />
                                <label class="check" style="margin-block-start:10px">
                                    <input type="checkbox" name="is_active" value="1" @checked($root->is_active)>
                                    <span>نشط</span>
                                </label>
                                <button type="submit" class="btn btn--sm btn--primary" style="margin-block-start:12px">حفظ</button>
                            </form>
                        </details>

                        @if ($root->children->isNotEmpty())
                            <ul class="pill-group" style="margin-block-start:14px">
                                @foreach ($root->children as $child)
                                    <li>
                                        <a href="{{ $child->url() }}" class="pill">
                                            {{ $child->name }}
                                            <span class="dim num">{{ $child->products_count }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </section>
            @endforeach
        </div>

        <aside class="panel">
            <div class="panel__head"><h2>إضافة قسم</h2></div>
            <div class="panel__body">
                <form method="POST" action="{{ route('admin.categories.store') }}">
                    @csrf
                    <x-ui.field name="name" label="اسم القسم" required />
                    <x-ui.select-field name="parent_id" label="القسم الرئيسي" :options="$options"
                                       placeholder="قسم رئيسي جديد" />
                    <x-ui.field name="description" label="وصف" type="textarea" />
                    <x-ui.field name="position" label="الترتيب" type="number" value="0" />
                    <label class="check" style="margin-block-start:10px">
                        <input type="checkbox" name="is_active" value="1" checked>
                        <span>نشط</span>
                    </label>
                    <button type="submit" class="btn btn--primary" style="margin-block-start:14px">إضافة</button>
                </form>
            </div>
        </aside>
    </div>
</x-layouts.dashboard>
