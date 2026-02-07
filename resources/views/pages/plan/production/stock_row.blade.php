<td>{{ $stock->Mfgbatchno }}</td>
<td>{{ $stock->ARNO }}</td>
<td>
    <div>
        {{ $stock->Expirydate
            ? \Carbon\Carbon::parse($stock->Expirydate)->format('d/m/Y')
            : '' }}
    </div>
    <div>
        {{ $stock->Retestdate
            ? \Carbon\Carbon::parse($stock->Retestdate)->format('d/m/Y')
            : '' }}
    </div>
</td>
<td>{{ $stock->Mfg }}</td>
<td>{{ round($stock->ReceiptQuantity,4) }} {{ $stock->MatUOM }}</td>
<td>{{ round($stock->{'Total Qty'},4) }} {{ $stock->MatUOM }}</td>
<td class="text-center">
    @php
        $label = lable_status($stock->GRNSts, $stock->ARNO);
    @endphp
    <span style="
        background-color: {{ $label['color'] }};
        color: white;
        padding: 4px 12px;
        border-radius: 14px;
        font-size: 0.85rem;
        font-weight: 600;
        white-space: nowrap;
    ">
        {{ $label['text'] }}
    </span>
    <br>
    {{ $stock->ARNO }}

    @if (session('user')['userGroup'] === 'Admin')
        <div class="text-muted small">
            GRNSts: {{ $stock->GRNSts }}
        </div>
    @endif
</td>
