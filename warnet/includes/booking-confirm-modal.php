<div class="modal-backdrop" id="confirm-modal" aria-hidden="true">
  <section class="confirm-modal" role="dialog" aria-modal="true" aria-labelledby="modal-title">
    <div class="modal-icon" aria-hidden="true">⏳</div>
    <h2 id="modal-title">Memproses pembayaran</h2>
    <p>Pembayaran demo sedang diproses. Booking akan dikonfirmasi otomatis; tidak ada transaksi uang sungguhan.</p>
    <p id="payment-processing" class="payment-processing" role="status" aria-live="polite" hidden>
      <span class="payment-processing__spinner" aria-hidden="true"></span>
      Mohon tunggu sebentar...
    </p>
    <div class="modal-summary"><span>Total simulasi</span><strong><?= rupiah($total) ?></strong></div>
  </section>
</div>
