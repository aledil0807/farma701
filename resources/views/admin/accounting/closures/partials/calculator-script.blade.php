<script>
    document.addEventListener('DOMContentLoaded', function () {
        const table = document.querySelector('[data-accounting-table]');

        if (!table) return;

        function parseAmount(value) {
            value = String(value || '').trim();

            if (!value) return 0;

            value = value
                .replaceAll('Bs.', '')
                .replaceAll('Bs', '')
                .replaceAll('bs.', '')
                .replaceAll('bs', '')
                .replaceAll(' ', '');

            const hasComma = value.includes(',');
            const hasDot = value.includes('.');

            if (hasComma && hasDot) {
                value = value.replaceAll('.', '');
                value = value.replace(',', '.');
            } else if (hasComma) {
                value = value.replace(',', '.');
            }

            value = value.replace(/[^0-9.-]/g, '');

            const number = Number(value);

            return Number.isFinite(number) ? number : 0;
        }

        function formatBs(value) {
            return 'Bs. ' + Number(value || 0).toLocaleString('es-VE', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
        }

        function updateAccountingTable() {
            let totalFacturado = 0;
            let totalEntregado = 0;
            let totalTransactions = 0;

            table.querySelectorAll('[data-accounting-row]').forEach(function (row) {
                const facturadoInput = row.querySelector('[data-facturado]');
                const entregadoInput = row.querySelector('[data-entregado]');
                const differenceEl = row.querySelector('[data-difference]');
                const transactionsInput = row.querySelector('[data-transactions]');

                const facturado = parseAmount(facturadoInput?.value);
                const entregado = parseAmount(entregadoInput?.value);
                const difference = entregado - facturado;
                const transactions = Number(transactionsInput?.value || 0);

                totalFacturado += facturado;
                totalEntregado += entregado;
                totalTransactions += Number.isFinite(transactions) ? transactions : 0;

                if (differenceEl) {
                    differenceEl.textContent = formatBs(difference);
                    differenceEl.classList.toggle('accounting-difference-negative', difference < 0);
                    differenceEl.classList.toggle('accounting-difference-positive', difference > 0);
                }
            });

            const totalDifference = totalEntregado - totalFacturado;

            table.querySelector('[data-total-facturado]').textContent = formatBs(totalFacturado);
            table.querySelector('[data-total-entregado]').textContent = formatBs(totalEntregado);
            table.querySelector('[data-total-difference]').textContent = formatBs(totalDifference);
            table.querySelector('[data-total-transactions]').textContent = totalTransactions;

            table.querySelector('[data-total-difference]').classList.toggle('accounting-difference-negative', totalDifference < 0);
            table.querySelector('[data-total-difference]').classList.toggle('accounting-difference-positive', totalDifference > 0);
        }

        table.addEventListener('input', updateAccountingTable);

        updateAccountingTable();
    });
</script>