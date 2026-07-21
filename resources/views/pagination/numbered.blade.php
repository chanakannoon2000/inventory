@if ($paginator->hasPages() || $paginator->total() > 0)
    <div class="pager-wrap no-print">
        <div class="pager-left">
            <label class="pager-length">
                <span>แสดง</span>
                <select onchange="changePageLength(this.value)">
                    @foreach([25, 50, 100] as $n)
                        <option value="{{ $n }}" @selected((int) request('per_page', $paginator->perPage()) === $n)>{{ $n }}</option>
                    @endforeach
                </select>
                <span>รายการ / หน้า</span>
            </label>
            <div class="pager-info helptext">
                แสดง {{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }}
                จาก {{ number_format($paginator->total()) }} รายการ
            </div>
        </div>
        <nav class="pagination">
            @if ($paginator->onFirstPage())
                <span class="disabled">‹ ก่อนหน้า</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}">‹ ก่อนหน้า</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="disabled">{{ $element }}</span>
                @endif

                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="active"><span>{{ $page }}</span></span>
                        @else
                            <a href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}">ถัดไป ›</a>
            @else
                <span class="disabled">ถัดไป ›</span>
            @endif
        </nav>
    </div>
    <script>
    function changePageLength(n){
      var url = new URL(window.location.href);
      url.searchParams.set('per_page', n);
      url.searchParams.set('page', '1');
      window.location.href = url.toString();
    }
    </script>
@endif
