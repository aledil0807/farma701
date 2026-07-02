<div class="admin-card quote-total-card" data-quote-total-card>
    <h2>Total general</h2>

    <p>
        $ {{ number_format((float) $quote->total_usd, 2, '.', ',') }}
        |
        Bs. {{ number_format((float) $quote->total_bs, 2, ',', '.') }}
    </p>
</div>